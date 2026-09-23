<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\InventoryItem;
use App\Models\PartnerRepair;
use App\Models\PartnerSettlement;
use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        $completedToday = ServiceRequest::where('status', 'COMPLETED')
            ->whereDate('completed_at', $today)->get(['price', 'payment_method']);
        $todayRevenue = $completedToday->sum(fn ($r) => (float) $r->price);
        $todayCash = (float) $completedToday->where('payment_method', 'cash')->sum(fn ($r) => (float) $r->price);
        $todayTransfer = (float) $completedToday->where('payment_method', 'transfer')->sum(fn ($r) => (float) $r->price);

        // إيرادات امبارح للمقارنة (اتجاه ↑/↓)
        $yesterday = now()->subDay()->toDateString();
        $yesterdayRevenue = (float) ServiceRequest::where('status', 'COMPLETED')
            ->whereDate('completed_at', $yesterday)->sum('price');

        $todayExpenses = (float) Expense::where('date', $today)->sum('amount');
        $todayExpensesCount = Expense::where('date', $today)->count();

        $partnerPaid = (float) PartnerRepair::where('date', $today)->sum('paid_amount')
            + (float) PartnerSettlement::where('date', $today)->sum('amount');

        // المتأخر حسب مهلة الاستعجال (مش ميعاد الدخول/الخروج)
        $overdueCount = ServiceRequest::whereNotIn('status', ['COMPLETED', 'CANCELLED'])
            ->where('expected_exit_at', '<', now())->count();

        $stats = [
            'total' => ServiceRequest::count(),
            'pending' => ServiceRequest::where('status', 'PENDING')->count(),
            'contacted' => ServiceRequest::where('status', 'CONTACTED')->count(),
            'confirmed' => ServiceRequest::where('status', 'CONFIRMED')->count(),
            'inProgress' => ServiceRequest::where('status', 'IN_PROGRESS')->count(),
            'ready' => ServiceRequest::where('status', 'READY')->count(),
            'returned' => ServiceRequest::where('status', 'RETURNED')->count(),
            'completed' => ServiceRequest::where('status', 'COMPLETED')->count(),
            'cancelled' => ServiceRequest::where('status', 'CANCELLED')->count(),
            'todayEntered' => ServiceRequest::whereDate('created_at', $today)->count(),
            'totalCustomers' => User::where('role', 'CUSTOMER')->count(),
            'octoberCount' => ServiceRequest::where('area', '6_october')->count(),
            'gardensCount' => ServiceRequest::where('area', 'pyramids_gardens')->count(),
            'totalTechnicians' => User::where('role', 'TECHNICIAN')->count(),
            'activeTechnicians' => User::where('role', 'TECHNICIAN')->where('is_active', true)->count(),
            'totalReception' => User::where('role', 'RECEPTION')->count(),
            'unassignedPending' => ServiceRequest::where('status', 'PENDING')->whereNull('assigned_technician_id')->count(),
            'todayRevenue' => $todayRevenue,
            'todayRevenueCount' => $completedToday->count(),
            'todayCash' => $todayCash,
            'todayTransfer' => $todayTransfer,
            'yesterdayRevenue' => $yesterdayRevenue,
            'todayExpenses' => $todayExpenses,
            'todayExpensesCount' => $todayExpensesCount,
            'todayPartnerPaid' => $partnerPaid,
            'todayNetRevenue' => $todayRevenue + $partnerPaid - $todayExpenses,
            'lowStock' => InventoryItem::whereColumn('quantity', '<=', 'min_quantity')->count(),
            // إجمالي إيرادات المكتمل (كامل التاريخ) — زي الأصل
            'totalCompletedRevenue' => (float) ServiceRequest::where('status', 'COMPLETED')->sum('price'),
            'overdue' => $overdueCount,
        ];

        // مدفوعات الشركاء اليوم بالتفصيل — زي الأصل (عدد الصيانات + التسويات)
        $partnerRepairsToday = PartnerRepair::where('date', $today)->count();
        $partnerSettlementsToday = PartnerSettlement::where('date', $today)->count();

        $recentRequests = ServiceRequest::with('customer:id,name,phone', 'assignedTechnician:id,name')
            ->latest()->limit(6)->get();

        $pendingReviews = Review::where('status', 'PENDING')->count();

        // بيانات آخر 7 أيام للرسم البياني
        $last7 = collect(range(6, 0))->map(function ($i) {
            $day = now()->subDays($i);
            return [
                'label' => $day->format('D'),
                'date' => $day->toDateString(),
                'revenue' => (float) ServiceRequest::where('status', 'COMPLETED')->whereDate('completed_at', $day)->sum('price')
                    + (float) PartnerRepair::where('date', $day->toDateString())->sum('paid_amount')
                    + (float) PartnerSettlement::where('date', $day->toDateString())->sum('amount'),
                'expenses' => (float) Expense::where('date', $day->toDateString())->sum('amount'),
                'requests' => ServiceRequest::whereDate('created_at', $day)->count(),
                'completedCount' => ServiceRequest::where('status', 'COMPLETED')->whereDate('completed_at', $day)->count(),
            ];
        });

        // سلاسل السباركلاين (إيراد / وارد / مكتمل)
        $revenueSeries = $last7->pluck('revenue')->values()->all();
        $enteredSeries = $last7->pluck('requests')->values()->all();
        $completedSeries = $last7->pluck('completedCount')->values()->all();

        // توزيع أنواع الأجهزة — للدونات
        $deviceDist = ServiceRequest::selectRaw('device_type, COUNT(*) as c')
            ->groupBy('device_type')->orderByDesc('c')->limit(6)->pluck('c', 'device_type');

        // الفنيون الآن — عبء الشغل الحالي
        $techniciansNow = User::where('role', 'TECHNICIAN')->where('is_active', true)
            ->withCount([
                'assignedRequests as active_jobs' => fn ($q) => $q->whereIn('status', ['CONFIRMED', 'IN_PROGRESS', 'READY', 'RETURNED']),
            ])->with('department:id,name')->get(['id', 'name', 'specialty', 'department_id']);

        // الإنذارات النشطة + سجل النشاط المباشر
        $alerts = \App\Support\Alerts::active();
        // سجل النشاط المباشر — آخر 100 حركة للمدير العام
        $activity = ActivityLog::with('user:id,name,role')->latest()->limit(100)->get();

        return view('admin.dashboard', compact(
            'stats', 'recentRequests', 'pendingReviews', 'last7', 'partnerRepairsToday', 'partnerSettlementsToday',
            'revenueSeries', 'enteredSeries', 'completedSeries', 'deviceDist', 'techniciansNow', 'alerts', 'activity'
        ));
    }

    public function notifications(Request $request)
    {
        $notifications = $request->user()->notifications()
            ->with('request:id,order_number,device_type,status')
            ->latest()->paginate(20);

        return view('admin.notifications', compact('notifications'));
    }

    public function markRead(Request $request)
    {
        $request->user()->notifications()->where('is_read', false)->update(['is_read' => true]);

        return back()->with('success', 'تم تعليم الكل كمقروء');
    }

    public function activity()
    {
        $logs = ActivityLog::with('user:id,name,role')->latest()->paginate(30);

        return view('admin.activity', compact('logs'));
    }

    public function performance()
    {
        $technicians = User::where('role', 'TECHNICIAN')
            ->withCount([
                'assignedRequests as total_assigned',
                'assignedRequests as completed_count' => fn ($q) => $q->where('status', 'COMPLETED'),
                'assignedRequests as active_count' => fn ($q) => $q->whereIn('status', ['CONTACTED', 'CONFIRMED', 'IN_PROGRESS', 'READY', 'RETURNED']),
            ])
            ->with(['department:id,name'])
            ->get()
            ->map(function ($t) {
                $t->avg_rating = Review::where('status', 'APPROVED')
                    ->whereHas('request', fn ($q) => $q->where('assigned_technician_id', $t->id))
                    ->avg('rating') ?: 0;
                $t->revenue = (float) ServiceRequest::where('assigned_technician_id', $t->id)
                    ->where('status', 'COMPLETED')->sum('price');
                return $t;
            });

        return view('admin.performance', compact('technicians'));
    }

    public function heatmap()
    {
        $slots = ['9-12' => 'صباحاً (9-12)', '12-3' => 'ظهراً (12-3)', '3-6' => 'عصراً (3-6)', '6-9' => 'مساءً (6-9)'];
        $days = ['Sunday' => 'الأحد', 'Monday' => 'الاثنين', 'Tuesday' => 'الثلاثاء', 'Wednesday' => 'الأربعاء', 'Thursday' => 'الخميس', 'Friday' => 'الجمعة', 'Saturday' => 'السبت'];

        $grid = [];
        foreach ($days as $d) {
            foreach (array_keys($slots) as $s) $grid[$d][$s] = 0;
        }

        ServiceRequest::select('preferred_date', 'preferred_time')->chunk(500, function ($rows) use (&$grid, $days) {
            foreach ($rows as $r) {
                try {
                    $day = $days[Carbon::parse($r->preferred_date)->format('l')];
                    $start = explode('-', $r->preferred_time)[0] ?? '';
                    $hour = (int) substr($start, 0, 2);
                    $slot = $hour < 12 ? '9-12' : ($hour < 15 ? '12-3' : ($hour < 18 ? '3-6' : '6-9'));
                    $grid[$day][$slot]++;
                } catch (\Throwable $e) {}
            }
        });

        $max = 0;
        foreach ($grid as $row) $max = max($max, max($row));

        return view('admin.heatmap', compact('grid', 'slots', 'days', 'max'));
    }

    public function reminders()
    {
        $settings = \App\Models\Setting::current();
        $months = (int) $settings->maintenance_reminder_months ?: 6;

        if (! $settings->maintenance_reminder_enabled) {
            return view('admin.reminders', ['customers' => collect(), 'months' => $months, 'enabled' => false]);
        }

        $cutoff = now()->subMonths($months)->toDateString();

        $customers = User::where('role', 'CUSTOMER')
            ->whereHas('requests', fn ($q) => $q->where('status', 'COMPLETED'))
            ->with(['requests' => fn ($q) => $q->where('status', 'COMPLETED')->latest('completed_at')->limit(1)])
            ->get()
            ->filter(fn ($u) => $u->requests->isNotEmpty() && $u->requests->first()->completed_at && $u->requests->first()->completed_at->toDateString() < $cutoff)
            ->sortBy(fn ($u) => $u->requests->first()->completed_at)
            ->values();

        return view('admin.reminders', compact('customers', 'months') + ['enabled' => true]);
    }

    public function remindersUpdate(Request $request)
    {
        $data = $request->validate([
            'maintenance_reminder_enabled' => 'nullable|boolean',
            'maintenance_reminder_months' => 'required|integer|min:1|max:24',
        ], [
            'maintenance_reminder_months.required' => 'اكتب عدد الشهور',
            'maintenance_reminder_months.min' => 'عدد الشهور يجب أن يكون بين 1 و 24',
            'maintenance_reminder_months.max' => 'عدد الشهور يجب أن يكون بين 1 و 24',
        ]);

        $settings = \App\Models\Setting::current();
        $settings->update([
            'maintenance_reminder_enabled' => $request->boolean('maintenance_reminder_enabled'),
            'maintenance_reminder_months' => $data['maintenance_reminder_months'],
        ]);

        ActivityLog::record($request->user()->id, 'SETTINGS_UPDATED', 'حدّث إعدادات التذكير الدوري ('.($request->boolean('maintenance_reminder_enabled') ? 'مفعّل' : 'معطّل').' — كل '.$data['maintenance_reminder_months'].' شهر)');

        return back()->with('success', 'تم تحديث إعدادات التذكير');
    }

    public function backup()
    {
        $db = database_path('database.sqlite');

        if (! file_exists($db)) {
            return back()->with('error', 'قاعدة البيانات غير موجودة');
        }

        return response()->download($db, 'noor-backup-'.now()->format('Y-m-d-His').'.sqlite');
    }

    public function reset(Request $request)
    {
        $request->validate(['confirm' => 'required|in:RESET'], [
            'confirm.in' => 'اكتب RESET للتأكيد',
        ]);

        \DB::statement('PRAGMA foreign_keys = OFF');

        ServiceRequest::query()->delete();
        Review::query()->delete();
        \App\Models\NoorNotification::query()->delete();
        Expense::query()->delete();
        InventoryItem::query()->delete();
        \App\Models\UsedPart::query()->delete();
        PartnerRepair::query()->delete();
        PartnerSettlement::query()->delete();
        \App\Models\PartnerTechnician::query()->delete();
        \App\Models\CustomerNote::query()->delete();
        \App\Models\RequestNote::query()->delete();
        \App\Models\DeviceType::query()->delete();
        User::where('role', '!=', 'ADMIN')->delete();

        \DB::statement('PRAGMA foreign_keys = ON');

        ActivityLog::record($request->user()->id, 'RESET', 'تم تصفير بيانات النظام بالكامل');

        return back()->with('success', 'تم تصفير النظام — المتبقي حسابات الأدمن فقط');
    }

    public function account(Request $request)
    {
        return view('admin.account', ['user' => $request->user()]);
    }

    public function updateAccount(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'required|string|min:2|max:255',
            'phone' => ['required', 'string', 'regex:/^01[0-9]{9}$/', Rule::unique('users', 'phone')->ignore($user->id)],
            'password' => 'nullable|string|min:6|confirmed',
        ], [
            'phone.regex' => 'اكتب رقم موبايل صحيح',
            'phone.unique' => 'الرقم مستخدم بحساب آخر',
        ]);

        $user->update(['name' => $data['name'], 'phone' => $data['phone']]);

        if (! empty($data['password'])) {
            $user->update(['password' => $data['password']]);
        }

        ActivityLog::record($user->id, 'ACCOUNT_UPDATED', 'تحديث حساب الأدمن');

        return back()->with('success', 'تم تحديث الحساب بنجاح');
    }
}
