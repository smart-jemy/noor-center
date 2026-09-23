<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\InventoryItem;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Support\Noor;
use Illuminate\Http\Request;

/**
 * لوحة مدير القسم — 7 تبويبات مطابقة للأصل:
 * نظرة عامة | الطلبات | المبيعات | الفنيين | العملاء | المخزن | التقرير المالي
 *
 * مدير القسم يرى كل شيء مقيّداً بقسمه فقط.
 */
class DepartmentManagerController extends Controller
{
    private function dept(Request $request)
    {
        $dept = $request->user()->department;
        abort_unless($dept, 403, 'لم يتم تعيين قسم لك');

        return $dept;
    }

    // ============================================================
    // 1) نظرة عامة (Overview)
    // ============================================================

    public function index(Request $request)
    {
        $dept = $request->user()->department;

        if (! $dept) {
            return view('department.index', ['dept' => null]);
        }

        $today = today()->toDateString();

        $stats = [
            'pending' => ServiceRequest::where('department_id', $dept->id)->where('status', 'PENDING')->count(),
            'contacted' => ServiceRequest::where('department_id', $dept->id)->where('status', 'CONTACTED')->count(),
            'inProgress' => ServiceRequest::where('department_id', $dept->id)->where('status', 'IN_PROGRESS')->count(),
            'ready' => ServiceRequest::where('department_id', $dept->id)->where('status', 'READY')->count(),
            'completed' => ServiceRequest::where('department_id', $dept->id)->where('status', 'COMPLETED')->count(),
            'completedToday' => ServiceRequest::where('department_id', $dept->id)->where('status', 'COMPLETED')->whereDate('completed_at', $today)->count(),
            'overdue' => ServiceRequest::where('department_id', $dept->id)->whereNotIn('status', ['COMPLETED', 'CANCELLED'])->where('expected_exit_at', '<', now())->count(),
            'totalRevenue' => (float) ServiceRequest::where('department_id', $dept->id)->where('status', 'COMPLETED')->sum('price'),
            'techniciansCount' => User::where('managed_by', $request->user()->id)->where('role', 'TECHNICIAN')->count(),
            'customersCount' => ServiceRequest::where('department_id', $dept->id)->distinct('customer_id')->count('customer_id'),
            'inventoryValue' => (float) InventoryItem::where('department_id', $dept->id)->selectRaw('COALESCE(SUM(quantity * cost_price), 0)')->value('quantity * cost_price'),
        ];

        $stats['lowStock'] = InventoryItem::where('department_id', $dept->id)->whereColumn('quantity', '<=', 'min_quantity')->count();
        $stats['itemsCount'] = InventoryItem::where('department_id', $dept->id)->count();

        // مبيعات اليوم (من سجل النشاط — زي الأصل)
        $salesToday = $this->salesFromLogs($request->user()->id, $today, $today);
        $stats['salesToday'] = $salesToday->sum('total');

        // آخر 7 أيام إيرادات (صيانات مكتملة + مبيعات)
        $last7 = collect(range(6, 0))->map(function ($i) use ($dept, $request) {
            $d = now()->subDays($i);
            $repairs = (float) ServiceRequest::where('department_id', $dept->id)->where('status', 'COMPLETED')->whereDate('completed_at', $d->toDateString())->sum('price');
            $sales = $this->salesFromLogs($request->user()->id, $d->toDateString(), $d->toDateString())->sum('total');
            return [
                'date' => $d->toDateString(),
                'day' => ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'][$d->dayOfWeek],
                'total' => $repairs + $sales,
            ];
        });

        // أحدث طلبات القسم
        $recentRequests = ServiceRequest::where('department_id', $dept->id)
            ->with('customer:id,name', 'assignedTechnician:id,name')
            ->latest()->limit(6)->get();

        return view('department.index', compact('dept', 'stats', 'last7', 'recentRequests'));
    }

    /** قراءة المبيعات من سجل النشاط (زي الأصل: DEPARTMENT_SALE) */
    private function salesFromLogs(int $managerId, string $from, string $to)
    {
        return ActivityLog::where('user_id', $managerId)
            ->where('action', 'DEPARTMENT_SALE')
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($log) {
                // التنسيق: "بيع: itemName | qty x price = total | customer"
                $parts = collect(explode('|', $log->details))->map(fn ($p) => trim($p));
                $itemName = (string) str_replace('بيع: ', '', (string) $parts->get(0, ''));
                $qtyPrice = explode('x', (string) $parts->get(1, '0x0'));
                $total = 0;
                if (preg_match('/= (\d+(?:\.\d+)?)/', (string) $parts->get(1, ''), $m)) {
                    $total = (float) $m[1];
                }
                return [
                    'id' => $log->id,
                    'item' => $itemName,
                    'qty' => (float) ($qtyPrice[0] ?? 0),
                    'price' => (float) ($qtyPrice[1] ?? 0),
                    'total' => $total,
                    'customer' => (string) $parts->get(2, ''),
                    'created_at' => $log->created_at,
                ];
            });
    }

    // ============================================================
    // 2) الطلبات
    // ============================================================

    public function requests(Request $request)
    {
        $dept = $this->dept($request);

        $query = ServiceRequest::where('department_id', $dept->id)
            ->with('customer:id,name,phone', 'assignedTechnician:id,name', 'department:id,name');

        if ($request->filled('status') && in_array($request->status, ServiceRequest::STATUSES)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($w) use ($q) {
                $w->where('order_number', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('device_type', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$q}%"));
            });
        }

        $requests = $query->latest()->paginate(15)->withQueryString();

        return view('department.requests', compact('dept', 'requests'));
    }

    public function show(ServiceRequest $serviceRequest)
    {
        $dept = auth()->user()->department;
        abort_unless($dept && $serviceRequest->department_id === $dept->id, 403);

        $serviceRequest->load('customer', 'assignedTechnician:id,name,specialty', 'usedParts.inventoryItem', 'internalNotes.author', 'review');

        $technicians = User::where('role', 'TECHNICIAN')->where('is_active', true)
            ->where(function ($q) use ($dept) {
                $q->where('managed_by', auth()->id())->orWhereNull('managed_by');
            })->get(['id', 'name', 'specialty']);

        return view('department.show', compact('serviceRequest', 'technicians', 'dept'));
    }

    /** تسجيل طلب مباشرة لقسمه (زي الأصل /api/department/create-request) */
    public function storeRequest(Request $request)
    {
        $dept = $this->dept($request);

        $data = $request->validate([
            'customer_name' => 'required|string|min:2|max:255',
            'customer_phone' => ['required', 'string', 'regex:/^01[0125][0-9]{8}$/', function (string $attribute, mixed $value, \Closure $fail) {
                // رقم واحد لكل حساب — الرقم المسجل كطاقم مستحيل يبقى عميل
                $existing = \App\Models\User::where('phone', $value)->first();
                if ($existing && $existing->role !== User::ROLE_CUSTOMER) {
                    $fail('الرقم ده مسجل على النظام كحساب «'.$existing->roleLabel().'» — مينفعش يتسجل كعميل');
                }
            }],
            'device_type' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'issue_description' => 'required|string|min:5',
            'area' => 'required|in:6_october,pyramids_gardens',
            'address' => 'required|string|min:5',
            'phone' => ['required', 'string', 'regex:/^01[0125][0-9]{8}$/'],
            'preferred_date' => 'required|date',
            'preferred_time' => 'required|in:09:00-12:00,12:00-15:00,15:00-18:00,18:00-21:00,فوري',
        ], [
            'customer_name.required' => 'اكتب اسم العميل',
            'customer_phone.regex' => 'رقم موبايل العميل لازم 11 رقم يبدأ بـ 01',
            'device_type.required' => 'اختر نوع الجهاز',
            'issue_description.min' => 'اكتب وصف العطل',
        ]);

        $customer = User::firstOrCreate(
            ['phone' => $data['customer_phone']],
            ['name' => $data['customer_name'], 'role' => User::ROLE_CUSTOMER, 'password' => '123456']
        );

        $serviceRequest = ServiceRequest::create([
            'order_number' => Noor::orderNumber(),
            'customer_id' => $customer->id,
            'device_type' => $data['device_type'],
            'brand' => $data['brand'] ?? null,
            'issue_description' => $data['issue_description'],
            'area' => $data['area'],
            'address' => $data['address'],
            'phone' => $data['phone'],
            'preferred_date' => $data['preferred_date'],
            'preferred_time' => $data['preferred_time'],
            'photos' => [],
            'status' => 'PENDING',
            'department_id' => $dept->id, // طلب مباشر لقسمه
        ]);

        Noor::notifyAdmins(
            'NEW_REQUEST',
            'طلب جديد (مدير قسم)',
            $customer->name.' — '.$data['device_type'].' ('.$serviceRequest->order_number.') قسم '.$dept->name,
            $serviceRequest->id
        );

        ActivityLog::record($request->user()->id, 'REQUEST_UPDATED', "مدير قسم {$dept->name} سجّل طلب {$serviceRequest->order_number} للعميل {$customer->name}");

        return redirect()->route('department.show', $serviceRequest)
            ->with('success', 'تم إنشاء الطلب — رقمه: '.$serviceRequest->order_number);
    }

    // ============================================================
    // 3) المبيعات (بيع قطع من المخزن — تسجيل في سجل النشاط زي الأصل)
    // ============================================================

    public function sales(Request $request)
    {
        $dept = $this->dept($request);

        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());

        $sales = $this->salesFromLogs($request->user()->id, $from, $to);

        $today = today()->toDateString();
        $salesToday = $this->salesFromLogs($request->user()->id, $today, $today);

        $items = InventoryItem::where('department_id', $dept->id)->where('quantity', '>', 0)->orderBy('name')->get();

        return view('department.sales', [
            'dept' => $dept,
            'sales' => $sales,
            'from' => $from,
            'to' => $to,
            'total' => $sales->sum('total'),
            'count' => $sales->count(),
            'todayTotal' => $salesToday->sum('total'),
            'todayCount' => $salesToday->count(),
            'items' => $items,
        ]);
    }

    public function storeSale(Request $request)
    {
        $dept = $this->dept($request);

        $data = $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'customer_name' => 'nullable|string|max:255',
        ], [
            'inventory_item_id.required' => 'اختر الصنف من المخزن',
            'quantity.required' => 'اكتب الكمية',
            'unit_price.required' => 'اكتب سعر الوحدة',
        ]);

        $item = InventoryItem::where('department_id', $dept->id)->findOrFail($data['inventory_item_id']);

        if ($item->quantity < $data['quantity']) {
            return back()->with('error', 'الكمية غير متوفرة (المتاح: '.$item->quantity.' '.$item->unit.')');
        }

        $item->decrement('quantity', $data['quantity']);

        $total = round($data['quantity'] * $data['unit_price'], 2);
        $customer = trim((string) $data['customer_name']) ?: 'عميل نقدي';

        // تسجيل البيع في سجل النشاط بنفس تنسيق الأصل: "بيع: itemName | qty x price = total | customer"
        ActivityLog::record(
            $request->user()->id,
            'DEPARTMENT_SALE',
            "بيع: {$item->name} | {$data['quantity']} x {$data['unit_price']} = {$total} | {$customer}"
        );

        return back()->with('success', "تم تسجيل بيع {$item->name} بمبلغ ".money($total));
    }

    // ============================================================
    // 4) الفنيين (إدارة فنيو القسم)
    // ============================================================

    public function technicians(Request $request)
    {
        $dept = $this->dept($request);

        $technicians = User::where('managed_by', $request->user()->id)
            ->where('role', 'TECHNICIAN')
            ->withCount(['assignedRequests', 'reviews'])
            ->orderByDesc('created_at')->get();

        return view('department.technicians', compact('dept', 'technicians'));
    }

    public function storeTechnician(Request $request)
    {
        $this->dept($request);

        $data = $request->validate([
            'name' => 'required|string|min:2|max:255',
            'phone' => ['required', 'string', 'regex:/^01[0-9]{9}$/', 'unique:users,phone'],
            'password' => 'required|string|min:4',
            'specialty' => 'nullable|string|max:255',
        ], [
            'name.required' => 'اكتب اسم الفني',
            'phone.regex' => 'رقم الموبايل لازم 11 رقم يبدأ بـ 01',
            'phone.unique' => 'الرقم ده مسجل بحساب تاني خلاص',
            'password.required' => 'اكتب كلمة مرور للفني',
        ]);

        // زي الأصل: الفني يرتبط بالمدير (managed_by) بدون department_id
        User::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => $data['password'],
            'role' => User::ROLE_TECHNICIAN,
            'specialty' => $data['specialty'] ?? null,
            'managed_by' => $request->user()->id,
            'is_active' => true,
        ]);

        ActivityLog::record($request->user()->id, 'STAFF_CREATED', "مدير قسم أضاف فني: {$data['name']}");

        return back()->with('success', 'تم إضافة الفني لفريقك');
    }

    public function toggleTechnician(Request $request, User $user)
    {
        abort_unless($user->managed_by === $request->user()->id && $user->role === User::ROLE_TECHNICIAN, 403);

        $user->update(['is_active' => ! $user->is_active]);

        $state = $user->is_active ? 'تفعيل' : 'تعطيل';
        ActivityLog::record($request->user()->id, 'STAFF_UPDATED', "{$state} حساب الفني {$user->name}");

        return back()->with('success', "تم {$state} حساب الفني");
    }

    // ============================================================
    // 5) العملاء (عملاء القسم فقط)
    // ============================================================

    public function customers(Request $request)
    {
        $dept = $this->dept($request);

        $customerIds = ServiceRequest::where('department_id', $dept->id)->distinct()->pluck('customer_id');

        $query = User::where('role', User::ROLE_CUSTOMER)->whereIn('id', $customerIds)
            ->withCount(['requests as dept_requests_count' => fn ($q) => $q->where('department_id', $dept->id)]);

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%");
            });
        }

        $customers = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('department.customers', compact('dept', 'customers'));
    }

    // ============================================================
    // 6) المخزن
    // ============================================================

    public function inventory(Request $request)
    {
        $dept = $this->dept($request);

        $query = InventoryItem::where('department_id', $dept->id);

        if ($request->filled('category') && array_key_exists($request->category, InventoryItem::CATEGORY_LABELS)) {
            $query->where('category', $request->category);
        }

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")->orWhere('brand', 'like', "%{$q}%")->orWhere('part_number', 'like', "%{$q}%");
            });
        }

        $items = $query->orderBy('name')->paginate(20)->withQueryString();

        $stats = [
            'total' => InventoryItem::where('department_id', $dept->id)->count(),
            'stockValue' => (float) InventoryItem::where('department_id', $dept->id)->selectRaw('COALESCE(SUM(quantity * cost_price), 0)')->value('quantity * cost_price'),
            'lowStock' => InventoryItem::where('department_id', $dept->id)->whereColumn('quantity', '<=', 'min_quantity')->where('quantity', '>', 0)->count(),
            'outOfStock' => InventoryItem::where('department_id', $dept->id)->where('quantity', '<=', 0)->count(),
        ];

        return view('department.inventory', compact('dept', 'items', 'stats'));
    }

    public function storeItem(Request $request)
    {
        $dept = $this->dept($request);

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
        ], [
            'name.required' => 'اكتب اسم الصنف',
            'quantity.required' => 'اكتب الكمية',
        ]);

        $data['department_id'] = $dept->id;
        InventoryItem::create($data);

        ActivityLog::record($request->user()->id, 'INVENTORY_ADDED', "مدير قسم {$dept->name} أضاف صنف: {$data['name']}");

        return back()->with('success', 'تم إضافة الصنف لمخزن القسم');
    }

    public function updateItem(Request $request, InventoryItem $inventoryItem)
    {
        abort_unless($inventoryItem->department_id === $request->user()->department_id, 403);

        $data = $request->validate([
            'quantity' => 'required|numeric|min:0',
            'sell_price' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ], [
            'quantity.required' => 'اكتب الكمية',
        ]);

        $inventoryItem->update($data);

        ActivityLog::record($request->user()->id, 'INVENTORY_UPDATED', "تحديث صنف {$inventoryItem->name} (كمية: {$data['quantity']})");

        return back()->with('success', 'تم تحديث الصنف');
    }

    // ============================================================
    // 7) التقرير المالي (period: today / week / month)
    // ============================================================

    public function financial(Request $request)
    {
        $dept = $this->dept($request);

        $period = $request->input('period', 'month');
        $today = today()->toDateString();

        [$from, $label] = match ($period) {
            'today' => [$today, 'اليوم'],
            'week' => [now()->subDays(6)->toDateString(), 'آخر 7 أيام'],
            default => [now()->startOfMonth()->toDateString(), 'الشهر الحالي'],
        };

        // 1) إيراد الصيانات (طلبات قسمه المكتملة)
        $repairsRevenue = (float) ServiceRequest::where('department_id', $dept->id)
            ->where('status', 'COMPLETED')
            ->whereBetween('completed_at', [$from.' 00:00:00', $today.' 23:59:59'])
            ->sum('price');

        // 2) إيراد المبيعات (من سجل النشاط)
        $sales = $this->salesFromLogs($request->user()->id, $from, $today);
        $salesRevenue = (float) $sales->sum('total');

        $totalRevenue = $repairsRevenue + $salesRevenue;

        // توزيع يومي خلال الفترة
        $days = collect();
        $start = \Carbon\Carbon::parse($from);
        $end = \Carbon\Carbon::parse($today);
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $r = (float) ServiceRequest::where('department_id', $dept->id)->where('status', 'COMPLETED')->whereDate('completed_at', $d->toDateString())->sum('price');
            $s = (float) $this->salesFromLogs($request->user()->id, $d->toDateString(), $d->toDateString())->sum('total');
            $days->push(['date' => $d->toDateString(), 'day' => $d->format('j'), 'repairs' => $r, 'sales' => $s, 'total' => $r + $s]);
        }

        $maxDay = max($days->max('total'), 1);

        return view('department.financial', [
            'dept' => $dept,
            'period' => $period,
            'periodLabel' => $label,
            'from' => $from,
            'to' => $today,
            'repairsRevenue' => $repairsRevenue,
            'salesRevenue' => $salesRevenue,
            'salesCount' => $sales->count(),
            'totalRevenue' => $totalRevenue,
            'days' => $days,
            'maxDay' => $maxDay,
        ]);
    }

    // ============================================================
    // إشعارات مدير القسم
    // ============================================================

    public function notifications(Request $request)
    {
        $notifications = \App\Models\NoorNotification::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')->paginate(25);

        return view('department.notifications', compact('notifications'));
    }

    public function markRead(Request $request)
    {
        \App\Models\NoorNotification::where('user_id', $request->user()->id)->where('is_read', false)->update(['is_read' => true]);

        return back()->with('success', 'تم تعليم كل الإشعارات كمقروءة');
    }
}
