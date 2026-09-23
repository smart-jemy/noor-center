<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CustomerNote;
use App\Models\Expense;
use App\Models\InventoryItem;
use App\Models\NoorNotification;
use App\Models\PartnerRepair;
use App\Models\PartnerSettlement;
use App\Models\PartnerTechnician;
use App\Models\RequestNote;
use App\Models\ServiceRequest;
use App\Models\UsedPart;
use App\Models\User;
use App\Support\Noor;
use App\Support\Permissions;
use Illuminate\Http\Request;

/**
 * لوحة الاستقبال — 6 تبويبات مطابقة للأصل:
 * الطلبات | المخزن | العملاء | الإحصائيات | المصروفات | شركاء الصيانة
 *
 * الصلاحيات (مطابقة للأصل):
 * - staff          = ADMIN + RECEPTION       (الطلبات، العملاء، الملاحظات، الإشعارات، بحث العميل)
 * - requestEditor  = ADMIN + RECEPTION + DEPT_MGR (المخزن، القطع، المصروفات، الشركاء)
 */
class ReceptionController extends Controller
{
    // ============================================================
    // 1) تبويب الطلبات
    // ============================================================

    public function index(Request $request)
    {
        $query = ServiceRequest::with('customer:id,name,phone', 'assignedTechnician:id,name', 'department:id,name');

        if ($request->filled('status') && in_array($request->status, ServiceRequest::STATUSES)) {
            $query->where('status', $request->status);
        }

        // فلترة حسب وضع الطلب (عادي/مستعجل/طوارئ) — من الإنذارات
        if ($request->filled('urgency') && array_key_exists($request->urgency, ServiceRequest::URGENCY_LABELS)) {
            $query->where('urgency', $request->urgency);
        }

        // فلترة المتأخرات (حسب مهلة الاستعجال — مش ميعاد الدخول/الخروج)
        if ($request->boolean('overdue')) {
            $query->whereNotIn('status', ['COMPLETED', 'CANCELLED'])
                ->where('expected_exit_at', '<', now());
        }

        // إخفاء المكتمل/الملغي — مفتاح مثل الأصل
        if ($request->boolean('hide_completed')) {
            $query->whereNotIn('status', ['COMPLETED', 'CANCELLED']);
        }

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($w) use ($q) {
                $w->where('order_number', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('device_type', 'like', "%{$q}%")
                    ->orWhere('brand', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$q}%"));
            });
        }

        $requests = $query->latest()->paginate(15)->withQueryString();

        $technicians = User::where('role', 'TECHNICIAN')->where('is_active', true)
            ->get(['id', 'name', 'specialty']);

        $stats = [
            'today' => ServiceRequest::whereDate('created_at', today())->count(),
            'pending' => ServiceRequest::where('status', 'PENDING')->count(),
            'contacted' => ServiceRequest::where('status', 'CONTACTED')->count(),
            'confirmed' => ServiceRequest::where('status', 'CONFIRMED')->count(),
            'inProgress' => ServiceRequest::where('status', 'IN_PROGRESS')->count(),
            'ready' => ServiceRequest::where('status', 'READY')->count(),
            'active' => ServiceRequest::whereIn('status', ['CONTACTED', 'CONFIRMED', 'IN_PROGRESS', 'READY', 'RETURNED'])->count(),
            'returned' => ServiceRequest::where('status', 'RETURNED')->count(),
            'overdue' => ServiceRequest::whereNotIn('status', ['COMPLETED', 'CANCELLED'])
                ->where('expected_exit_at', '<', now())->count(),
            'completedToday' => ServiceRequest::where('status', 'COMPLETED')->whereDate('completed_at', today())->count(),
        ];

        // لوحة المتابعة: طلبات متأخرة (حسب مهلة الاستعجال) + طلبات اليوم
        $overdue = ServiceRequest::whereNotIn('status', ['COMPLETED', 'CANCELLED'])
            ->where('expected_exit_at', '<', now())
            ->with('customer:id,name', 'department:id,name')
            ->orderBy('expected_exit_at')
            ->limit(6)->get();

        $todayRequests = ServiceRequest::whereDate('created_at', today())
            ->whereNotIn('status', ['COMPLETED', 'CANCELLED'])
            ->with('customer:id,name', 'department:id,name')
            ->orderBy('created_at')
            ->limit(8)->get();

        // إنذارات نشطة (شريط الحالة الحي)
        $alerts = \App\Support\Alerts::active();

        // أنواع الأجهزة النشطة بملحقاتها — لنموذج التسجيل (زي الأصل)
        $deviceTypes = \App\Models\DeviceType::where('is_active', true)->orderBy('sort_order')->get(['name', 'accessories']);

        return view('reception.index', compact('requests', 'technicians', 'stats', 'overdue', 'todayRequests', 'deviceTypes', 'alerts'));
    }

    /**
     * تسجيل جهاز واصل للمركز (عميل موجود أو جديد)
     * مطابق للأصل + قواعد التشغيل الجديدة:
     * - ميعاد الزيارة اختياري (مش اجباري) — المنطقة اختيارية كمان
     * - وضع الطلب: عادي / مستعجل / طوارئ — بيحدد مهلة التسليم (SLA)
     * - الطوارئ = صيانة فورية بمعنى الأصل (ميعاد اليوم بدون فترة)
     * الاسم مطلوب للعملاء الجدد فقط (زي الأصل)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name' => 'nullable|string|min:2|max:255',
            'customer_phone' => ['required', 'string', 'regex:/^01[0125][0-9]{8}$/', function (string $attribute, mixed $value, \Closure $fail) {
                // رقم واحد لكل حساب — الرقم المسجل كطاقم (استقبال/فني/أدمن/مدير قسم) مستحيل يبقى عميل
                $existing = User::where('phone', $value)->first();
                if ($existing && $existing->role !== User::ROLE_CUSTOMER) {
                    $fail('الرقم ده مسجل على النظام كحساب «'.$existing->roleLabel().'» ('.$existing->name.') — مينفعش يتسجل كعميل، رقم واحد لكل حساب تحت أي بند');
                }
            }],
            'device_type' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'issue_description' => 'required|string|min:2|max:3000',
            'area' => 'nullable|in:6_october,pyramids_gardens',
            'address' => 'nullable|string|max:500',
            // رقم التواصل الإضافي اختياري — لو فاضي التواصل على رقم العميل نفسه (بدون كتابة الرقم مرتين)
            'phone' => ['nullable', 'string', 'regex:/^01[0125][0-9]{8}$/'],
            'urgency' => 'nullable|in:normal,urgent,emergency',
            'is_immediate' => 'nullable',
            'preferred_date' => 'nullable|date',
            'preferred_time' => 'nullable|in:09:00-12:00,12:00-15:00,15:00-18:00,18:00-21:00',
            'extra_devices' => 'nullable|array|max:4',
            'extra_devices.*.device_type' => 'required|string|max:255',
            'extra_devices.*.brand' => 'nullable|string|max:255',
            'extra_devices.*.issue_description' => 'required|string|min:2|max:3000',
            'accessories' => 'nullable|array|max:10',
            'accessories.*' => 'string|max:100',
            'photos' => 'nullable|array|max:3',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'customer_phone.regex' => 'رقم موبايل العميل لازم 11 رقم يبدأ بـ 01',
            'device_type.required' => 'اختر نوع الجهاز',
            'issue_description.required' => 'اكتب وصف العطل',
            'issue_description.min' => 'اكتب وصف العطل',
            'phone.regex' => 'رقم التواصل الإضافي لازم 11 رقم يبدأ بـ 01',
            'photos.max' => 'أقصى 3 صور',
            'photos.*.max' => 'حجم الصورة أقصى 2MB',
            'extra_devices.*.device_type.required' => 'اكتب نوع الجهاز الإضافي',
            'extra_devices.*.issue_description.required' => 'اكتب وصف عطل الجهاز الإضافي',
            'extra_devices.*.issue_description.min' => 'اكتب وصف عطل الجهاز الإضافي',
        ]);

        // وضع الطلب: طوارئ = فوري بمعنى الأصل — ولو مفيش استعجال مختار يبقى عادي
        $urgency = $data['urgency'] ?? 'normal';
        $isImmediate = $urgency === 'emergency' || $request->boolean('is_immediate');

        // الميعاد اختياري بالكامل — الجهاز داخل المركز النهاردة أياً كان
        if ($isImmediate) {
            $preferredDate = now()->toDateString();
            $preferredTime = ServiceRequest::IMMEDIATE;
        } elseif (! empty($data['preferred_date']) && ! empty($data['preferred_time'])) {
            $preferredDate = $data['preferred_date'];
            $preferredTime = $data['preferred_time'];
        } else {
            // بدون ميعاد — مش اجباري
            $preferredDate = now()->toDateString();
            $preferredTime = '';
        }

        // اسم العميل: مطلوب للجديد فقط — لو عميل قديم بنستخدم اسمه المسجل
        // (أرقام الطاقم مرفوضة قبل كده في الفاليديشن — مينفعش تتعمل حسابات عملاء بنفس الرقم)
        $existing = User::where('phone', $data['customer_phone'])->first();
        if (! $existing && empty($data['customer_name'])) {
            return back()->withInput()->withErrors(['customer_name' => 'اسم العميل مطلوب للعملاء الجدد']);
        }

        // رقم التواصل: الإضافي لو اتحط، وإلا رقم العميل نفسه — بدون كتابة الرقم مرتين
        $contactPhone = ! empty($data['phone']) ? $data['phone'] : $data['customer_phone'];

        $customer = User::firstOrCreate(
            ['phone' => $data['customer_phone']],
            // الاسم مضمون هنا للعملاء الجدد (اتحقق فوق) — ?? null للأمان لو العميل القديم بعتش الاسم
            ['name' => $data['customer_name'] ?? null, 'role' => User::ROLE_CUSTOMER, 'password' => '123456']
        );
        // لو الاسم موجود بقاله فترة بدون تحديث، حدّثه لآخر اسم قاله العميل
        if ($customer->wasRecentlyCreated === false && ! empty($data['customer_name'] ?? null) && $customer->name !== $data['customer_name']) {
            $customer->update(['name' => $data['customer_name']]);
        }

        // رفع صور العطل (لأول جهاز بس — زي الأصل)
        $paths = [];
        foreach ($request->file('photos', []) as $file) {
            $name = \Illuminate\Support\Str::uuid().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $name);
            $paths[] = '/uploads/'.$name;
        }

        // الأجهزة: الأول + الإضافية (كل جهاز يبقى طلب مستقل بنفس بيانات العميل)
        // الملحقات المختارة تُدمج في وصف العطل (زي اختيار الملحقات في الأصل مع تحسين الحفظ)
        $mainDescription = $data['issue_description'];
        if (! empty($data['accessories'])) {
            $mainDescription .= "\n[ملحقات مع الجهاز: ".implode('، ', $data['accessories']).']';
        }

        $devices = [[
            'device_type' => $data['device_type'],
            'brand' => $data['brand'] ?? null,
            'issue_description' => $mainDescription,
        ]];
        foreach ($data['extra_devices'] ?? [] as $extra) {
            $devices[] = [
                'device_type' => $extra['device_type'],
                'brand' => $extra['brand'] ?? null,
                'issue_description' => $extra['issue_description'],
            ];
        }

        $lastRequest = null;
        foreach ($devices as $i => $device) {
            $serviceRequest = ServiceRequest::create([
                'order_number' => Noor::orderNumber(),
                'customer_id' => $customer->id,
                'device_type' => $device['device_type'],
                'brand' => $device['brand'],
                'issue_description' => $device['issue_description'],
                'area' => $data['area'] ?? '',
                'address' => $data['address'] ?? '',
                'phone' => $contactPhone,
                'preferred_date' => $preferredDate,
                'preferred_time' => $preferredTime,
                'photos' => $i === 0 ? $paths : [],
                'status' => 'PENDING',
                'urgency' => $urgency,
                // ميعاد الدخول = استلام الجهاز — وميعاد الخروج المتوقع = الدخول + مهلة الاستعجال
                'entered_at' => now(),
                'expected_exit_at' => now()->addHours(ServiceRequest::URGENCY_SLA_HOURS[$urgency] ?? 96),
            ]);

            Noor::routeDepartment($serviceRequest);

            Noor::notifyAdmins(
                'NEW_REQUEST',
                'جهاز واصل (استقبال)',
                $customer->name.' سجّل جهاز '.$device['device_type'].' ('.$serviceRequest->order_number.')',
                $serviceRequest->id
            );

            $lastRequest = $serviceRequest;
        }

        ActivityLog::record($request->user()->id, 'REQUEST_UPDATED', "استقبال سجّل ".count($devices).' جهاز للعميل '.$customer->name.' ('.$lastRequest->order_number.')');

        $msg = count($devices) > 1
            ? 'تم إنشاء '.count($devices).' طلبات بنجاح — رقم آخر طلب: '.$lastRequest->order_number.($customer->wasRecentlyCreated ? ' (تم إنشاء حساب للعميل الجديد)' : '')
            : 'تم التسجيل — رقم الطلب: '.$lastRequest->order_number.($customer->wasRecentlyCreated ? ' (تم إنشاء حساب للعميل الجديد)' : '');

        return redirect()->route('reception.show', $lastRequest)->with('success', $msg);
    }

    public function show(ServiceRequest $serviceRequest)
    {
        $serviceRequest->load('customer', 'assignedTechnician:id,name,specialty', 'department:id,name', 'usedParts.inventoryItem', 'internalNotes.author', 'review');

        $technicians = User::where('role', 'TECHNICIAN')->where('is_active', true)
            ->get(['id', 'name', 'specialty']);

        $inventoryItems = InventoryItem::where('quantity', '>', 0)
            ->orderBy('name')->get(['id', 'name', 'brand', 'part_number', 'quantity', 'unit', 'sell_price']);

        return view('reception.show', compact('serviceRequest', 'technicians', 'inventoryItems'));
    }

    /**
     * تعديل الطلب — نفس منطق الأصل:
     * الأدمن أي حالة، الاستقبال للطلبات النشطة فقط (كشف/تواصل/تأكيد/تنفيذ/جاهز/مرتجع)
     */
    public function update(Request $request, ServiceRequest $serviceRequest)
    {
        abort_unless(Permissions::canEditRequest($request->user()->role, $serviceRequest->status), 403,
            'لا يمكنك تعديل طلب بحالة "'.$serviceRequest->statusLabel().'" — دي صلاحية الأدمن فقط');

        $data = $request->validate([
            'status' => 'required|in:PENDING,CONTACTED,CONFIRMED,IN_PROGRESS,READY,RETURNED,COMPLETED,CANCELLED',
            'price' => 'nullable|numeric|min:0',
            'warranty_months' => 'nullable|integer|min:0|max:36',
            'admin_notes' => 'nullable|string|max:2000',
            'assigned_technician_id' => 'nullable|exists:users,id',
            'payment_method' => 'nullable|in:cash,transfer',
            'urgency' => 'nullable|in:normal,urgent,emergency',
        ], [
            'status.required' => 'اختر الحالة',
        ]);

        $oldStatus = $serviceRequest->status;
        $technicianId = array_key_exists('assigned_technician_id', $data) ? $data['assigned_technician_id'] : $serviceRequest->assigned_technician_id;

        $update = [
            'status' => $data['status'],
            'price' => $data['price'] ?? $serviceRequest->price,
            // التحصيل من العملاء بكامل السعر — التسليم يحصّل الأجر كاملاً تلقائياً
            'paid_amount' => $data['status'] === 'COMPLETED' ? ($data['price'] ?? $serviceRequest->price) : $serviceRequest->paid_amount,
            'admin_notes' => $data['admin_notes'] ?? $serviceRequest->admin_notes,
            'assigned_technician_id' => $technicianId,
            'assigned_at' => $technicianId ? ($serviceRequest->assigned_at ?? now()) : null,
        ];

        // تغيير وضع الاستعجال؟ — مهلة تسليم جديدة على أساسه
        if (! empty($data['urgency']) && $data['urgency'] !== $serviceRequest->urgency) {
            $update['urgency'] = $data['urgency'];
            $update['expected_exit_at'] = ($serviceRequest->entered_at ?? now())->addHours(ServiceRequest::URGENCY_SLA_HOURS[$data['urgency']] ?? 168);
        }

        if ($data['status'] === 'COMPLETED' && $oldStatus !== 'COMPLETED') {
            // التسليم = تم التحصيل بالكامل — مع طريقة الدفع (كاش/تحويل)
            Noor::completeRequest($serviceRequest->fill($update), (int) ($data['warranty_months'] ?? 0), $data['payment_method'] ?? null);
        } else {
            // طريقة الدفع تتحفظ كمان لو اتحددت بدون إكمال
            if (! empty($data['payment_method'])) {
                $update['payment_method'] = $data['payment_method'];
            }
            $serviceRequest->update($update);
        }

        if ($data['status'] !== $oldStatus) {
            Noor::notifyUser(
                $serviceRequest->customer_id,
                'REQUEST_UPDATED',
                'تحديث حالة الطلب',
                "طلبك {$serviceRequest->order_number} أصبح: ".$serviceRequest->fresh()->statusLabel(),
                $serviceRequest->id
            );
        }

        if ($technicianId && $technicianId !== $serviceRequest->getOriginal('assigned_technician_id')) {
            Noor::notifyUser((int) $technicianId, 'TECHNICIAN_ASSIGNED', 'تعيين طلب جديد', "تم تعيين الطلب {$serviceRequest->order_number} ليك", $serviceRequest->id);
        }

        ActivityLog::record($request->user()->id, 'REQUEST_UPDATED', "تحديث الطلب {$serviceRequest->order_number} من ".(ServiceRequest::STATUS_LABELS[$oldStatus] ?? $oldStatus).' إلى '.$serviceRequest->fresh()->statusLabel());

        return back()->with('success', 'تم تحديث الطلب بنجاح');
    }

    /**
     * مرتجع للفني — العميل رجّع الجهاز والشركة هتصلحه تاني
     * ينذر الفني المخصص والأدمنز ويفتح مهلة تسليم جديدة
     */
    public function returnRequest(Request $request, ServiceRequest $serviceRequest)
    {
        abort_unless(Permissions::canEditRequest($request->user()->role, 'IN_PROGRESS'), 403);

        $data = $request->validate([
            'reason' => 'required|string|min:3|max:500',
        ], [
            'reason.required' => 'اكتب سبب الإرجاع',
            'reason.min' => 'اكتب سبب الإرجاع بالتفصيل',
        ]);

        if (in_array($serviceRequest->status, ['CANCELLED'], true)) {
            return back()->with('error', 'الطلب ملغي — مش ممكن يرجع للفني');
        }

        Noor::returnRequest($serviceRequest, $data['reason'], (int) $request->user()->id);

        ActivityLog::record($request->user()->id, 'REQUEST_RETURNED',
            "مرتجع للفني: الطلب {$serviceRequest->order_number} رجع من العميل — سبب: {$data['reason']}");

        return back()->with('success', "تم تسجيل مرتجع للفني — الطلب {$serviceRequest->order_number} رجع لقائمة الشغل بمهلة جديدة");
    }

    /** بيانات حية (JSON) — شريط حالة غرفة العمليات يتحدث كل ثواني */
    public function liveStats()
    {
        $today = today()->toDateString();

        $completedToday = ServiceRequest::where('status', 'COMPLETED')
            ->whereDate('completed_at', $today)->get(['price', 'payment_method']);

        return response()->json([
            'clock' => now()->format('H:i:s'),
            'date' => now()->translatedFormat('l j F'),
            'todayEntered' => ServiceRequest::whereDate('created_at', $today)->count(),
            'todayCompleted' => $completedToday->count(),
            'todayRevenue' => (float) $completedToday->sum(fn ($r) => (float) $r->price),
            'todayCash' => (float) $completedToday->where('payment_method', 'cash')->sum(fn ($r) => (float) $r->price),
            'todayTransfer' => (float) $completedToday->where('payment_method', 'transfer')->sum(fn ($r) => (float) $r->price),
            'todayReturned' => ServiceRequest::whereDate('returned_at', $today)->count(),
            'activeNow' => ServiceRequest::whereIn('status', ['CONTACTED', 'CONFIRMED', 'IN_PROGRESS', 'READY', 'RETURNED'])->count(),
            'alerts' => \App\Support\Alerts::liveSummary(),
        ]);
    }

    /** إيصال استلام للطباعة */
    public function receipt(ServiceRequest $serviceRequest)
    {
        $serviceRequest->load('customer:id,name,phone', 'department:id,name', 'usedParts.inventoryItem');

        return view('requests.receipt', compact('serviceRequest'));
    }

    /** بحث عميل بالهاتف (JSON — لتجنب تكرار الحسابات عند التسجيل) */
    public function lookup(Request $request)
    {
        $phone = trim((string) $request->query('phone', ''));

        if (! preg_match('/^01[0-9]{9}$/', $phone)) {
            return response()->json(['found' => false, 'customer' => null]);
        }

        $user = User::where('phone', $phone)->first();

        if (! $user) {
            return response()->json(['found' => false, 'customer' => null]);
        }

        // الرقم مسجل كطاقم (استقبال/فني/أدمن/مدير قسم) — مستحيل يبقى عميل: تحذير فوري
        if ($user->role !== User::ROLE_CUSTOMER) {
            return response()->json([
                'found' => false,
                'isStaff' => true,
                'staffRole' => $user->roleLabel(),
                'staffName' => $user->name,
                'customer' => null,
            ]);
        }

        return response()->json([
            'found' => true,
            'customer' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'requestsCount' => $user->requests()->count(),
                'since' => $user->created_at->format('Y-m-d'),
            ],
        ]);
    }

    // ============================================================
    // 2) تبويب المخزن (عرض + إضافة — مثل الأصل /api/inventory)
    // ============================================================

    public function inventory(Request $request)
    {
        $query = InventoryItem::with('department:id,name');

        if ($request->filled('category') && array_key_exists($request->category, InventoryItem::CATEGORY_LABELS)) {
            $query->where('category', $request->category);
        }

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('brand', 'like', "%{$q}%")
                    ->orWhere('part_number', 'like', "%{$q}%");
            });
        }

        $items = $query->orderBy('name')->paginate(20)->withQueryString();

        $stats = [
            'total' => InventoryItem::count(),
            'stockValue' => (float) \DB::table('inventory_items')->selectRaw('COALESCE(SUM(quantity * cost_price), 0) as v')->value('v'),
            'lowStock' => InventoryItem::whereColumn('quantity', '<=', 'min_quantity')->where('quantity', '>', 0)->count(),
            'outOfStock' => InventoryItem::where('quantity', '<=', 0)->count(),
        ];

        return view('reception.inventory', compact('items', 'stats'));
    }

    public function storeItem(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:parts,tools,consumables,other',
            'brand' => 'nullable|string|max:255',
            'part_number' => 'nullable|string|max:255',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'cost_price' => 'nullable|numeric|min:0',
            'sell_price' => 'nullable|numeric|min:0',
            'min_quantity' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'department_id' => 'nullable|exists:departments,id',
        ], [
            'name.required' => 'اكتب اسم الصنف',
            'category.required' => 'اختر التصنيف',
            'quantity.required' => 'اكتب الكمية',
        ]);

        InventoryItem::create($data);

        ActivityLog::record($request->user()->id, 'INVENTORY_ADDED', "استقبال أضاف صنف: {$data['name']} (كمية {$data['quantity']})");

        return back()->with('success', 'تم إضافة الصنف للمخزن');
    }

    // ============================================================
    // 3) تبويب العملاء (بحث + سجل + ملاحظات)
    // ============================================================

    public function customers(Request $request)
    {
        $query = User::where('role', User::ROLE_CUSTOMER)->withCount('requests');

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%");
            });
        }

        $customers = $query->withCount('requests')->orderByDesc('created_at')->paginate(20)->withQueryString();

        $stats = [
            'total' => User::where('role', User::ROLE_CUSTOMER)->count(),
            'newThisMonth' => User::where('role', User::ROLE_CUSTOMER)->whereMonth('created_at', now()->month)->count(),
        ];

        return view('reception.customers', compact('customers', 'stats'));
    }

    public function customerShow(User $user)
    {
        abort_unless($user->role === User::ROLE_CUSTOMER, 404);

        $user->loadCount('requests');
        $requests = $user->requests()->with('assignedTechnician:id,name', 'department:id,name')->latest()->paginate(10);
        $notes = $user->customerNotes()->with('author:id,name')->latest()->get();
        $completedCount = $user->requests()->where('status', 'COMPLETED')->count();

        return view('reception.customer-show', compact('user', 'requests', 'notes', 'completedCount'));
    }

    public function storeCustomerNote(Request $request, User $user)
    {
        abort_unless($user->role === User::ROLE_CUSTOMER, 404);

        $data = $request->validate([
            'content' => 'required|string|min:2|max:1000',
        ], [
            'content.required' => 'اكتب الملاحظة',
        ]);

        CustomerNote::create([
            'customer_id' => $user->id,
            'author_id' => $request->user()->id,
            'content' => $data['content'],
        ]);

        return back()->with('success', 'تمت إضافة ملاحظة عن العميل');
    }

    // ============================================================
    // 4) تبويب الإحصائيات (ملخص مالي اليومي + آخر 7 أيام + توزيع الحالات)
    // ============================================================

    public function stats()
    {
        $today = today()->toDateString();

        // ==== إيرادات اليوم: المكتمل = محصّل (كاش / تحويل) ====
        $completedToday = ServiceRequest::where('status', 'COMPLETED')
            ->whereDate('completed_at', $today)->get(['price', 'payment_method']);
        $todayRevenue = (float) $completedToday->sum(fn ($r) => (float) $r->price);
        $todayRevenueCount = $completedToday->count();
        $todayCash = (float) $completedToday->where('payment_method', 'cash')->sum(fn ($r) => (float) $r->price);
        $todayTransfer = (float) $completedToday->where('payment_method', 'transfer')->sum(fn ($r) => (float) $r->price);

        // مدفوعات الشركاء اليوم (صيانات مدفوعة + تسويات)
        $partnerRepairsToday = PartnerRepair::whereDate('date', $today)->get();
        $partnerSettlementsToday = PartnerSettlement::whereDate('date', $today)->get();
        $todayPartnerPaid = (float) $partnerRepairsToday->sum('paid_amount') + (float) $partnerSettlementsToday->sum('amount');

        // مصروفات اليوم
        $todayExpenses = (float) Expense::whereDate('date', $today)->sum('amount');
        $todayExpensesCount = Expense::whereDate('date', $today)->count();

        // الصافي = إيرادات الطلبات + مدفوعات الشركاء - المصروفات (زي الأصل بالظبط)
        $todayNet = ($todayRevenue + $todayPartnerPaid) - $todayExpenses;

        // ==== بيانات اليوم بيومه موزعة (حتى من دور الاستقبال) ====
        $todayByStatus = ServiceRequest::whereDate('created_at', $today)
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');
        $todayUrgency = ServiceRequest::whereDate('created_at', $today)
            ->selectRaw('urgency, COUNT(*) as c')->groupBy('urgency')->pluck('c', 'urgency');
        // مرتجعات اليوم (رجعت للفني النهاردة)
        $todayReturned = ServiceRequest::whereDate('returned_at', $today)->count();
        // المتأخر النهاردة (حسب مهلة الاستعجال — مش ميعاد الدخول/الخروج)
        $overdueToday = ServiceRequest::whereNotIn('status', ['COMPLETED', 'CANCELLED'])
            ->where('expected_exit_at', '<', now())->count();

        // إجماليات الطلبات
        $counts = ServiceRequest::selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');
        $overdue = $overdueToday;
        $todayRequestsCount = ServiceRequest::whereDate('created_at', $today)->count();

        // آخر 7 أيام: الوارد مقابل المكتمل + الإيراد اليومي
        $last7 = collect(range(6, 0))->map(function ($i) {
            $d = now()->subDays($i);
            $ds = $d->toDateString();

            return [
                'date' => $ds,
                'day' => ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'][$d->dayOfWeek],
                'count' => ServiceRequest::whereDate('created_at', $ds)->count(),
                'completed' => ServiceRequest::where('status', 'COMPLETED')->whereDate('completed_at', $ds)->count(),
                'returned' => ServiceRequest::whereDate('returned_at', $ds)->count(),
                'revenue' => (float) ServiceRequest::where('status', 'COMPLETED')->whereDate('completed_at', $ds)->sum('price'),
            ];
        });
        $maxCount = max($last7->max('count'), $last7->max('completed'), 1);
        $maxRevenue = max($last7->max('revenue'), 1);

        // سلاسل السباركلاين
        $revenueSeries = $last7->pluck('revenue')->values()->all();
        $enteredSeries = $last7->pluck('count')->values()->all();
        $completedSeries = $last7->pluck('completed')->values()->all();

        // توزيع أنواع الأجهزة (أعلى 6) — للدونات
        $deviceDist = ServiceRequest::selectRaw('device_type, COUNT(*) as c')
            ->groupBy('device_type')->orderByDesc('c')->limit(6)->pluck('c', 'device_type');

        // الإنذارات النشطة + سجل النشاط المباشر
        $alerts = \App\Support\Alerts::active();
        // سجل النشاط المباشر — آخر 50 حركة للاستقبال
        $activity = ActivityLog::with('user:id,name,role')->latest()->limit(50)->get();

        return view('reception.stats', [
            'todayRevenue' => $todayRevenue,
            'todayRevenueCount' => $todayRevenueCount,
            'todayCash' => $todayCash,
            'todayTransfer' => $todayTransfer,
            'todayPartnerPaid' => $todayPartnerPaid,
            'partnerRepairsCount' => $partnerRepairsToday->count(),
            'partnerSettlementsCount' => $partnerSettlementsToday->count(),
            'todayExpenses' => $todayExpenses,
            'todayExpensesCount' => $todayExpensesCount,
            'todayNet' => $todayNet,
            'total' => $counts->sum(),
            'todayRequestsCount' => $todayRequestsCount,
            'todayByStatus' => $todayByStatus,
            'todayUrgency' => $todayUrgency,
            'todayReturned' => $todayReturned,
            'todayPending' => $todayByStatus->get('PENDING', 0),
            'todayContacted' => $todayByStatus->get('CONTACTED', 0),
            'todayConfirmed' => $todayByStatus->get('CONFIRMED', 0),
            'todayInProgress' => $todayByStatus->get('IN_PROGRESS', 0),
            'todayReady' => $todayByStatus->get('READY', 0),
            'todayCompletedCount' => $todayByStatus->get('COMPLETED', 0),
            'completed' => $counts->get('COMPLETED', 0),
            'pending' => $counts->get('PENDING', 0),
            'contacted' => $counts->get('CONTACTED', 0),
            'confirmed' => $counts->get('CONFIRMED', 0),
            'inProgress' => $counts->get('IN_PROGRESS', 0),
            'ready' => $counts->get('READY', 0),
            'returnedTotal' => $counts->get('RETURNED', 0),
            'cancelled' => $counts->get('CANCELLED', 0),
            'overdue' => $overdue,
            'last7' => $last7,
            'maxCount' => $maxCount,
            'maxRevenue' => $maxRevenue,
            'revenueSeries' => $revenueSeries,
            'enteredSeries' => $enteredSeries,
            'completedSeries' => $completedSeries,
            'deviceDist' => $deviceDist,
            'alerts' => $alerts,
            'activity' => $activity,
        ]);
    }

    // ============================================================
    // 4.5) التقرير اليومي المفصل — كل ما حدث في اليوم المختار
    // وارد + تسليمات + حالات جديدة (تواصل/جاهز) + شركاء وتسوياتهم + مصروفات + سجل الحركة
    // ============================================================

    public function daily(Request $request)
    {
        // اليوم المختار (افتراضياً النهارده) — بيتمشي للأمس والتواريخ القديمة كمان
        $date = $request->filled('date') ? $request->input('date') : now()->toDateString();
        try { $day = \Carbon\Carbon::parse($date)->startOfDay(); } catch (\Throwable) { $day = now()->startOfDay(); }
        $dayKey = $day->toDateString();
        $dayEnd = $day->copy()->endOfDay();

        $isToday = $dayKey === now()->toDateString();
        $dayName = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'][$day->dayOfWeek];

        // ===== وارد اليوم =====
        $entered = ServiceRequest::with('customer:id,name,phone', 'assignedTechnician:id,name', 'department:id,name')
            ->whereDate('created_at', $dayKey)->orderBy('created_at')->get();

        // ===== تسليمات اليوم (المكتمل النهارده = محصّل كامل) =====
        $delivered = ServiceRequest::with('customer:id,name,phone', 'assignedTechnician:id,name', 'department:id,name', 'usedParts')
            ->where('status', 'COMPLETED')->whereDate('completed_at', $dayKey)->orderBy('completed_at')->get();

        $deliveredRevenue = (float) $delivered->sum(fn ($r) => (float) ($r->price ?? 0));
        $cashRevenue = (float) $delivered->where('payment_method', 'cash')->sum(fn ($r) => (float) ($r->price ?? 0));
        $transferRevenue = (float) $delivered->where('payment_method', 'transfer')->sum(fn ($r) => (float) ($r->price ?? 0));

        // ===== الحالات الجديدة النهارده: تواصل / جاهز (من سجل الحركة الفعلي) =====
        $contactedToday = ServiceRequest::with('customer:id,name,phone', 'assignedTechnician:id,name')
            ->whereIn('status', ['CONTACTED', 'CONFIRMED', 'IN_PROGRESS', 'READY', 'COMPLETED'])
            ->whereDate('updated_at', $dayKey)
            ->get()
            ->filter(function ($r) use ($dayKey) {
                // العدد الفعلي اللي اتحول «تواصل» النهارده — من سجل النشاط
                return ActivityLog::where('action', 'REQUEST_UPDATED')
                    ->whereDate('created_at', $dayKey)
                    ->where('details', 'like', '%'.$r->order_number.'%')
                    ->where('details', 'like', '%إلى تواصل%')
                    ->exists();
            })->values();

        $readyToday = ServiceRequest::with('customer:id,name,phone', 'assignedTechnician:id,name')
            ->whereIn('status', ['READY', 'COMPLETED'])
            ->whereDate('updated_at', $dayKey)
            ->get()
            ->filter(function ($r) use ($dayKey) {
                return ActivityLog::where('action', 'REQUEST_UPDATED')
                    ->whereDate('created_at', $dayKey)
                    ->where('details', 'like', '%'.$r->order_number.'%')
                    ->where('details', 'like', '%إلى جاهز%')
                    ->exists();
            })->values();

        // ===== مرتجعات وملغي اليوم =====
        $returnedToday = ServiceRequest::with('customer:id,name,phone')
            ->whereDate('returned_at', $dayKey)->orderBy('returned_at')->get();
        $cancelledToday = ServiceRequest::with('customer:id,name,phone')
            ->where('status', 'CANCELLED')->whereDate('updated_at', $dayKey)->get();

        // ===== شركاء الصيانة النهارده + تسوياتهم =====
        $partnerRepairs = PartnerRepair::with('partner:id,name')->whereDate('date', $dayKey)->orderBy('id')->get();
        $partnerSettlements = PartnerSettlement::with('partner:id,name')->whereDate('date', $dayKey)->orderBy('id')->get();
        $partnerPaid = (float) $partnerRepairs->sum('paid_amount') + (float) $partnerSettlements->sum('amount');
        $partnerUnpriced = $partnerRepairs->filter(fn ($r) => ! $r->hasPrice())->count();

        // ===== مصروفات اليوم =====
        $expenses = Expense::with('createdBy:id,name')->whereDate('date', $dayKey)->orderBy('id')->get();
        $expensesTotal = (float) $expenses->sum('amount');

        // ===== الإيراد الصافي: تحصيل العملاء + مدفوع الشركاء + تسوياتهم − المصروفات =====
        $netRevenue = $deliveredRevenue + $partnerPaid - $expensesTotal;

        // ===== سجل كل ما حدث النهارده (تايم لاين) =====
        $timeline = ActivityLog::with('user:id,name,role')
            ->whereBetween('created_at', [$day->copy()->format('Y-m-d H:i:s'), $dayEnd->copy()->format('Y-m-d H:i:s')])
            ->orderBy('created_at')->limit(300)->get();

        return view('reception.daily', [
            'dayKey' => $dayKey,
            'dayName' => $dayName,
            'isToday' => $isToday,
            'entered' => $entered,
            'delivered' => $delivered,
            'deliveredRevenue' => $deliveredRevenue,
            'cashRevenue' => $cashRevenue,
            'transferRevenue' => $transferRevenue,
            'contactedToday' => $contactedToday,
            'readyToday' => $readyToday,
            'returnedToday' => $returnedToday,
            'cancelledToday' => $cancelledToday,
            'partnerRepairs' => $partnerRepairs,
            'partnerSettlements' => $partnerSettlements,
            'partnerPaid' => $partnerPaid,
            'partnerUnpriced' => $partnerUnpriced,
            'expenses' => $expenses,
            'expensesTotal' => $expensesTotal,
            'netRevenue' => $netRevenue,
            'timeline' => $timeline,
        ]);
    }

    // ============================================================
    // 5) تبويب المصروفات (تسجيل + عرض + حذف)
    // ============================================================

    public function expenses(Request $request)
    {
        $query = Expense::with('createdBy:id,name');

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        } elseif ($request->filled('from') || $request->filled('to')) {
            $query->whereBetween('date', array_filter([$request->from, $request->to]) ?: [now()->toDateString(), now()->toDateString()]);
        }

        if ($request->filled('category') && array_key_exists($request->category, Expense::CATEGORY_LABELS)) {
            $query->where('category', $request->category);
        }

        $expenses = $query->orderByDesc('date')->orderByDesc('created_at')->paginate(20)->withQueryString();

        $today = today()->toDateString();
        $stats = [
            'today' => (float) Expense::whereDate('date', $today)->sum('amount'),
            'todayCount' => Expense::whereDate('date', $today)->count(),
            'month' => (float) Expense::whereMonth('date', now()->month)->whereYear('date', now()->year)->sum('amount'),
            'total' => (float) Expense::sum('amount'),
            'filtered' => (float) $expenses->sum('amount'),
        ];

        // آخر 7 أيام
        $last7 = collect(range(6, 0))->map(function ($i) {
            $d = now()->subDays($i);
            return [
                'date' => $d->toDateString(),
                'day' => ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'][$d->dayOfWeek],
                'total' => (float) Expense::whereDate('date', $d->toDateString())->sum('amount'),
            ];
        });

        return view('reception.expenses', compact('expenses', 'stats', 'last7'));
    }

    public function storeExpense(Request $request)
    {
        $data = $request->validate([
            'description' => 'required|string|min:2|max:500',
            'amount' => 'required|numeric|min:0.01',
            'category' => 'required|in:rent,utilities,salaries,supplies,transport,other',
            'date' => 'required|date',
        ], [
            'description.required' => 'اكتب وصف المصروف',
            'amount.required' => 'اكتب المبلغ',
            'category.required' => 'اختر الفئة',
        ]);

        $data['created_by_id'] = $request->user()->id;
        Expense::create($data);

        ActivityLog::record($request->user()->id, 'EXPENSE_ADDED', "تسجيل مصروف: {$data['description']} — {$data['amount']} ج.م");

        return back()->with('success', 'تم تسجيل المصروف');
    }

    public function destroyExpense(Request $request, Expense $expense)
    {
        $expense->delete();

        ActivityLog::record($request->user()->id, 'EXPENSE_DELETED', "حذف مصروف: {$expense->description}");

        return back()->with('success', 'تم حذف المصروف');
    }

    // ============================================================
    // 6) تبويب شركاء الصيانة (عرض + إضافة — التعديل والحذف للأدمن فقط زي الأصل)
    // ============================================================

    public function partners()
    {
        $partners = PartnerTechnician::with('createdBy:id,name')
            ->withCount(['repairs as repairs_count', 'settlements as settlements_count'])
            ->withMax(['repairs as last_repair_date' => fn ($q) => $q->latest('date')], 'date')
            ->orderBy('name')->paginate(12);

        // رصيد كل شريك
        foreach ($partners as $p) {
            $total = (float) $p->repairs()->sum('total_amount');
            $paid = (float) $p->repairs()->sum('paid_amount');
            $settled = (float) $p->settlements()->sum('amount');
            $p->total_amount = $total;
            $p->paid_amount = $paid;
            $p->settled_amount = $settled;
            $p->balance_amount = $total - $paid - $settled;
        }

        $stats = [
            'total' => PartnerTechnician::count(),
            'active' => PartnerTechnician::where('is_active', true)->count(),
            'totalRepairs' => PartnerRepair::count(),
            'totalDeferred' => (function () {
                $total = (float) PartnerRepair::sum('total_amount');
                $paid = (float) PartnerRepair::sum('paid_amount');
                $settled = (float) PartnerSettlement::sum('amount');
                return $total - $paid - $settled;
            })(),
        ];

        // آخر الصيانات — اللي ملهاش سعر بتظهر الأول عشان تتحدد
        $recentRepairs = PartnerRepair::with('partner:id,name')
            ->orderByRaw('total_amount IS NULL DESC')->latest('date')->limit(12)->get();
        $recentSettlements = PartnerSettlement::with('partner:id,name')->latest('date')->limit(10)->get();

        return view('reception.partners', compact('partners', 'stats', 'recentRepairs', 'recentSettlements'));
    }

    public function storePartner(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:255',
            'phone' => 'nullable|string|max:32',
            'notes' => 'nullable|string|max:1000',
            'credit_limit' => 'nullable|numeric|min:0',
        ], [
            'name.required' => 'اكتب اسم الشريك',
        ]);

        $data['created_by_id'] = $request->user()->id;
        $data['credit_limit'] = $data['credit_limit'] ?? 0;
        $data['is_active'] = true;

        PartnerTechnician::create($data);

        ActivityLog::record($request->user()->id, 'PARTNER_ADDED', "استقبال أضاف شريك: {$data['name']}");

        return back()->with('success', 'تم إضافة الشريك');
    }

    public function storePartnerRepair(Request $request, PartnerTechnician $partnerTechnician)
    {
        // أجهزة متعددة + سعر اختياري — الاستقبال بيستلم الأجهزة الأول والسعر يتحدد بعدين
        $data = $request->validate([
            'devices' => 'present|array|min:1|max:10',
            'devices.*.device_type' => 'required|string|max:255',
            'devices.*.brand' => 'nullable|string|max:255',
            'devices.*.customer_name' => 'nullable|string|max:255',
            'devices.*.issue_description' => 'nullable|string|max:1000',
            'devices.*.total_amount' => 'nullable|numeric|min:0',
            'devices.*.paid_amount' => 'nullable|numeric|min:0',
            'date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ], [
            'devices.required' => 'اكتب بيانات الأجهزة',
            'devices.*.device_type.required' => 'اكتب نوع الجهاز',
            'date.required' => 'اختر التاريخ',
        ]);

        $created = 0;
        $unpriced = 0;
        foreach ($data['devices'] as $dev) {
            $total = ($dev['total_amount'] ?? null) !== null && $dev['total_amount'] !== '' ? (float) $dev['total_amount'] : null;
            $paid = (float) ($dev['paid_amount'] ?? 0);

            if ($total !== null && $paid > $total) {
                return back()->with('error', 'المدفوع أكبر من الإجمالي في أحد الأجهزة');
            }

            if ($total !== null && $partnerTechnician->credit_limit > 0) {
                $newBalance = $partnerTechnician->balance() + ($total - $paid);
                if ($newBalance > $partnerTechnician->credit_limit) {
                    return back()->with('error', 'الحساب هيتجاوز حد الآجل ('.money($partnerTechnician->credit_limit).') — الرصيد الجديد هيبقى '.money($newBalance));
                }
            }

            $partnerTechnician->repairs()->create([
                'device_type' => $dev['device_type'],
                'brand' => $dev['brand'] ?? null,
                'customer_name' => $dev['customer_name'] ?? null,
                'issue_description' => $dev['issue_description'] ?? null,
                'total_amount' => $total,
                'paid_amount' => $paid,
                'date' => $data['date'],
                'notes' => $data['notes'] ?? null,
                'created_by_id' => $request->user()->id,
            ]);
            $created++;
            if ($total === null) {
                $unpriced++;
            }
        }

        ActivityLog::record($request->user()->id, 'PARTNER_REPAIR_ADDED', "استقبال سجّل {$created} صيانة للشريك {$partnerTechnician->name}".($unpriced ? " ({$unpriced} بدون سعر لحد الآن)" : ''));

        $msg = 'تم تسجيل '.$created.' صيانة للشريك';
        if ($unpriced > 0) {
            $msg .= " — {$unpriced} جهاز سعره لسه محددش (تقدر تضيفه من قائمة الصيانات في أي وقت)";
        }

        return back()->with('success', $msg);
    }

    /**
     * تحديد/تحديث سعر صيانة شريك بعدين — الاستقبال استلم الجهاز بدون سعر وحدده دلوقتي
     * (صلاحية تحديد السعر للجميع زي الإضافة — الحذف والتعديل الكامل للأدمن زي الأصل)
     */
    public function updatePartnerRepair(Request $request, PartnerRepair $partnerRepair)
    {
        $data = $request->validate([
            'total_amount' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ], [
            'total_amount.min' => 'السعر لازم يكون رقم موجب أو فاضي',
            'paid_amount.min' => 'المدفوع لازم يكون رقم موجب',
        ]);

        $total = ($data['total_amount'] ?? null) !== null && $data['total_amount'] !== '' ? (float) $data['total_amount'] : null;
        $paid = $data['paid_amount'] !== null && $data['paid_amount'] !== '' ? (float) $data['paid_amount'] : (float) $partnerRepair->paid_amount;

        if ($total !== null && $paid > $total) {
            return back()->with('error', 'المدفوع أكبر من الإجمالي');
        }

        $partner = $partnerRepair->partner;
        if ($total !== null && $partner->credit_limit > 0) {
            $newBalance = $partner->balance() - ($partnerRepair->hasPrice() ? $partnerRepair->deferredAmount() : 0) + ($total - $paid);
            if ($newBalance > $partner->credit_limit) {
                return back()->with('error', 'الحساب هيتجاوز حد الآجل ('.money($partner->credit_limit).') — الرصيد الجديد هيبقى '.money($newBalance));
            }
        }

        $wasUnpriced = ! $partnerRepair->hasPrice();
        $partnerRepair->update([
            'total_amount' => $total,
            'paid_amount' => $paid,
            'notes' => $data['notes'] ?? $partnerRepair->notes,
        ]);

        ActivityLog::record($request->user()->id, 'PARTNER_REPAIR_UPDATED', "استقبال حدد سعر صيانة شريك {$partner->name} ({$partnerRepair->device_type})".($wasUnpriced && $total !== null ? ': '.money($total) : ''));

        return back()->with('success', $wasUnpriced && $total !== null ? 'تم تحديد سعر الصيانة: '.money($total) : 'تم تحديث الصيانة');
    }

    public function storePartnerSettlement(Request $request, PartnerTechnician $partnerTechnician)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ], [
            'amount.required' => 'اكتب مبلغ التسوية',
            'date.required' => 'اختر التاريخ',
        ]);

        $balance = $partnerTechnician->balance();

        if ($data['amount'] > $balance + 0.001) {
            return back()->with('error', 'المبلغ أكبر من الرصيد المستحق ('.money($balance).')');
        }

        $data['created_by_id'] = $request->user()->id;
        $partnerTechnician->settlements()->create($data);

        ActivityLog::record($request->user()->id, 'PARTNER_SETTLEMENT_ADDED', "استقبال سجّل تسوية {$data['amount']} ج.م من الشريك {$partnerTechnician->name}");

        return back()->with('success', 'تم تسجيل التسوية — المتبقي: '.money($partnerTechnician->fresh()->balance()));
    }

    // ============================================================
    // 7) ملاحظات الطلب + قطع الغيار (من صفحة تفاصيل الطلب)
    // ============================================================

    public function storeNote(Request $request, ServiceRequest $serviceRequest)
    {
        $data = $request->validate([
            'content' => 'required|string|min:2|max:2000',
        ], [
            'content.required' => 'اكتب الملاحظة',
        ]);

        RequestNote::create([
            'request_id' => $serviceRequest->id,
            'author_id' => $request->user()->id,
            'content' => $data['content'],
        ]);

        return back()->with('success', 'تمت إضافة الملاحظة');
    }

    public function destroyNote(RequestNote $requestNote)
    {
        $requestNote->delete();

        return back()->with('success', 'تم حذف الملاحظة');
    }

    public function storePart(Request $request, ServiceRequest $serviceRequest)
    {
        $data = $request->validate([
            'inventory_item_id' => 'nullable|exists:inventory_items,id',
            'custom_name' => 'nullable|required_without:inventory_item_id|string|max:255',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
        ], [
            'quantity.required' => 'اكتب الكمية',
            'unit_price.required' => 'اكتب سعر الوحدة',
        ]);

        // خصم من المخزن لو صنف داخلي
        if (! empty($data['inventory_item_id'])) {
            $item = InventoryItem::findOrFail($data['inventory_item_id']);

            if ($item->quantity < $data['quantity']) {
                return back()->with('error', 'الكمية غير متوفرة في المخزن (المتاح: '.$item->quantity.')');
            }

            $item->decrement('quantity', $data['quantity']);

            if ($data['unit_price'] == 0) {
                $data['unit_price'] = $item->sell_price;
            }
        }

        UsedPart::create([
            'request_id' => $serviceRequest->id,
            'inventory_item_id' => $data['inventory_item_id'] ?? null,
            'custom_name' => $data['custom_name'] ?? null,
            'quantity' => $data['quantity'],
            'unit_price' => $data['unit_price'],
        ]);

        ActivityLog::record($request->user()->id, 'PART_ADDED', "إضافة قطعة للطلب {$serviceRequest->order_number}: ".($data['custom_name'] ?? InventoryItem::find($data['inventory_item_id'])?->name ?? '—'));

        return back()->with('success', 'تمت إضافة القطعة');
    }

    public function destroyPart(Request $request, UsedPart $usedPart)
    {
        // إرجاع الكمية للمخزن
        if ($usedPart->inventoryItem) {
            $usedPart->inventoryItem->increment('quantity', $usedPart->quantity);
        }

        $usedPart->delete();

        return back()->with('success', 'تم حذف القطعة وإرجاعها للمخزن');
    }

    // ============================================================
    // 8) إشعارات الطاقم (الاستقبال يشوف إشعاراته زي الأصل)
    // ============================================================

    public function notifications(Request $request)
    {
        $notifications = NoorNotification::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')->paginate(25);

        return view('reception.notifications', compact('notifications'));
    }

    public function markRead(Request $request)
    {
        NoorNotification::where('user_id', $request->user()->id)->where('is_read', false)->update(['is_read' => true]);

        return back()->with('success', 'تم تعليم كل الإشعارات كمقروءة');
    }
}
