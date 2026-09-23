{{-- شريط الحالة الحي — غرفة العمليات (ساعة + بيانات اليوم + إنذارات) --}}
{{-- يحدث تلقائياً كل 30 ثانية من /reception/live-stats --}}
@php
    $sevColor = \App\Support\Chart::sevColor(\App\Support\Alerts::severity());
    $alertsCount = \App\Support\Alerts::count();
    $liveStats = [
        'todayEntered' => $todayEntered ?? ($stats['todayEntered'] ?? 0),
        'todayCompleted' => $todayCompleted ?? ($stats['todayRevenueCount'] ?? 0),
        'todayRevenue' => $todayRevenue ?? ($stats['todayRevenue'] ?? 0),
        'todayReturned' => $todayReturned ?? ($stats['returned'] ?? 0),
    ];
@endphp
<div class="ops-strip" x-data="opsStrip({
        entered: {{ (int) $liveStats['todayEntered'] }},
        completed: {{ (int) $liveStats['todayCompleted'] }},
        revenue: {{ (float) $liveStats['todayRevenue'] }},
        returned: {{ (int) $liveStats['todayReturned'] }},
        alerts: {{ (int) $alertsCount }},
        sev: '{{ \App\Support\Alerts::severity() }}'
     })">
    <div class="flex flex-wrap items-center gap-3">

        {{-- الساعة الحية + نقطة البث --}}
        <div class="flex items-center gap-2.5 shrink-0 pl-1">
            <div class="flex flex-col items-center leading-none">
                <span id="ops-clock" class="ops-clock text-xl font-extrabold text-primary">--:--:--</span>
                <span class="text-[15px] font-extrabold text-success">Ahmed Gamal</span>
            </div>
            <div class="h-9 w-px bg-border"></div>
            <div class="flex items-center gap-2.5">
                <span class="live-dot"></span>
                <span class="text-[12px] font-extrabold text-success">بث حي</span>
            </div>
        </div>

        <div class="h-9 w-px bg-border/60 hidden sm:block"></div>

        {{-- بيانات اليوم الحية --}}
        <div class="flex flex-wrap items-center gap-2 flex-1 min-w-[280px]">
            <div class="ops-chip" title="أجهزة دخلت المركز النهاردة">
                <span class="text-primary">{!! icon('package-plus', 'h-4 w-4') !!}</span>
                <div>
                    <div class="val text-primary" x-text="entered"></div>
                    <span class="text-[10px] font-extrabold text-success">وارد اليوم</span>
                </div>
            </div>
            <div class="ops-chip" title="طلبات اكتملت واتحصلت النهاردة">
                <span class="text-green-600 dark:text-green-400">{!! icon('check-circle', 'h-4 w-4') !!}</span>
                <div>
                    <div class="val text-green-600 dark:text-green-400" x-text="completed"></div>
                    <span class="text-[10px] font-extrabold text-success">محصل اليوم</span>
                </div>
            </div>
            <div class="ops-chip" title="إيراد اليوم من المكتمل (المحصّل)">
                <span class="text-emerald-600 dark:text-emerald-400">{!! icon('dollar', 'h-4 w-4') !!}</span>
                <div>
                    <div class="val text-emerald-600 dark:text-emerald-400" dir="ltr" x-text="revenue.toLocaleString() + ' ج.م'"></div>
                    <span class="text-[10px] font-extrabold text-success">ايراد اليوم</span>
                </div>
            </div>
            <div class="ops-chip" title="مرتجعات النهاردة">
                <span class="text-orange-600 dark:text-orange-400">{!! icon('undo', 'h-4 w-4') !!}</span>
                <div>
                    <div class="val text-orange-600 dark:text-orange-400" x-text="returned"></div>
                    <span class="text-[10px] font-extrabold text-success"> مرتجعات اليوم</span>
                </div>
            </div>
        </div>

        {{-- إنذارات نشطة --}}
        <a href="{{ url('/reception?overdue=1') }}" class="shrink-0"
           :class="alerts > 0 ? 'alerts-badge pulse' : 'alerts-badge'"
           :style="'--sev:' + (sevColorMap[sev] || '#10b981')">
            <span x-show="alerts > 0" class="alert-sev-dot" style="display:none"></span>
            {!! icon('alert-triangle', 'h-4 w-4') !!}
            <span x-text="alerts > 0 ? alerts + ' إنذار نشط' : 'لا إنذارات'"></span>
        </a>
    </div>
</div>

@once
@push('scripts')
<script>
    function opsStrip(initial) {
        return {
            ...initial,
            sevColorMap: {
                critical: '#ef4444', high: '#f97316', medium: '#f59e0b', low: '#94a3b8', none: '#10b981'
            },
            async refresh() {
                try {
                    const res = await fetch('{{ route('reception.live-stats') }}', { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;
                    const d = await res.json();
                    this.entered = d.todayEntered;
                    this.completed = d.todayCompleted;
                    this.revenue = d.todayRevenue;
                    this.returned = d.todayReturned;
                    this.alerts = d.alerts.alertsCount || 0;
                    this.sev = d.alerts.severity || 'none';
                } catch (e) { /* silence */ }
            },
            init() { setInterval(() => this.refresh(), 30000); }
        };
    }
</script>
@endpush
@endonce
