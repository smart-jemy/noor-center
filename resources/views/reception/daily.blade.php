@extends('layouts.app')
@section('title', 'التقرير اليومي — استقبال')

@push('head')
<style>
    /* الطباعة — التقرير يطلع ورقة نظيفة بدون قوائم */
    @media print {
        .print-hide { display: none !important; }
        body { background: white !important; }
        .container { max-width: 100% !important; padding: 0 !important; }
        .card { break-inside: avoid; box-shadow: none !important; border: 1px solid #ddd !important; }
        details { break-inside: avoid; }
        .report-head { display: block !important; border-bottom: 3px double #333 !important; }
        main { padding-top: 0 !important; }
    }
    .daily-kpi { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 0.9rem 1rem; border-radius: 0.9rem; border: 1px solid var(--border); background: var(--card); }
    .daily-kpi .v { font-size: 1.35rem; font-weight: 800; line-height: 1.1; }
    .daily-kpi .l { font-size: 0.7rem; color: var(--muted-foreground, #777); margin-top: 2px; }
    details.order-box { border: 1px solid var(--border); border-radius: 0.85rem; background: var(--card); overflow: hidden; }
    details.order-box > summary { cursor: pointer; padding: 0.7rem 0.9rem; list-style: none; display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; }
    details.order-box > summary::-webkit-details-marker { display: none; }
    details.order-box[open] > summary { border-bottom: 1px solid var(--border); background: color-mix(in oklch, var(--primary) 4%, transparent); }
    .chip { display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.65rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 999px; }
</style>
@endpush

@section('content')
<div class="container mx-auto max-w-6xl px-4 sm:px-6 py-8">

    {{-- ===== الترويسة ===== --}}
    <div class="report-head flex items-center justify-between flex-wrap gap-3 mb-5">
        <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary shrink-0">
                {!! icon('calendar-check', 'h-6 w-6') !!}
            </div>
            <div>
                <h1 class="text-2xl font-extrabold mb-0.5">التقرير اليومي {{ $isToday ? '— النهارده' : '' }}</h1>
                <p class="text-muted-foreground text-sm">{{ $dayName }} {{ dt($dayKey) }} • كل ما حدث في المركز خلال اليوم بالتفصيل</p>
            </div>
        </div>
        <div class="flex items-center gap-2 print-hide">
            <form method="GET" action="{{ route('reception.daily') }}" class="flex items-center gap-2">
                <input type="date" name="date" value="{{ $dayKey }}" class="input h-10" onchange="this.form.submit()">
            </form>
            <a href="{{ route('reception.daily', ['date' => now()->subDay()->toDateString()]) }}" class="btn btn-outline btn-sm">أمس</a>
            <a href="{{ route('reception.daily') }}" class="btn btn-outline btn-sm">النهارده</a>
            <button onclick="window.print()" class="btn btn-primary btn-sm">{!! icon('printer', 'h-3.5 w-3.5') !!} طباعة التقرير</button>
        </div>
    </div>

    @include('reception.partials.tabs')

    {{-- ===== ملخص اليوم — KPI ===== --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2.5 mb-5">
        <div class="daily-kpi" style="border-inline-start: 3px solid var(--color-emerald-500)">
            <div><div class="v text-emerald-600 dark:text-emerald-400" >{{ $entered->count() }}</div><div class="l">وارد اليوم (أجهزة اتسجلت)</div></div>
        </div>
        <div class="daily-kpi" style="border-inline-start: 3px solid var(--color-green-600)">
            <div><div class="v">{{ $delivered->count() }}</div><div class="l">أوردرات خلصت وسلّمت (تحصيل كامل)</div></div>
        </div>
        <div class="daily-kpi" style="border-inline-start: 3px solid var(--color-cyan-500)">
            <div><div class="v">{{ $contactedToday->count() }}</div><div class="l">اتواصل معاهم النهارده (تواصل)</div></div>
        </div>
        <div class="daily-kpi" style="border-inline-start: 3px solid var(--color-teal-500)">
            <div><div class="v">{{ $readyToday->count() }}</div><div class="l">جهزت النهارده (جاهز للتسليم)</div></div>
        </div>
        <div class="daily-kpi" style="border-inline-start: 3px solid oklch(0.7 0.17 55)">
            <div><div class="v" style="color: oklch(0.7 0.17 55)">{{ $returnedToday->count() }}</div><div class="l">مرتجعات النهارده</div></div>
        </div>
        <div class="daily-kpi" style="border-inline-start: 3px solid var(--color-red-500)">
            <div><div class="v text-red-600 dark:text-red-400">{{ $cancelledToday->count() }}</div><div class="l">ملغي النهارده</div></div>
        </div>
    </div>

    {{-- ===== الإيراد المالي لليوم ===== --}}
    <div class="card p-5 mb-5">
        <h2 class="font-bold mb-4 flex items-center gap-2">{!! icon('banknote', 'h-4 w-4 text-primary') !!} الحساب المالي لليوم</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 text-center">
            <div class="p-3 rounded-xl bg-green-50 dark:bg-green-950/30">
                <div class="text-[10px] text-muted-foreground mb-1">تحصيل العملاء (كاش)</div>
                <div class="font-extrabold text-green-600 dark:text-green-400 text-lg">{{ money($cashRevenue) }}</div>
            </div>
            <div class="p-3 rounded-xl bg-blue-50 dark:bg-blue-950/30">
                <div class="text-[10px] text-muted-foreground mb-1">تحصيل العملاء (تحويل)</div>
                <div class="font-extrabold text-blue-600 dark:text-blue-400 text-lg">{{ money($transferRevenue) }}</div>
            </div>
            <div class="p-3 rounded-xl bg-green-50 dark:bg-green-950/30">
                <div class="text-[10px] text-muted-foreground mb-1">إجمالي تحصيل العملاء</div>
                <div class="font-extrabold text-lg">{{ money($deliveredRevenue) }}</div>
            </div>
            <div class="p-3 rounded-xl bg-purple-50 dark:bg-purple-950/30">
                <div class="text-[10px] text-muted-foreground mb-1">مدفوع الشركاء + تسوياتهم</div>
                <div class="font-extrabold text-purple-600 dark:text-purple-400 text-lg">{{ money($partnerPaid) }}</div>
            </div>
            <div class="p-3 rounded-xl bg-red-50 dark:bg-red-950/30">
                <div class="text-[10px] text-muted-foreground mb-1">مصروفات اليوم</div>
                <div class="font-extrabold text-red-600 dark:text-red-400 text-lg">{{ money($expensesTotal) }}</div>
            </div>
            <div class="p-3 rounded-xl bg-primary/10 border border-primary/20">
                <div class="text-[10px] text-muted-foreground mb-1">صافي إيراد اليوم</div>
                <div class="font-extrabold text-primary text-xl">{{ money($netRevenue) }}</div>
            </div>
        </div>
        <p class="text-[10px] text-muted-foreground mt-3">صافي اليوم = تحصيل العملاء + مدفوع الشركاء وتسوياتهم − المصروفات</p>
    </div>

    {{-- ===== تسليمات اليوم ===== --}}
    <div class="card p-5 mb-5">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <h2 class="font-bold flex items-center gap-2">{!! icon('check-circle', 'h-4 w-4 text-green-600') !!} تسليمات النهارده — {{ $delivered->count() }} أوردر</h2>
            <span class="text-xs text-muted-foreground">التحصيل بكامل السعر لكل أوردر — اضغط على الأوردر لتفاصيله</span>
        </div>
        @forelse ($delivered as $r)
            <details class="order-box mb-2">
                <summary>
                    <span class="font-mono font-extrabold text-primary text-[12px]" dir="ltr">{{ $r->order_number }}</span>
                    <span class="font-bold text-sm">{{ $r->customer->name }}</span>
                    <span class="text-xs text-muted-foreground" dir="ltr">{{ $r->customer->phone }}</span>
                    <span class="text-xs">{{ $r->device_type }}{{ $r->brand ? ' — '.$r->brand : '' }}</span>
                    <span class="chip bg-green-100 dark:bg-green-950/50 text-green-700 dark:text-green-300">{{ money($r->price ?? 0) }}</span>
                    @if ($r->payment_method)
                        <span class="chip {{ $r->payment_method === 'cash' ? 'bg-green-100 dark:bg-green-950/50 text-green-700 dark:text-green-300' : 'bg-blue-100 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300' }}">{{ $r->paymentLabel() }}</span>
                    @endif
                    <span class="chip bg-muted text-muted-foreground">{{ $r->completed_at?->format('H:i') }}</span>
                </summary>
                <div class="p-4 grid sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                    <div><p class="text-muted-foreground mb-0.5">الفني</p><p class="font-bold">{{ $r->assignedTechnician?->name ?? '—' }}</p></div>
                    <div><p class="text-muted-foreground mb-0.5">القسم</p><p class="font-bold">{{ $r->department?->name ?? '—' }}</p></div>
                    <div><p class="text-muted-foreground mb-0.5">ميعاد الدخول</p><p class="font-bold" dir="ltr">{{ $r->entered_at?->format('d/m H:i') ?? '—' }}</p></div>
                    <div><p class="text-muted-foreground mb-0.5">مدة الإصلاح</p><p class="font-bold">{{ $r->entered_at && $r->completed_at ? $r->entered_at->diffForHumans($r->completed_at, ['parts' => 2, 'short' => true]) : '—' }}</p></div>
                    <div class="sm:col-span-2 lg:col-span-4">
                        <p class="text-muted-foreground mb-0.5">العطل</p><p class="leading-relaxed">{{ $r->issue_description }}</p>
                    </div>
                    @if ($r->usedParts->count() > 0)
                        <div class="sm:col-span-2 lg:col-span-4">
                            <p class="text-muted-foreground mb-0.5">القطع المستخدمة ({{ $r->usedParts->count() }})</p>
                            @foreach ($r->usedParts as $p)
                                <span class="chip bg-muted text-muted-foreground">{{ $p->custom_name ?? $p->inventoryItem?->name }} × {{ $p->quantity }} — {{ money($p->unit_price) }}</span>
                            @endforeach
                        </div>
                    @endif
                    @if ($r->warranty_months > 0)
                        <div><p class="text-muted-foreground mb-0.5">الضمان</p><p class="font-bold">{{ $r->warranty_months }} شهر — حتى {{ $r->warranty_end_date }}</p></div>
                    @endif
                    @if ($r->admin_notes)
                        <div class="sm:col-span-2 lg:col-span-4"><p class="text-muted-foreground mb-0.5">ملاحظات</p><p>{{ $r->admin_notes }}</p></div>
                    @endif
                </div>
            </details>
        @empty
            <p class="text-xs text-muted-foreground text-center py-4">مفيش تسليمات النهارده</p>
        @endforelse
    </div>

    {{-- ===== وارد اليوم ===== --}}
    <div class="card p-5 mb-5">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <h2 class="font-bold flex items-center gap-2">{!! icon('log-in', 'h-4 w-4 text-emerald-600') !!} وارد النهارده — {{ $entered->count() }} جهاز</h2>
            <span class="text-xs text-muted-foreground">الأجهزة اللي دخلت المركز النهارده بكل تفاصيلها</span>
        </div>
        @forelse ($entered as $r)
            <details class="order-box mb-2">
                <summary>
                    <span class="font-mono font-extrabold text-primary text-[12px]" dir="ltr">{{ $r->order_number }}</span>
                    <span class="font-bold text-sm">{{ $r->customer->name }}</span>
                    <span class="text-xs">{{ $r->device_type }}{{ $r->brand ? ' — '.$r->brand : '' }}</span>
                    <span class="chip {{ $r->urgency === 'emergency' ? 'bg-red-100 dark:bg-red-950/50 text-red-700 dark:text-red-300' : ($r->urgency === 'urgent' ? 'bg-amber-100 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300' : 'bg-muted text-muted-foreground') }}">{{ $r->urgencyLabel() }}</span>
                    <span class="badge {{ $r->statusColor() }} text-[9px]"><span class="h-1.5 w-1.5 rounded-full {{ $r->statusDot() }}"></span>{{ $r->statusLabel() }}</span>
                    <span class="chip bg-muted text-muted-foreground">{{ $r->created_at->format('H:i') }}</span>
                </summary>
                <div class="p-4 grid sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                    <div><p class="text-muted-foreground mb-0.5">موبايل العميل</p><p class="font-bold" dir="ltr">{{ $r->customer->phone }}</p></div>
                    <div><p class="text-muted-foreground mb-0.5">رقم التواصل</p><p class="font-bold" dir="ltr">{{ $r->phone }}</p></div>
                    <div><p class="text-muted-foreground mb-0.5">القسم</p><p class="font-bold">{{ $r->department?->name ?? '—' }}</p></div>
                    <div><p class="text-muted-foreground mb-0.5">الفني المخصص</p><p class="font-bold">{{ $r->assignedTechnician?->name ?? 'لسه مفيش' }}</p></div>
                    <div class="sm:col-span-2 lg:col-span-4">
                        <p class="text-muted-foreground mb-0.5">العطل</p><p class="leading-relaxed">{{ $r->issue_description }}</p>
                    </div>
                    <div><p class="text-muted-foreground mb-0.5">المنطقة</p><p class="font-bold">{{ $r->areaLabel() }}</p></div>
                    <div><p class="text-muted-foreground mb-0.5">مهلة التسليم</p><p class="font-bold">{{ $r->isOverdue() ? 'متأخر!' : $r->slaRemainingLabel() }}</p></div>
                    <div><p class="text-muted-foreground mb-0.5">السعر</p><p class="font-bold">{{ $r->price ? money($r->price) : 'لسه محددش' }}</p></div>
                    <div><p class="text-muted-foreground mb-0.5">ميعاد الدخول</p><p class="font-bold" dir="ltr">{{ $r->entered_at?->format('d/m H:i') ?? '—' }}</p></div>
                </div>
            </details>
        @empty
            <p class="text-xs text-muted-foreground text-center py-4">مفيش أجهزة جديدة النهارده</p>
        @endforelse
    </div>

    {{-- ===== الحالات الجديدة: تواصل + جاهز ===== --}}
    <div class="grid lg:grid-cols-2 gap-4 mb-5">
        <div class="card p-5">
            <h2 class="font-bold mb-4 flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-500/10 text-cyan-500 shrink-0">{!! icon('phone-call', 'h-4 w-4') !!}</span>
                اتحولت «تواصل» النهارده — {{ $contactedToday->count() }}
            </h2>
            <div class="space-y-2">
                @forelse ($contactedToday as $r)
                    <a href="{{ route('reception.show', $r) }}" class="flex items-center justify-between p-2.5 rounded-xl bg-muted/40 hover:bg-muted/70 transition-colors text-sm flex-wrap gap-2">
                        <div>
                            <span class="font-mono font-bold text-[11px] text-primary" dir="ltr">{{ $r->order_number }}</span>
                            <b> {{ $r->customer->name }}</b> — {{ $r->device_type }}
                        </div>
                        <span class="badge {{ $r->statusColor() }} text-[9px]"><span class="h-1.5 w-1.5 rounded-full {{ $r->statusDot() }}"></span>{{ $r->statusLabel() }}</span>
                    </a>
                @empty
                    <p class="text-xs text-muted-foreground text-center py-3">مفيش</p>
                @endforelse
            </div>
        </div>
        <div class="card p-5">
            <h2 class="font-bold mb-4 flex items-center gap-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-500/10 text-teal-500 shrink-0">{!! icon('package', 'h-4 w-4') !!}</span>
                اجهزت النهارده (جاهز للتسليم) — {{ $readyToday->count() }}
            </h2>
            <div class="space-y-2">
                @forelse ($readyToday as $r)
                    <a href="{{ route('reception.show', $r) }}" class="flex items-center justify-between p-2.5 rounded-xl bg-muted/40 hover:bg-muted/70 transition-colors text-sm flex-wrap gap-2">
                        <div>
                            <span class="font-mono font-bold text-[11px] text-primary" dir="ltr">{{ $r->order_number }}</span>
                            <b> {{ $r->customer->name }}</b> — {{ $r->device_type }}
                        </div>
                        <span class="badge {{ $r->statusColor() }} text-[9px]"><span class="h-1.5 w-1.5 rounded-full {{ $r->statusDot() }}"></span>{{ $r->statusLabel() }}</span>
                    </a>
                @empty
                    <p class="text-xs text-muted-foreground text-center py-3">مفيش</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ===== شركاء الصيانة + تسوياتهم ===== --}}
    <div class="card p-5 mb-5">
        <h2 class="font-bold mb-4 flex items-center gap-2">{!! icon('handshake', 'h-4 w-4 text-primary') !!} شركاء الصيانة النهارده وتسوياتهم</h2>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <p class="text-xs font-bold text-muted-foreground mb-2">صيانات الشركاء ({{ $partnerRepairs->count() }}{{ $partnerUnpriced > 0 ? ' — منها '.$partnerUnpriced.' بدون سعر' : '' }})</p>
                <div class="space-y-2">
                    @forelse ($partnerRepairs as $r)
                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-muted/40 text-sm flex-wrap gap-2">
                            <div>
                                <b>{{ $r->partner->name }}</b> — {{ $r->device_type }}{{ $r->brand ? ' ('.$r->brand.')' : '' }}
                                <div class="text-[10px] text-muted-foreground">{{ $r->customer_name ?: 'بدون اسم عميل' }} • مدفوع {{ money($r->paid_amount) }}</div>
                            </div>
                            @if ($r->hasPrice())
                                <b class="text-sm">{{ money($r->total_amount) }}</b>
                            @else
                                <span class="chip bg-cyan-100 dark:bg-cyan-950/50 text-cyan-700 dark:text-cyan-300">السعر لسه محددش</span>
                            @endif
                        </div>
                    @empty
                        <p class="text-xs text-muted-foreground text-center py-3">مفيش صيانات شركاء النهارده</p>
                    @endforelse
                </div>
            </div>
            <div>
                <p class="text-xs font-bold text-muted-foreground mb-2">تسويات الشركاء ({{ $partnerSettlements->count() }} — إجمالي {{ money($partnerSettlements->sum('amount')) }})</p>
                <div class="space-y-2">
                    @forelse ($partnerSettlements as $s)
                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-muted/40 text-sm">
                            <div>
                                <b>{{ $s->partner->name }}</b>
                                <div class="text-[10px] text-muted-foreground">{{ $s->notes ?: 'تسوية' }}</div>
                            </div>
                            <b class="text-green-600 dark:text-green-400">{{ money($s->amount) }}</b>
                        </div>
                    @empty
                        <p class="text-xs text-muted-foreground text-center py-3">مفيش تسويات النهارده</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ===== مرتجعات وملغي + مصروفات ===== --}}
    <div class="grid lg:grid-cols-3 gap-4 mb-5">
        <div class="card p-5">
            <h2 class="font-bold mb-3 text-sm flex items-center gap-2" style="color: oklch(0.7 0.17 55)">{!! icon('undo', 'h-4 w-4') !!} مرتجعات النهارده ({{ $returnedToday->count() }})</h2>
            <div class="space-y-2">
                @forelse ($returnedToday as $r)
                    <div class="p-2.5 rounded-xl bg-muted/40 text-xs">
                        <span class="font-mono font-bold" dir="ltr">{{ $r->order_number }}</span> — {{ $r->customer->name }} — {{ $r->device_type }}
                        @if ($r->return_reason)<p class="text-muted-foreground mt-1">السبب: {{ $r->return_reason }}</p>@endif
                    </div>
                @empty
                    <p class="text-xs text-muted-foreground text-center py-3">مفيش مرتجعات النهارده</p>
                @endforelse
            </div>
        </div>
        <div class="card p-5">
            <h2 class="font-bold mb-3 text-sm flex items-center gap-2 text-red-600 dark:text-red-400">{!! icon('x', 'h-4 w-4') !!} ملغي النهارده ({{ $cancelledToday->count() }})</h2>
            <div class="space-y-2">
                @forelse ($cancelledToday as $r)
                    <div class="p-2.5 rounded-xl bg-muted/40 text-xs">
                        <span class="font-mono font-bold" dir="ltr">{{ $r->order_number }}</span> — {{ $r->customer->name }} — {{ $r->device_type }}
                    </div>
                @empty
                    <p class="text-xs text-muted-foreground text-center py-3">مفيش إلغاء النهارده</p>
                @endforelse
            </div>
        </div>
        <div class="card p-5">
            <h2 class="font-bold mb-3 text-sm flex items-center gap-2 text-red-600 dark:text-red-400">{!! icon('wallet', 'h-4 w-4') !!} مصروفات النهارده ({{ money($expensesTotal) }})</h2>
            <div class="space-y-2">
                @forelse ($expenses as $e)
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-muted/40 text-xs">
                        <div>
                            <b>{{ $e->description }}</b>
                            <div class="text-muted-foreground">{{ $e->categoryLabel() ?? $e->category }} • {{ $e->createdBy?->name }}</div>
                        </div>
                        <b class="text-red-600 dark:text-red-400">{{ money($e->amount) }}</b>
                    </div>
                @empty
                    <p class="text-xs text-muted-foreground text-center py-3">مفيش مصروفات النهارده</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ===== سجل كل ما حدث ===== --}}
    <div class="card p-5">
        <h2 class="font-bold mb-4 flex items-center gap-2">{!! icon('history', 'h-4 w-4 text-primary') !!} سجل كل ما حدث النهارده ({{ $timeline->count() }} حركة)</h2>
        @if ($timeline->isEmpty())
            <p class="text-xs text-muted-foreground text-center py-6">اليوم ده كان هادي — مفيش حركات مسجلة</p>
        @else
            <div class="space-y-1.5 max-h-[560px] overflow-y-auto">
                @foreach ($timeline as $log)
                    <div class="flex items-start gap-2.5 p-2.5 rounded-xl bg-muted/30 text-xs">
                        <span class="chip bg-muted text-muted-foreground shrink-0" dir="ltr">{{ $log->created_at->format('H:i') }}</span>
                        <div class="flex-1 leading-relaxed">
                            {{ $log->details }}
                            <span class="text-muted-foreground"> — {{ $log->user?->name ?? 'النظام' }}</span>
                        </div>
                        <span class="chip bg-primary/10 text-primary shrink-0">{{ $log->actionLabel() }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
