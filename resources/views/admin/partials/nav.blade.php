@php
    // تابات المدير العام 3D — لون مميز لكل تاب + نقطة نور للشغال + ظل بارز
    // الألوان oklch تعمل صح في الوضعين النهاري والليلي
    $adminNav = [
        ['route' => 'admin.dashboard', 'icon' => 'dashboard', 'label' => 'نظرة عامة', 'match' => 'admin.dashboard', 'color' => 'oklch(0.55 0.12 162)'],
        // سلوك مثبّت (مفيد وغير ضار): تاب الطلبات للأدمن يفتح تجربة الاستقبال الكاملة —
        // عرض طلبات الصيانة كاملة برؤية اكونت الاستقبال، والرجوع عبر تاب «لوحة التحكم»
        ['route' => 'reception.index', 'icon' => 'clipboard-list', 'label' => 'الطلبات', 'match' => 'reception.*', 'badge' => $navStats['unassignedPending'] ?? 0, 'color' => 'oklch(0.55 0.16 255)'],
        ['route' => 'admin.customers.index', 'icon' => 'users', 'label' => 'العملاء', 'match' => 'admin.customers.*', 'color' => 'oklch(0.55 0.18 295)'],
        ['route' => 'admin.departments.index', 'icon' => 'layers', 'label' => 'الأقسام', 'match' => 'admin.departments.*', 'color' => 'oklch(0.55 0.12 210)'],
        ['route' => 'admin.device-types.index', 'icon' => 'grid', 'label' => 'أنواع الأجهزة', 'match' => 'admin.device-types.*', 'color' => 'oklch(0.52 0.16 280)'],
        ['route' => 'admin.reminders', 'icon' => 'timer', 'label' => 'تذكير الصيانة', 'match' => 'admin.reminders', 'color' => 'oklch(0.68 0.15 75)'],
        ['route' => 'admin.users.index', 'icon' => 'user-cog', 'label' => 'الموظفون', 'match' => 'admin.users.*', 'color' => 'oklch(0.58 0.18 15)'],
        ['route' => 'admin.performance', 'icon' => 'chart', 'label' => 'أداء الفنيين', 'match' => 'admin.performance', 'color' => 'oklch(0.55 0.1 185)'],
        ['route' => 'admin.inventory.index', 'icon' => 'package', 'label' => 'المخزون', 'match' => 'admin.inventory.*', 'color' => 'oklch(0.65 0.16 55)'],
        ['route' => 'admin.expenses.index', 'icon' => 'wallet', 'label' => 'المصروفات', 'match' => 'admin.expenses.*', 'color' => 'oklch(0.58 0.17 30)'],
        ['route' => 'admin.partners.index', 'icon' => 'repeat', 'label' => 'شركاء الصيانة', 'match' => 'admin.partners.*', 'color' => 'oklch(0.58 0.2 320)'],
        ['route' => 'admin.financial.index', 'icon' => 'trending-up', 'label' => 'التقرير المالي', 'match' => 'admin.financial.*', 'color' => 'oklch(0.55 0.13 150)'],
        ['route' => 'admin.heatmap', 'icon' => 'activity', 'label' => 'أوقات الذروة', 'match' => 'admin.heatmap', 'color' => 'oklch(0.6 0.13 235)'],
        ['route' => 'admin.reviews.index', 'icon' => 'star', 'label' => 'التقييمات', 'match' => 'admin.reviews.*', 'badge' => $navStats['pendingReviews'] ?? 0, 'color' => 'oklch(0.72 0.15 90)'],
        ['route' => 'admin.notifications', 'icon' => 'bell', 'label' => 'الإشعارات', 'match' => 'admin.notifications', 'badge' => $unreadBadge, 'color' => 'oklch(0.55 0.19 27)'],
        ['route' => 'admin.activity', 'icon' => 'history', 'label' => 'سجل النشاط', 'match' => 'admin.activity', 'color' => 'oklch(0.5 0.05 260)'],
        ['route' => 'admin.settings.index', 'icon' => 'settings', 'label' => 'الإعدادات', 'match' => 'admin.settings.*', 'color' => 'oklch(0.5 0.08 310)'],
    ];
@endphp
<div class="nav3d-wrap">
    @foreach ($adminNav as $item)
        <a href="{{ route($item['route']) }}" style="--n: {{ $item['color'] }}"
           class="nav3d {{ request()->routeIs($item['match']) ? 'active' : '' }}">
            <span class="nav3d-ic shrink-0">{!! icon($item['icon'], 'h-4 w-4') !!}</span>
            <span class="truncate">{{ $item['label'] }}</span>
            {{-- نقطة النور — تظهر بس لما التاب يكون شغال --}}
            <span class="nav3d-dot" aria-hidden="true"></span>
            @if (($item['badge'] ?? 0) > 0)
                <span class="mr-auto flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white pulse-soft shadow-sm">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>
            @endif
        </a>
    @endforeach

    <div class="h-px bg-border/60 my-2"></div>

    <a href="{{ route('admin.account') }}" style="--n: oklch(0.55 0.12 162)"
       class="nav3d {{ request()->routeIs('admin.account') ? 'active' : '' }}">
        <span class="nav3d-ic shrink-0">{!! icon('user', 'h-4 w-4') !!}</span>
        <span class="truncate">حسابي</span>
        <span class="nav3d-dot" aria-hidden="true"></span>
    </a>
    <a href="{{ route('admin.backup') }}" style="--n: oklch(0.5 0.05 260)" class="nav3d">
        <span class="nav3d-ic shrink-0">{!! icon('download', 'h-4 w-4') !!}</span>
        <span class="truncate">نسخة احتياطية</span>
        <span class="nav3d-dot" aria-hidden="true"></span>
    </a>
    <a href="{{ route('admin.restore') }}" style="--n: oklch(0.55 0.16 195)"
       class="nav3d {{ request()->routeIs('admin.restore*') ? 'active' : '' }}">
        <span class="nav3d-ic shrink-0">{!! icon('database', 'h-4 w-4') !!}</span>
        <span class="truncate">استعادة بيانات</span>
        <span class="nav3d-dot" aria-hidden="true"></span>
    </a>
</div>
