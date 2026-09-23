@extends('layouts.app')
@section('title', 'الإحصائيات — غرفة العمليات اليومية')

@section('content')
<div class="container mx-auto max-w-6xl px-4 sm:px-6 py-8">
    <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
        <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary shrink-0">
                {!! icon('trending-up', 'h-6 w-6') !!}
            </div>
            <div>
                <h1 class="text-2xl font-extrabold mb-0.5 flex items-center gap-2">
                    غرفة العمليات اليومية
                    <span class="live-dot" title="تحديث كل 30 ثانية"></span>
                </h1>
                <p class="text-muted-foreground text-sm flex items-center gap-1.5">
                    {!! icon('calendar', 'h-3.5 w-3.5') !!} إحصائيات يوم {{ today()->translatedFormat('l j F Y') }} — بيانات اليوم بيومه موزعة
                </p>
            </div>
        </div>
        <a href="{{ route('reception.stats') }}" class="btn btn-outline btn-sm">{!! icon('refresh', 'h-3.5 w-3.5') !!} تحديث</a>
    </div>

    @include('reception.partials.tabs')

    {{-- ===== شريط الحالة الحي ===== --}}
    @include('partials.ops-live-strip', ['todayEntered' => $todayRequestsCount, 'todayCompleted' => $todayRevenueCount, 'todayRevenue' => $todayRevenue, 'todayReturned' => $todayReturned])

    {{-- ===== بيانات اليوم موزعة: الوارد حسب دورة الحياة الجديدة ===== --}}
    <h2 class="font-extrabold text-sm mb-2.5 flex items-center gap-2">{!! icon('activity', 'h-4 w-4 text-primary') !!} طلبات اليوم حسب الحالة <span class="badge bg-primary/10 text-primary border-primary/30">{{ $todayRequestsCount }} طلب واصل</span></h2>
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2.5 mb-4 stagger">
        <div class="ops-kpi" style="--kpi-accent: #3b82f6">
            <div class="ops-kpi-label">واصل النهاردة</div>
            <div class="ops-kpi-value" data-count="{{ $todayRequestsCount }}">0</div>
            <div class="ops-kpi-hint">كل حالات النهاردة</div>
        </div>
        <div class="ops-kpi" style="--kpi-accent: #f59e0b">
            <div class="ops-kpi-label">كشف</div>
            <div class="ops-kpi-value" data-count="{{ $todayPending }}">0</div>
            <div class="ops-kpi-hint">من وارد اليوم</div>
        </div>
        <div class="ops-kpi" style="--kpi-accent: #22d3ee">
            <div class="ops-kpi-label">تواصل</div>
            <div class="ops-kpi-value" data-count="{{ $todayContacted ?? 0 }}">0</div>
            <div class="ops-kpi-hint">في التواصل مع العميل</div>
        </div>
        <div class="ops-kpi" style="--kpi-accent: #06b6d4">
            <div class="ops-kpi-label">تأكيد</div>
            <div class="ops-kpi-value" data-count="{{ $todayConfirmed }}">0</div>
            <div class="ops-kpi-hint">اتعين لهم فنيين</div>
        </div>
        <div class="ops-kpi" style="--kpi-accent: #8b5cf6">
            <div class="ops-kpi-label">تنفيذ</div>
            <div class="ops-kpi-value" data-count="{{ $todayInProgress }}">0</div>
            <div class="ops-kpi-hint">شغل شغال دلوقتي</div>
        </div>
        <div class="ops-kpi" style="--kpi-accent: #14b8a6">
            <div class="ops-kpi-label">جاهز</div>
            <div class="ops-kpi-value" data-count="{{ $todayReady ?? 0 }}">0</div>
            <div class="ops-kpi-hint">في انتظار الاستلام</div>
        </div>
        <div class="ops-kpi" style="--kpi-accent: #10b981">
            <div class="ops-kpi-label">تسليم</div>
            <div class="ops-kpi-value" data-count="{{ $todayCompletedCount }}">0</div>
            <div class="ops-kpi-hint">خرجوا بالتحصيل الكامل</div>
        </div>
        <div class="ops-kpi" style="--kpi-accent: #f97316">
            <div class="ops-kpi-label">مرتجع النهاردة</div>
            <div class="ops-kpi-value" data-count="{{ $todayReturned }}">0</div>
            <div class="ops-kpi-hint">رجعوا للفني للإصلاح</div>
        </div>
    </div>

    {{-- ===== وضع طلبات اليوم (عادي/مستعجل/طوارئ) ===== --}}
    <div class="grid sm:grid-cols-3 gap-2.5 mb-4">
        @php
            $todayUrgencyAll = collect([
                ['key' => 'emergency', 'label' => 'طوارئ', 'color' => '#ef4444', 'sla' => 'مهلة 24 ساعة', 'icon' => 'alert-triangle'],
                ['key' => 'urgent', 'label' => 'مستعجل', 'color' => '#f59e0b', 'sla' => 'مهلة 48 ساعة', 'icon' => 'zap'],
                ['key' => 'normal', 'label' => 'عادي', 'color' => '#64748b', 'sla' => 'مهلة 7 أيام', 'icon' => 'clock'],
            ]);
        @endphp
        @foreach ($todayUrgencyAll as $u)
            <a href="{{ route('reception.index', ['urgency' => $u['key']]) }}" class="ops-kpi flex items-center justify-between"
               style="--kpi-accent: {{ $u['color'] }}">
                <div>
                    <div class="ops-kpi-label">طلبات {{ $u['label'] }} اليوم</div>
                    <div class="ops-kpi-value" data-count="{{ $todayUrgency->get($u['key'], 0) }}">0</div>
                    <div class="ops-kpi-hint">{{ $u['sla'] }} — المتأخر بيتحسب بيها</div>
                </div>
                <div class="ops-kpi-icon">{!! icon($u['icon'], 'h-5 w-5') !!}</div>
            </a>
        @endforeach
    </div>

    {{-- ===== المالية اليومية: كاش / تحويل / شركاء / مصروفات / صافي ===== --}}
    <h2 class="font-extrabold text-sm mb-2.5 flex items-center gap-2">{!! icon('dollar', 'h-4 w-4 text-primary') !!} مالية اليوم — التسليم = تحصيل كامل</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
        <div class="card p-4 border-green-200/60 dark:border-green-900/50 bg-green-50/30 dark:bg-green-950/20">
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-green-100 dark:bg-green-950/60 shrink-0">
                    {!! icon('banknote', 'h-4.5 w-4.5 text-green-600 dark:text-green-400') !!}
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-bold">كاش النهاردة</p>
                    <p class="text-lg font-extrabold text-green-700 dark:text-green-400" data-count="{{ $todayCash }}" data-suffix=" ج.م">0</p>
                </div>
            </div>
        </div>
        <div class="card p-4 border-blue-200/60 dark:border-blue-900/50 bg-blue-50/30 dark:bg-blue-950/20">
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-950/60 shrink-0">
                    {!! icon('credit-card', 'h-4.5 w-4.5 text-blue-600 dark:text-blue-400') !!}
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-bold">تحويل النهاردة</p>
                    <p class="text-lg font-extrabold text-blue-700 dark:text-blue-400" data-count="{{ $todayTransfer ?? 0 }}" data-suffix=" ج.م">0</p>
                </div>
            </div>
        </div>
        <div class="card p-4 border-cyan-200/60 dark:border-cyan-900/50 bg-cyan-50/30 dark:bg-cyan-950/20">
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-cyan-100 dark:bg-cyan-950/60 shrink-0">
                    {!! icon('handshake', 'h-4.5 w-4.5 text-cyan-600 dark:text-cyan-400') !!}
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-bold">مدفوعات الشركاء</p>
                    <p class="text-lg font-extrabold text-cyan-700 dark:text-cyan-400" data-count="{{ $todayPartnerPaid }}" data-suffix=" ج.م">0</p>
                    <p class="text-[9px] text-muted-foreground">{{ $partnerRepairsCount }} صيانة + {{ $partnerSettlementsCount }} تسوية</p>
                </div>
            </div>
        </div>
        <div class="card p-4 border-red-200/60 dark:border-red-900/50 bg-red-50/30 dark:bg-red-950/20">
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-100 dark:bg-red-950/60 shrink-0">
                    {!! icon('wallet', 'h-4.5 w-4.5 text-red-600 dark:text-red-400') !!}
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-bold">مصروفات النهاردة</p>
                    <p class="text-lg font-extrabold text-red-700 dark:text-red-400" data-count="{{ $todayExpenses }}" data-suffix=" ج.م">0</p>
                    <p class="text-[9px] text-muted-foreground">{{ $todayExpensesCount }} مصروف</p>
                </div>
            </div>
        </div>
        <div class="card p-4 border-2 {{ $todayNet >= 0 ? 'border-primary/40 bg-primary/5' : 'border-amber-300 bg-amber-50/50 dark:bg-amber-950/20' }}">
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg {{ $todayNet >= 0 ? 'bg-primary/10' : 'bg-amber-100 dark:bg-amber-950/60' }} shrink-0">
                    {!! icon('trending-up', 'h-4.5 w-4.5 '.($todayNet >= 0 ? 'text-primary' : 'text-amber-600')) !!}
                </div>
                <div class="min-w-0">
                    <p class="text-[11px] font-bold">صافي النهاردة</p>
                    <p class="text-lg font-extrabold {{ $todayNet >= 0 ? 'text-primary' : 'text-amber-700 dark:text-amber-400' }}" data-count="{{ $todayNet }}" data-suffix=" ج.م">0</p>
                    <p class="text-[9px] text-muted-foreground">كاش + تحويل + شركاء − مصروفات</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== الرسوم: حركة الأسبوع + التوزيعات ===== --}}
    <div class="grid lg:grid-cols-3 gap-4 mb-4">
        {{-- حركة الأسبوع: وارد مقابل مكتمل --}}
        <div class="lg:col-span-2 card p-5">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <h2 class="font-extrabold text-sm flex items-center gap-2">{!! icon('bar-chart', 'h-4 w-4 text-primary') !!} حركة آخر 7 أيام</h2>
                <div class="flex items-center gap-3 text-[10px] font-bold text-muted-foreground">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-blue-500"></span> وارد</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-green-500"></span> مكتمل</span>
                </div>
            </div>
            <div class="flex items-end gap-2 h-44" dir="ltr">
                @foreach ($last7 as $day)
                    <div class="flex-1 flex flex-col items-center gap-1.5 group">
                        <div class="text-[10px] font-extrabold text-muted-foreground">{{ money($day['revenue']) }}</div>
                        <div class="w-full flex items-end justify-center gap-1 h-32">
                            <div class="w-[38%] max-w-5 rounded-t-lg bg-blue-500/75 transition-all group-hover:bg-blue-500"
                                 style="height: {{ max(2, ($day['count'] / $maxCount) * 100) }}%" title="وارد: {{ $day['count'] }}"></div>
                            <div class="w-[38%] max-w-5 rounded-t-lg bg-green-500/75 transition-all group-hover:bg-green-500"
                                 style="height: {{ max(2, ($day['completed'] / $maxCount) * 100) }}%" title="مكتمل: {{ $day['completed'] }}"></div>
                        </div>
                        <span class="text-[10px] font-bold text-muted-foreground">{{ $day['day'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- سباركلاين الإيراد + توزيع الأجهزة --}}
        <div class="card p-5">
            <h2 class="font-extrabold text-sm mb-1 flex items-center gap-2">{!! icon('dollar', 'h-4 w-4 text-primary') !!} إيراد آخر 7 أيام</h2>
            <p class="text-[10px] text-muted-foreground mb-3">المكتمل = المحصّل يوم بيوم</p>
            <div class="rounded-xl border border-border/60 bg-muted/20 p-3">
                {!! \App\Support\Chart::sparkline($revenueSeries, '#10b981', 240, 64) !!}
                <div class="flex items-center justify-between mt-2 text-[10px] font-bold text-muted-foreground">
                    <span>أقل: {{ money(min($revenueSeries)) }}</span>
                    <span class="text-primary">النهاردة: {{ money($revenueSeries[6] ?? 0) }}</span>
                    <span>أعلى: {{ money(max($revenueSeries)) }}</span>
                </div>
            </div>

            <h3 class="font-extrabold text-xs mt-4 mb-2">{!! icon('pie-chart', 'h-3.5 w-3.5 text-primary') !!} توزيع الأجهزة (الأعلى)</h3>
            <div class="space-y-1.5">
                @foreach ($deviceDist as $name => $c)
                    @php $pct = $total > 0 ? ($c / $total) * 100 : 0; @endphp
                    <div class="flex items-center gap-2 text-[11px]">
                        <span class="truncate font-bold w-20">{{ $name }}</span>
                        <div class="flex-1 h-2 rounded-full bg-muted overflow-hidden">
                            <div class="h-full rounded-full bg-primary/70" style="width: {{ $pct }}%"></div>
                        </div>
                        <b class="shrink-0" dir="ltr">{{ $c }}</b>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ===== الإنذارات + النشاط المباشر ===== --}}
    <div class="grid lg:grid-cols-2 gap-4 mb-4">
        @include('partials.alerts-panel', ['alerts' => $alerts])
        @include('partials.activity-feed', ['activity' => $activity])
    </div>

    {{-- ===== إجماليات كل الفترات — دورة الحياة الجديدة ===== --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-2.5">
        <div class="card p-3.5 text-center card-hover">
            <div class="text-2xl font-extrabold text-primary">{{ number_format($total) }}</div>
            <div class="text-[10px] text-muted-foreground mt-0.5">إجمالي الطلبات</div>
        </div>
        <div class="card p-3.5 text-center card-hover">
            <div class="text-2xl font-extrabold text-amber-600">{{ number_format($pending) }}</div>
            <div class="text-[10px] text-muted-foreground mt-0.5">كشف</div>
        </div>
        <div class="card p-3.5 text-center card-hover">
            <div class="text-2xl font-extrabold text-cyan-600">{{ number_format($contacted ?? 0) }}</div>
            <div class="text-[10px] text-muted-foreground mt-0.5">تواصل</div>
        </div>
        <div class="card p-3.5 text-center card-hover">
            <div class="text-2xl font-extrabold text-blue-600">{{ number_format($confirmed) }}</div>
            <div class="text-[10px] text-muted-foreground mt-0.5">تأكيد</div>
        </div>
        <div class="card p-3.5 text-center card-hover">
            <div class="text-2xl font-extrabold text-purple-600">{{ number_format($inProgress) }}</div>
            <div class="text-[10px] text-muted-foreground mt-0.5">تنفيذ</div>
        </div>
        <div class="card p-3.5 text-center card-hover">
            <div class="text-2xl font-extrabold text-teal-600">{{ number_format($ready ?? 0) }}</div>
            <div class="text-[10px] text-muted-foreground mt-0.5">جاهز</div>
        </div>
        <div class="card p-3.5 text-center card-hover">
            <div class="text-2xl font-extrabold text-orange-600">{{ number_format($returnedTotal) }}</div>
            <div class="text-[10px] text-muted-foreground mt-0.5">مرتجع</div>
        </div>
        <div class="card p-3.5 text-center card-hover">
            <div class="text-2xl font-extrabold text-red-600">{{ number_format($overdue) }}</div>
            <div class="text-[10px] text-muted-foreground mt-0.5">متأخر عن المهلة</div>
        </div>
    </div>
</div>
@endsection
