<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DeviceType;
use App\Models\Review;
use App\Models\ServiceRequest;
use App\Support\Noor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RequestController extends Controller
{
    public function create()
    {
        $deviceTypes = DeviceType::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('requests.create', compact('deviceTypes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'device_type' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'issue_description' => 'required|string|min:5|max:3000',
            'area' => 'nullable|in:6_october,pyramids_gardens',
            'address' => 'nullable|string|min:5|max:500',
            'phone' => ['required', 'string', 'regex:/^01[0125][0-9]{8}$/'],
            'urgency' => 'nullable|in:normal,urgent,emergency',
            'is_immediate' => 'nullable',
            'preferred_date' => 'nullable|date|after_or_equal:today',
            'preferred_time' => 'nullable|in:09:00-12:00,12:00-15:00,15:00-18:00,18:00-21:00,فوري',
            'photos' => 'nullable|array|max:3',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            'accessories' => 'nullable|array|max:10',
            'accessories.*' => 'string|max:100',
        ], [
            'device_type.required' => 'اختر نوع الجهاز',
            'issue_description.min' => 'اكتب وصف العطل (5 أحرف على الأقل)',
            'issue_description.required' => 'اكتب وصف العطل',
            'address.min' => 'اكتب العنوان التفصيلي',
            'phone.regex' => 'اكتب رقم موبايل صحيح (11 رقم يبدأ بـ 01)',
            'preferred_date.after_or_equal' => 'التاريخ لازم يكون النهاردة أو بعده',
            'photos.max' => 'أقصى 3 صور',
            'photos.*.max' => 'حجم الصورة أقصى 2MB',
        ]);

        // وضع الطلب (عادي/مستعجل/طوارئ) — الطوارئ = فوري بمعنى الأصل
        $urgency = $data['urgency'] ?? 'normal';
        $isImmediate = $urgency === 'emergency' || $request->boolean('is_immediate');

        // الميعاد والمنطقة اختياريين بالكامل
        if ($isImmediate) {
            $preferredDate = now()->toDateString();
            $preferredTime = ServiceRequest::IMMEDIATE;
        } elseif (! empty($data['preferred_date']) && ! empty($data['preferred_time'])) {
            $preferredDate = $data['preferred_date'];
            $preferredTime = $data['preferred_time'];
        } else {
            $preferredDate = now()->toDateString();
            $preferredTime = '';
        }

        // رفع الصور
        $paths = [];
        foreach ($request->file('photos', []) as $file) {
            $name = Str::uuid().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $name);
            $paths[] = '/uploads/'.$name;
        }

        // الملحقات المختارة تُدمج في وصف العطل (تحسين عن الأصل — كانت تُهمل)
        $description = $data['issue_description'];
        if (! empty($data['accessories'])) {
            $description .= "\n[ملحقات مع الجهاز: ".implode('، ', $data['accessories']).']';
        }

        $serviceRequest = ServiceRequest::create([
            'order_number' => Noor::orderNumber(),
            'customer_id' => $request->user()->id,
            'device_type' => $data['device_type'],
            'brand' => $data['brand'] ?? null,
            'issue_description' => $description,
            'area' => $data['area'] ?? '',
            'address' => $data['address'] ?? '',
            'phone' => $data['phone'],
            'preferred_date' => $preferredDate,
            'preferred_time' => $preferredTime,
            'photos' => $paths,
            'status' => 'PENDING',
            'urgency' => $urgency,
            // ميعاد الدخول + ميعاد الخروج المتوقع (حسب مهلة الاستعجال)
            'entered_at' => now(),
            'expected_exit_at' => now()->addHours(ServiceRequest::URGENCY_SLA_HOURS[$urgency] ?? 96),
        ]);

        // توجيه تلقائي للقسم المختص
        Noor::routeDepartment($serviceRequest);

        Noor::notifyAdmins(
            'NEW_REQUEST',
            'طلب صيانة جديد',
            $request->user()->name.' طلب صيانة لـ '.$data['device_type'].' ('.$serviceRequest->order_number.')',
            $serviceRequest->id
        );

        return redirect()->route('requests.show', $serviceRequest)
            ->with('success', 'تم إرسال طلبك بنجاح — رقم الطلب: '.$serviceRequest->order_number);
    }

    public function index(Request $request)
    {
        $query = $request->user()->requests()
            ->with('assignedTechnician:id,name,specialty', 'review', 'department:id,name');

        if ($request->filled('status') && in_array($request->status, ServiceRequest::STATUSES)) {
            $query->where('status', $request->status);
        }

        $requests = $query->latest()->paginate(10)->withQueryString();

        // إحصائيات سريعة أعلى الصفحة — زي الأصل
        $stats = [
            'total' => $request->user()->requests()->count(),
            'pending' => $request->user()->requests()->whereIn('status', ['PENDING', 'CONTACTED'])->count(),
            'inProgress' => $request->user()->requests()->whereIn('status', ['CONFIRMED', 'IN_PROGRESS', 'READY', 'RETURNED'])->count(),
            'completed' => $request->user()->requests()->where('status', 'COMPLETED')->count(),
        ];

        return view('requests.index', compact('requests', 'stats'));
    }

    public function show(ServiceRequest $serviceRequest)
    {
        abort_unless($serviceRequest->customer_id === auth()->id() || auth()->user()->isAdmin(), 403);

        $serviceRequest->load('assignedTechnician:id,name,specialty,phone', 'review', 'usedParts.inventoryItem', 'department:id,name');

        return view('requests.show', compact('serviceRequest'));
    }

    /** إيصال استلام جهاز (طباعة) */
    public function receipt(ServiceRequest $serviceRequest)
    {
        abort_unless($serviceRequest->customer_id === auth()->id() || auth()->user()->isAdmin(), 403);

        $serviceRequest->load('customer:id,name,phone', 'department:id,name');

        return view('requests.receipt', compact('serviceRequest'));
    }

    public function review(Request $request, ServiceRequest $serviceRequest)
    {
        abort_unless($serviceRequest->customer_id === $request->user()->id, 403);
        abort_unless($serviceRequest->status === 'COMPLETED', 403, 'التقييم متاح بعد استلام جهازك فقط');

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:3|max:2000',
        ], [
            'rating.required' => 'اختر عدد النجوم',
            'comment.min' => 'اكتب تعليق قصير على الأقل',
        ]);

        Review::updateOrCreate(
            ['request_id' => $serviceRequest->id],
            [
                'customer_id' => $request->user()->id,
                'rating' => $data['rating'],
                'comment' => $data['comment'],
                'status' => 'PENDING',
            ]
        );

        Noor::notifyAdmins(
            'NEW_REVIEW',
            'تقييم جديد',
            $request->user()->name.' قيّم الطلب '.$serviceRequest->order_number.' بـ '.$data['rating'].' نجوم',
            $serviceRequest->id
        );

        return back()->with('success', 'شكراً لتقييمك! هيظهر على الموقع بعد المراجعة');
    }
}
