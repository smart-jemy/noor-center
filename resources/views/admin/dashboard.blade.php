@extends('admin.layout')
@section('title', 'غرفة العمليات — لوحة التحكم')
@section('admin_title', 'غرفة العمليات الحية')
@section('admin_subtitle', 'مراقبة شاملة لحركة المركز اليوم — لحظة بلحظة')

@section('admin_content')

{{-- ===== شريط الحالة الحي ===== --}}
@include('partials.ops-live-strip')

{{-- ===== KPI كبير مع عدّاد متحرك + اتجاه ===== --}}
@php
    $revenueTrend = $stats['yesterdayRevenue'] > 0
        ? round((($stats['todayRevenue'] - $stats['yesterdayRevenue']) / $stats['yesterdayRevenue']) * 100)
        : ($stats['todayRevenue'] > 0 ? 100 : 0);
@endphp
<div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-2.5 mb-4 stagger">

    <div class="ops-kpi" style="--kpi-accent: var(--color-primary)">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <div class="ops-kpi-label">إيراد اليوم</div>
                <div class="ops-kpi-value" data-count="{{ $stats['todayRevenue'] }}" data-decimals="0" data-suffix=" ج.م">0</div>
                <div class="ops-kpi-hint">
                    <span class="{{ $revenueTrend >= 0 ? 'trend-up' : 'trend-down' }}">{{ $revenueTrend >= 0 ? '↑' : '↓' }} {{ abs($revenueTrend) }}%</span>
                    عن امبارح ({{ money($stats['yesterdayRevenue']) }})
                </div>
            </div>
            <div class="ops-kpi-icon">{!! icon('dollar', 'h-5 w-5') !!}</div>
        </div>
        {{-- سباركلاين الإيراد آخر 7 أيام --}}
        <div class="mt-2">{!! \App\Support\Chart::sparkline($revenueSeries, '#10b981') !!}</div>
    </div>

    <div class="ops-kpi" style="--kpi-accent: var(--color-blue-500)">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <div class="ops-kpi-label">وارد اليوم</div>
                <div class="ops-kpi-value" data-count="{{ $stats['todayEntered'] }}">0</div>
                <div class="ops-kpi-hint">أجهزة دخلت المركز النهاردة</div>
            </div>
            <div class="ops-kpi-icon">{!! icon('package-plus', 'h-5 w-5') !!}</div>
        </div>
        <div class="mt-2">{!! \App\Support\Chart::sparkline($enteredSeries, '#3b82f6') !!}</div>
    </div>

    <div class="ops-kpi" style="--kpi-accent: var(--color-green-500)">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <div class="ops-kpi-label">محصّل اليوم</div>
                <div class="ops-kpi-value" data-count="{{ $stats['todayRevenueCount'] }}">0</div>
                <div class="ops-kpi-hint">
                    اكتملوا = اتحصلوا — كاش <b data-count="{{ $stats['todayCash'] }}" data-suffix=" ج.م">0</b>
                    · تحويل <b data-count="{{ $stats['todayTransfer'] }}" data-suffix=" ج.م">0</b>
                </div>
            </div>
            <div class="ops-kpi-icon">{!! icon('banknote', 'h-5 w-5') !!}</div>
        </div>
        <div class="mt-2">{!! \App\Support\Chart::sparkline($completedSeries, '#059669') !!}</div>
    </div>

    <a href="{{ route('reception.index', ['status' => 'RETURNED']) }}" class="ops-kpi" style="--kpi-accent: oklch(0.7 0.17 55)">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <div class="ops-kpi-label">مرتجع</div>
                <div class="ops-kpi-value" data-count="{{ $stats['returned'] }}">0</div>
                <div class="ops-kpi-hint">رجعوا من العميل — الشركة بتصلحهم تاني</div>
            </div>
            <div class="ops-kpi-icon">{!! icon('undo', 'h-5 w-5') !!}</div>
        </div>
    </a>

    <a href="{{ route('reception.index', ['overdue' => 1]) }}" class="ops-kpi {{ $stats['overdue'] > 0 ? 'selected' : '' }}" style="--kpi-accent: var(--color-red-500)">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <div class="ops-kpi-label">متأخر عن المهلة</div>
                <div class="ops-kpi-value" data-count="{{ $stats['overdue'] }}">0</div>
                <div class="ops-kpi-hint">حسب وضع الطلب: طوارئ 24س · مستعجل 48س · عادي 96س</div>
            </div>
            <div class="ops-kpi-icon">{!! icon('alert-triangle', 'h-5 w-5') !!}</div>
        </div>
    </a>

    <div class="ops-kpi {{ $stats['todayNetRevenue'] >= 0 ? '' : 'border-red-300' }}" style="--kpi-accent: {{ $stats['todayNetRevenue'] >= 0 ? 'var(--color-success)' : 'var(--color-destructive)' }}">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <div class="ops-kpi-label">صافي اليوم</div>
                <div class="ops-kpi-value" data-count="{{ $stats['todayNetRevenue'] }}" data-suffix=" ج.م">0</div>
                <div class="ops-kpi-hint">إيراد + شركاء {{ money($stats['todayPartnerPaid']) }} − مصروفات {{ money($stats['todayExpenses']) }}</div>
            </div>
            <div class="ops-kpi-icon">{!! icon('trending-up', 'h-5 w-5') !!}</div>
        </div>
    </div>
</div>

{{-- ===== خط سير الطلب (دورة الحياة) ===== --}}
<div class="card p-4 mb-4">
    <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
        <h2 class="font-extrabold text-sm flex items-center gap-2">{!! icon('activity', 'h-4 w-4 text-primary') !!} خط سير الطلب — دورة الحياة</h2>
        <span class="text-[10px] text-muted-foreground font-bold">الأرقام = الشغل الحالي في كل مرحلة</span>
    </div>
    <div class="pipeline">
        <a href="{{ route('reception.index', ['status' => 'PENDING']) }}" class="pipeline-stage" style="--stage-color: #f59e0b">
            <div class="flex items-center justify-between">
                <div class="stage-count" data-count="{{ $stats['pending'] }}">0</div>
                {!! icon('clipboard-list', 'h-4 w-4 opacity-50') !!}
            </div>
            <div class="stage-name">كشف</div>
            <div class="stage-sub">وصل المركز{{ $stats['unassignedPending'] > 0 ? ' · '.$stats['unassignedPending'].' بدون فني' : '' }}</div>
        </a>
        <div class="pipeline-arrow">{!! icon('arrow-left', 'h-4 w-4') !!}</div>
        <a href="{{ route('reception.index', ['status' => 'CONTACTED']) }}" class="pipeline-stage" style="--stage-color: #06b6d4">
            <div class="flex items-center justify-between">
                <div class="stage-count" data-count="{{ $stats['contacted'] ?? 0 }}">0</div>
                {!! icon('phone-call', 'h-4 w-4 opacity-50') !!}
            </div>
            <div class="stage-name">تواصل</div>
            <div class="stage-sub">تواصل مع العميل</div>
        </a>
        <div class="pipeline-arrow">{!! icon('arrow-left', 'h-4 w-4') !!}</div>
        <a href="{{ route('reception.index', ['status' => 'CONFIRMED']) }}" class="pipeline-stage" style="--stage-color: #3b82f6">
            <div class="flex items-center justify-between">
                <div class="stage-count" data-count="{{ $stats['confirmed'] }}">0</div>
                {!! icon('check', 'h-4 w-4 opacity-50') !!}
            </div>
            <div class="stage-name">تأكيد</div>
            <div class="stage-sub">اتعين له فني</div>
        </a>
        <div class="pipeline-arrow">{!! icon('arrow-left', 'h-4 w-4') !!}</div>
        <a href="{{ route('reception.index', ['status' => 'IN_PROGRESS']) }}" class="pipeline-stage" style="--stage-color: #8b5cf6">
            <div class="flex items-center justify-between">
                <div class="stage-count" data-count="{{ $stats['inProgress'] }}">0</div>
                {!! icon('wrench', 'h-4 w-4 opacity-50') !!}
            </div>
            <div class="stage-name">تنفيذ</div>
            <div class="stage-sub">الإصلاح شغال في المركز</div>
        </a>
        <div class="pipeline-arrow">{!! icon('arrow-left', 'h-4 w-4') !!}</div>
        <a href="{{ route('reception.index', ['status' => 'READY']) }}" class="pipeline-stage" style="--stage-color: #14b8a6">
            <div class="flex items-center justify-between">
                <div class="stage-count" data-count="{{ $stats['ready'] ?? 0 }}">0</div>
                {!! icon('package', 'h-4 w-4 opacity-50') !!}
            </div>
            <div class="stage-name">جاهز</div>
            <div class="stage-sub">خلص الإصلاح — للاستلام</div>
        </a>
        <div class="pipeline-arrow">{!! icon('arrow-left', 'h-4 w-4') !!}</div>
        <a href="{{ route('reception.index', ['status' => 'COMPLETED']) }}" class="pipeline-stage" style="--stage-color: #10b981">
            <div class="flex items-center justify-between">
                <div class="stage-count" data-count="{{ $stats['completed'] }}">0</div>
                {!! icon('check-circle', 'h-4 w-4 opacity-50') !!}
            </div>
            <div class="stage-name">تسليم / محصّل</div>
            <div class="stage-sub">خرج من المركز بالتحصيل الكامل</div>
        </a>
    </div>
    {{-- المرتجع حالة موازية — بيرجع من التسليم لإعادة الإصلاح --}}
    <div class="mt-3 flex items-center justify-center gap-2 text-[10px] font-bold text-muted-foreground">
        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-orange-100 dark:bg-orange-950/50 text-orange-700 dark:text-orange-300 border border-orange-200 dark:border-orange-800">
            {!! icon('undo', 'h-3 w-3') !!} مرتجع: {{ $stats['returned'] }} طلب بيرجع من التسليم لمرحلة التنفيذ تاني
        </span>
        <a href="{{ route('reception.index', ['status' => 'RETURNED']) }}" class="text-primary hover:underline">عرض المرتجعات</a>
    </div>
</div>

{{-- ===== صف الرسوم: حركة الأسبوع + توزيع الأجهزة ===== --}}
<div class="grid lg:grid-cols-3 gap-4 mb-4">

    {{-- حركة الطلبات الأسبوعية: وارد مقابل مكتمل --}}
    <div class="lg:col-span-2 card p-5">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <div>
                <h2 class="font-extrabold text-sm flex items-center gap-2">{!! icon('bar-chart', 'h-4 w-4 text-primary') !!} حركة الطلبات الأسبوعية</h2>
                <p class="text-[10px] text-muted-foreground mt-0.5">مقارنة الوارد بالمسلَّم + الإيراد اليومي</p>
            </div>
            <div class="flex items-center gap-3 text-[10px] font-bold text-muted-foreground">
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-blue-500"></span> وارد</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-green-500"></span> تسليم</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-primary/60"></span> إيراد</span>
            </div>
        </div>
        @php $maxWeekly = max($last7->max('requests'), $last7->max('completedCount'), 1); @endphp
        <div class="flex items-end gap-2 h-44" dir="ltr">
            @foreach ($last7 as $day)
                <div class="flex-1 flex flex-col items-center gap-1.5 group">
                    <div class="text-[10px] font-extrabold text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity">
                        {{ money($day['revenue']) }}
                    </div>
                    <div class="w-full flex items-end justify-center gap-1 h-32">
                        <div class="w-[38%] max-w-5 rounded-t-lg bg-blue-500/80 transition-all duration-500 group-hover:bg-blue-500"
                             style="height: {{ max(2, ($day['requests'] / $maxWeekly) * 100) }}%"
                             title="وارد: {{ $day['requests'] }}"></div>
                        <div class="w-[38%] max-w-5 rounded-t-lg bg-green-500/80 transition-all duration-500 group-hover:bg-green-500"
                             style="height: {{ max(2, ($day['completedCount'] / $maxWeekly) * 100) }}%"
                             title="تسليم: {{ $day['completedCount'] }}"></div>
                    </div>
                    <span class="text-[10px] font-bold text-muted-foreground">{{ ['Sun' => 'أحد', 'Mon' => 'اثنين', 'Tue' => 'ثلاثاء', 'Wed' => 'أربعاء', 'Thu' => 'خميس', 'Fri' => 'جمعة', 'Sat' => 'سبت'][$day['label']] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- توزيع الأجهزة (دونات) --}}
    <div class="card p-5">
        <h2 class="font-extrabold text-sm mb-1 flex items-center gap-2">{!! icon('pie-chart', 'h-4 w-4 text-primary') !!} توزيع الأجهزة</h2>
        <p class="text-[10px] text-muted-foreground mb-3">أكثر 6 أنواع أجهزة في الطلبات</p>
        @php
            $totalDevices = $deviceDist->sum();
            $donutSegs = $deviceDist->map(fn ($c, $name) => [
                'label' => $name, 'value' => $c, 'color' => \App\Support\Chart::PALETTE[$deviceDist->keys()->search($name) % 6]
            ])->values()->all();
        @endphp
        <div class="flex items-center gap-4 flex-wrap justify-center">
            <div class="relative shrink-0">
                {!! \App\Support\Chart::donut($donutSegs, 150, 20, number_format($totalDevices), 'جهاز') !!}
            </div>
            <div class="space-y-1.5 min-w-[110px] flex-1">
                @foreach ($donutSegs as $i => $seg)
                    <div class="flex items-center justify-between gap-2 text-xs">
                        <span class="flex items-center gap-1.5 min-w-0">
                            <span class="donut-legend-dot" style="background: {{ $seg['color'] }}"></span>
                            <span class="truncate font-bold">{{ $seg['label'] }}</span>
                        </span>
                        <b class="shrink-0" dir="ltr">{{ $seg['value'] }}</b>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ===== الإنذارات + النشاط المباشر + الفنيون الآن ===== --}}
<div class="grid lg:grid-cols-3 gap-4 mb-4">
    @include('partials.alerts-panel', ['alerts' => $alerts])
    @include('partials.activity-feed', ['activity' => $activity])

    {{-- الفنيون الآن — عبء الشغل --}}
    <div class="card p-5 h-full flex flex-col">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-extrabold text-sm flex items-center gap-2">{!! icon('hard-hat', 'h-4 w-4 text-primary') !!} الفنيون الآن</h2>
            <span class="text-[10px] font-bold text-muted-foreground">{{ $techniciansNow->count() }} نشط</span>
        </div>
        @if ($techniciansNow->isEmpty())
            <p class="text-sm text-muted-foreground text-center py-8 flex-1">مفيش فنيين نشطين</p>
        @else
            <div class="space-y-3 overflow-y-auto flex-1 max-h-[340px]">
                @foreach ($techniciansNow as $t)
                    @php $load = min(100, $t->active_jobs * 25); @endphp
                    <div>
                        <div class="flex items-center justify-between mb-1 gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary text-xs font-extrabold shrink-0">
                                    {{ mb_substr($t->name, 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-extrabold truncate">{{ $t->name }}</p>
                                    <p class="text-[9px] text-muted-foreground truncate">{{ $t->specialty ?: ($t->department?->name ?: 'فني صيانة') }}</p>
                                </div>
                            </div>
                            <span class="text-xs font-extrabold shrink-0 {{ $t->active_jobs > 0 ? 'text-primary' : 'text-muted-foreground' }}">{{ $t->active_jobs }}</span>
                        </div>
                        <div class="tech-load-bar">
                            <div style="width: {{ max(4, $load) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- ===== المالية اليوم + إجماليات ===== --}}
<div class="grid md:grid-cols-4 gap-3 mb-4">
    <div class="card p-4">
        <div class="text-[10px] text-muted-foreground font-bold mb-1 flex items-center gap-1">{!! icon('banknote', 'h-3.5 w-3.5 text-green-600') !!} كاش اليوم</div>
        <div class="text-2xl font-extrabold text-green-600 dark:text-green-400" data-count="{{ $stats['todayCash'] }}" data-suffix=" ج.م">0</div>
        <div class="text-[10px] text-muted-foreground mt-0.5">من إجمالي {{ money($stats['todayRevenue']) }}</div>
    </div>
    <div class="card p-4">
        <div class="text-[10px] text-muted-foreground font-bold mb-1 flex items-center gap-1">{!! icon('credit-card', 'h-3.5 w-3.5 text-blue-600') !!} تحويل اليوم</div>
        <div class="text-2xl font-extrabold text-blue-600 dark:text-blue-400" data-count="{{ $stats['todayTransfer'] }}" data-suffix=" ج.م">0</div>
        <div class="text-[10px] text-muted-foreground mt-0.5">{{ $stats['todayRevenueCount'] }} طلب اكتمل واتحصل</div>
    </div>
    <div class="card p-4">
        <div class="text-[10px] text-muted-foreground font-bold mb-1 flex items-center gap-1">{!! icon('handshake', 'h-3.5 w-3.5 text-primary') !!} مدفوعات الشركاء</div>
        <div class="text-2xl font-extrabold text-primary" data-count="{{ $stats['todayPartnerPaid'] }}" data-suffix=" ج.م">0</div>
        <div class="text-[10px] text-muted-foreground mt-0.5">{{ $partnerRepairsToday }} صيانة + {{ $partnerSettlementsToday }} تسوية</div>
    </div>
    <div class="card p-4">
        <div class="text-[10px] text-muted-foreground font-bold mb-1 flex items-center gap-1">{!! icon('wallet', 'h-3.5 w-3.5 text-red-600') !!} مصروفات اليوم</div>
        <div class="text-2xl font-extrabold text-red-600 dark:text-red-400" data-count="{{ $stats['todayExpenses'] }}" data-suffix=" ج.م">0</div>
        <div class="text-[10px] text-muted-foreground mt-0.5">{{ $stats['todayExpensesCount'] }} مصروف مسجّل</div>
    </div>
</div>

{{-- ===== إجماليات سريعة + آخر الطلبات ===== --}}
<div class="grid lg:grid-cols-3 gap-4">
    {{-- معلومات سريعة --}}
    <div class="card p-5 bg-gradient-to-br from-green-50 dark:from-green-950/30 to-transparent border-green-200 dark:border-green-900">
        <div class="text-[10px] text-muted-foreground font-bold mb-1">{!! icon('dollar', 'h-3.5 w-3.5 text-green-600') !!} إجمالي الإيرادات (كل المسلَّم)</div>
        <div class="text-3xl font-extrabold text-green-700 dark:text-green-400" data-count="{{ $stats['totalCompletedRevenue'] }}" data-suffix=" ج.م">0</div>
        <div class="text-[10px] text-muted-foreground mt-2 space-y-1">
            <p>من {{ number_format($stats['completed']) }} طلب تم تسليمه</p>
            <p>متوسط {{ money($stats['completed'] > 0 ? $stats['totalCompletedRevenue'] / $stats['completed'] : 0) }} / طلب</p>
            <p>{{ $stats['totalCustomers'] }} عميل · {{ $stats['activeTechnicians'] }}/{{ $stats['totalTechnicians'] }} فني نشط</p>
        </div>
    </div>

    {{-- آخر الطلبات --}}
    <div class="lg:col-span-2 card p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-extrabold text-sm flex items-center gap-2">{!! icon('clock', 'h-4 w-4 text-primary') !!} آخر الطلبات</h2>
            <a href="{{ route('reception.index') }}" class="text-xs text-primary font-bold hover:underline">شوف الكل ←</a>
        </div>
        <div class="overflow-x-auto">
            <table class="table-noor min-w-[560px]">
                <thead>
                    <tr>
                        <th>رقم الطلب</th>
                        <th>العميل</th>
                        <th>الجهاز</th>
                        <th>الوضع</th>
                        <th>الحالة</th>
                        <th>منذ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentRequests as $r)
                        <tr>
                            <td><a href="{{ route('reception.show', $r) }}" class="text-primary font-bold hover:underline" dir="ltr">{{ $r->order_number }}</a></td>
                            <td>
                                <div class="font-bold text-xs">{{ $r->customer->name }}</div>
                                <div class="text-[10px] text-muted-foreground" dir="ltr">{{ $r->customer->phone }}</div>
                            </td>
                            <td class="text-xs">{{ $r->device_type }}</td>
                            <td><span class="urgency-flag {{ $r->urgencyColor() }}">{{ $r->urgencyLabel() }}</span></td>
                            <td><span class="badge {{ $r->statusColor() }}">{{ $r->statusLabel() }}</span></td>
                            <td class="text-[10px] text-muted-foreground">{{ $r->timeAgo() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted-foreground py-6">لسه مفيش طلبات</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
