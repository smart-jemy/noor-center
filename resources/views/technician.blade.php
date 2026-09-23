@extends('layouts.app')
@section('title', 'طلباتي (فني)')

@section('content')
<div class="container mx-auto max-w-4xl px-4 sm:px-6 py-8">
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-primary/15 to-primary/5 text-primary ring-1 ring-primary/20 shrink-0">
                {!! icon('hard-hat', 'h-6 w-6') !!}
            </div>
            <div>
                <h1 class="text-2xl font-extrabold mb-0.5">طلباتي (فني)</h1>
                <p class="text-muted-foreground text-sm">{{ auth()->user()->name }}{{ auth()->user()->specialty ? ' — '.auth()->user()->specialty : '' }}</p>
            </div>
        </div>

        {{-- إحصائيات — زي الأصل: + جاهز + تقييمات + متوسط التقييم — تصميم KPI حديث --}}
        <div class="grid grid-cols-3 md:grid-cols-6 gap-2.5 stagger">
            <div class="kpi !p-3.5" style="--kpi-accent: var(--color-amber-500)">
                <div class="kpi-value">{{ $stats['active'] }}</div>
                <div class="kpi-label">شغل نشط</div>
            </div>
            <div class="kpi !p-3.5" style="--kpi-accent: var(--color-teal-500)">
                <div class="kpi-value">{{ $stats['ready'] ?? 0 }}</div>
                <div class="kpi-label">جاهز للاستلام</div>
            </div>
            <div class="kpi !p-3.5" style="--kpi-accent: var(--color-green-500)">
                <div class="kpi-value">{{ $stats['completed'] }}</div>
                <div class="kpi-label">تم تسليمها</div>
            </div>
            <div class="kpi !p-3.5" style="--kpi-accent: var(--color-primary)">
                <div class="kpi-value">{{ $stats['total'] }}</div>
                <div class="kpi-label">الإجمالي</div>
            </div>
            <div class="kpi !p-3.5" style="--kpi-accent: var(--color-blue-500)">
                <div class="kpi-value">{{ $stats['reviews'] }}</div>
                <div class="kpi-label">تقييمات</div>
            </div>
            <div class="kpi !p-3.5" style="--kpi-accent: var(--color-amber-500)">
                <div class="kpi-value flex items-center gap-1">
                    @if ($stats['avgRating'] > 0)<span class="fill-amber-500">{!! icon('star', 'h-4 w-4') !!}</span>@endif
                    <span>{{ $stats['avgRating'] > 0 ? number_format($stats['avgRating'], 1) : '—' }}</span>
                </div>
                <div class="kpi-label">متوسط التقييم</div>
            </div>
        </div>
    </div>

    {{-- فلاتر --}}
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('technician') }}" class="badge {{ ! request('status') ? 'bg-primary/10 text-primary border-primary/20' : 'bg-card text-muted-foreground border-border' }} px-3.5 py-1.5 cursor-pointer">الكل</a>
        @foreach (['CONFIRMED', 'IN_PROGRESS', 'READY', 'RETURNED', 'COMPLETED'] as $status)
            <a href="{{ route('technician', ['status' => $status]) }}"
               class="badge {{ request('status') === $status ? \App\Models\ServiceRequest::STATUS_COLORS[$status] : 'bg-card text-muted-foreground border-border' }} px-3.5 py-1.5 cursor-pointer">
                {{ \App\Models\ServiceRequest::STATUS_LABELS[$status] }}
            </a>
        @endforeach
    </div>

    {{-- الطلبات --}}
    @forelse ($requests as $r)
        <div class="card p-4 md:p-5 mb-3 fade-in-up" x-data="{ open: false }">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div class="flex items-center gap-3 cursor-pointer flex-1 min-w-0" @click="open = !open">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary shrink-0">
                        {!! device_icon($r->device_type, 'h-5 w-5') !!}
                    </div>
                    <div>
                        <div class="font-bold text-sm">
                            {{ $r->device_type }}
                            @if ($r->brand)<span class="text-muted-foreground font-normal">— {{ $r->brand }}</span>@endif
                            <span class="text-muted-foreground font-normal text-xs">— {{ $r->customer->name }}</span>
                            @if ($r->return_count > 0)
                                <span class="inline-flex items-center gap-0.5 text-[10px] font-bold text-orange-700 dark:text-orange-300 bg-orange-100 dark:bg-orange-950/50 px-1.5 py-0.5 rounded-md">{!! icon('undo', 'h-2.5 w-2.5') !!} مرتجع {{ $r->return_count }}×</span>
                            @endif
                        </div>
                        <div class="text-xs text-muted-foreground mt-0.5">
                            <span dir="ltr">{{ $r->order_number }}</span> • {{ $r->areaLabel() }} • {{ $r->urgencyLabel() }} • {{ $r->slaRemainingLabel() }}
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-1.5">
                    @if ($r->price)<span class="text-sm font-extrabold text-primary">{{ money($r->price) }}</span>@endif
                    <span class="badge {{ $r->statusColor() }}">{{ $r->statusLabel() }}</span>
                    {{-- إجراء سريع بدون فتح التفاصيل — زي الأصل --}}
                    @if ($r->status === 'CONFIRMED')
                        <form method="POST" action="{{ route('technician.status', $r) }}" class="inline">
                            @csrf
                            <input type="hidden" name="status" value="IN_PROGRESS">
                            <button type="submit" class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 hover:opacity-80">بدء التنفيذ</button>
                        </form>
                    @endif
                    <button @click="open = !open" class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-muted hover:bg-muted/70">تفاصيل</button>
                    <span @click="open = !open" class="cursor-pointer inline-flex transition-transform" x-bind:class="open ? 'rotate-180' : ''">{!! icon('chevron-down', 'h-4 w-4 text-muted-foreground') !!}</span>
                </div>
            </div>

            {{-- تقييم العميل على الشغلانة دي — زي الأصل --}}
            @if ($r->review && $r->review->status === 'APPROVED')
                <div class="mt-3 p-3 rounded-xl bg-amber-50/60 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/60 flex items-start gap-2">
                    <div class="flex gap-0.5 shrink-0 mt-0.5">
                        @for ($i = 1; $i <= 5; $i++)
                            <span class="{{ $i <= $r->review->rating ? 'fill-amber-500 text-amber-500' : 'text-amber-300 dark:text-amber-800' }}">{!! icon('star', 'h-3.5 w-3.5') !!}</span>
                        @endfor
                    </div>
                    <div class="text-xs text-muted-foreground leading-relaxed">{{ $r->review->comment }}</div>
                </div>
            @endif

            {{-- التفاصيل + تحديث الحالة --}}
            <div x-show="open" x-transition class="mt-4 pt-4 border-t border-border/60 space-y-4" style="display: none">
                <div class="grid sm:grid-cols-2 gap-2 text-sm">
                    <div class="p-2.5 rounded-lg bg-muted/40 flex justify-between"><span class="text-muted-foreground">العميل</span><b>{{ $r->customer->name }}</b></div>
                    <div class="p-2.5 rounded-lg bg-muted/40 flex justify-between"><span class="text-muted-foreground">هاتف</span><b dir="ltr"><a href="tel:{{ $r->customer->phone }}" class="text-primary hover:underline">{{ $r->customer->phone }}</a></b></div>
                    <div class="p-2.5 rounded-lg bg-muted/40 flex justify-between sm:col-span-2"><span class="text-muted-foreground">العنوان</span><b class="text-left">{{ $r->address }}</b></div>
                    <div class="p-2.5 rounded-lg bg-muted/40 sm:col-span-2"><div class="text-muted-foreground text-xs mb-1">العطل</div>{{ $r->issue_description }}</div>
                    @if ($r->admin_notes)
                        <div class="p-2.5 rounded-lg bg-blue-50 dark:bg-blue-950/40 sm:col-span-2"><div class="text-xs text-blue-700 dark:text-blue-300 mb-1 font-bold">ملاحظة الإدارة</div>{{ $r->admin_notes }}</div>
                    @endif
                </div>

                {{-- الصور --}}
                @if ($r->photos && count($r->photos))
                    <div class="flex flex-wrap gap-2">
                        @foreach ($r->photos as $photo)
                            <a href="{{ asset(ltrim($photo, '/')) }}" target="_blank" class="block w-20 h-20 rounded-lg overflow-hidden border border-border">
                                <img src="{{ asset(ltrim($photo, '/')) }}" class="w-full h-full object-cover" alt="صورة">
                            </a>
                        @endforeach
                    </div>
                @endif

                {{-- سبب الإرجاع للمرتجعات --}}
                @if ($r->status === 'RETURNED' && $r->return_reason)
                    <div class="p-3 rounded-xl bg-orange-50 dark:bg-orange-950/20 border border-orange-300 dark:border-orange-800">
                        <p class="text-xs font-extrabold text-orange-800 dark:text-orange-300 flex items-center gap-1.5">{!! icon('undo', 'h-3.5 w-3.5') !!} مرتجع من العميل — سبب الإرجاع</p>
                        <p class="text-xs text-orange-800/80 dark:text-orange-400/80 mt-1 leading-relaxed">{{ $r->return_reason }}</p>
                        <p class="text-[10px] text-muted-foreground mt-1">الشركة بتصلحه تاني — {{ $r->slaRemainingLabel() }}</p>
                    </div>
                @endif

                {{-- تحديث الحالة: تنفيذ ← جاهز ← تسليم --}}
                @if (in_array($r->status, ['CONFIRMED', 'IN_PROGRESS', 'RETURNED']))
                    <form method="POST" action="{{ route('technician.status', $r) }}" class="p-4 rounded-xl bg-muted/30 border border-border/60 space-y-3" x-data="{ newStatus: '{{ $r->status === 'IN_PROGRESS' ? 'READY' : 'IN_PROGRESS' }}', showDeliver: false }">
                        @csrf
                        <input type="hidden" name="status" :value="newStatus">

                        @if (in_array($r->status, ['CONFIRMED', 'RETURNED']))
                            <button type="submit" @click="newStatus = 'IN_PROGRESS'" class="btn btn-outline w-full {{ $r->status === 'RETURNED' ? '!border-orange-400 !text-orange-700 dark:!text-orange-300' : '' }}">
                                {!! icon($r->status === 'RETURNED' ? 'undo' : 'play', 'h-4 w-4') !!}
                                {{ $r->status === 'RETURNED' ? 'بدء إعادة الإصلاح — تنفيذ' : 'بدأت الشغل — تنفيذ' }}
                            </button>
                        @endif

                        @if ($r->status === 'IN_PROGRESS')
                            <button type="submit" @click="newStatus = 'READY'" class="btn btn-primary w-full">
                                {!! icon('package', 'h-4 w-4') !!}
                                خلصت الإصلاح — الجهاز جاهز
                            </button>
                            <button type="button" @click="showDeliver = !showDeliver" class="w-full text-xs font-bold text-muted-foreground hover:text-foreground py-1">
                                {{ showDeliver ? 'إخفاء' : 'تسليم مباشر للعميل بدل جاهز؟' }}
                            </button>
                            <div x-show="showDeliver" x-transition class="space-y-3 pt-2 border-t border-border/60">
                                <div class="flex items-center gap-2 text-sm font-bold">
                                    {!! icon('check-circle', 'h-4 w-4 text-green-600') !!}
                                    تسليم الجهاز مع الضمان
                                </div>
                                <div class="grid grid-cols-5 gap-2">
                                    @foreach ([0, 1, 3, 6, 12] as $months)
                                        <label class="relative cursor-pointer" @click="newStatus = 'COMPLETED'">
                                            <input type="radio" name="warranty_opt" value="{{ $months }}" class="peer sr-only" {{ $months === 3 ? 'checked' : '' }} @change="document.getElementById('warranty-{{ $r->id }}').value = '{{ $months }}'">
                                            <div class="rounded-lg border-2 border-border bg-card p-2 text-center text-xs font-bold transition-all peer-checked:border-primary peer-checked:bg-primary/5">
                                                {{ $months === 0 ? 'بدون' : $months.' شهر' }}
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                                <input type="hidden" name="warranty_months" id="warranty-{{ $r->id }}" value="3">
                                <button type="submit" @click="newStatus = 'COMPLETED'" class="btn btn-success w-full">
                                    {!! icon('check-circle', 'h-4 w-4') !!}
                                    تسليم — تمت الصيانة
                                </button>
                            </div>
                        @endif
                    </form>
                @elseif ($r->status === 'READY')
                    <div class="p-4 rounded-xl bg-teal-50 dark:bg-teal-950/30 border border-teal-200 dark:border-teal-900 space-y-3">
                        <div class="text-sm font-bold text-teal-800 dark:text-teal-300 flex items-center gap-2">
                            {!! icon('package', 'h-4 w-4') !!}
                            الجهاز جاهز — في انتظار التسليم من الاستقبال (التحصيل كامل)
                        </div>
                        <form method="POST" action="{{ route('technician.status', $r) }}" class="space-y-3" x-data="{ newStatus: 'COMPLETED' }">
                            @csrf
                            <input type="hidden" name="status" :value="newStatus">
                            <div class="grid grid-cols-5 gap-2">
                                @foreach ([0, 1, 3, 6, 12] as $months)
                                    <label class="relative cursor-pointer" @click="newStatus = 'COMPLETED'">
                                        <input type="radio" name="warranty_opt" value="{{ $months }}" class="peer sr-only" {{ $months === 3 ? 'checked' : '' }} @change="document.getElementById('warranty-r-{{ $r->id }}').value = '{{ $months }}'">
                                        <div class="rounded-lg border-2 border-border bg-card p-2 text-center text-xs font-bold transition-all peer-checked:border-primary peer-checked:bg-primary/5">
                                            {{ $months === 0 ? 'بدون' : $months.' شهر' }}
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            <input type="hidden" name="warranty_months" id="warranty-r-{{ $r->id }}" value="3">
                            <button type="submit" @click="newStatus = 'COMPLETED'" class="btn btn-success w-full">
                                {!! icon('check-circle', 'h-4 w-4') !!}
                                تسليم مباشر — تمت الصيانة
                            </button>
                        </form>
                    </div>
                @elseif ($r->status === 'COMPLETED')
                    <div class="p-3 rounded-xl bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-900 text-green-700 dark:text-green-300 text-sm flex items-center gap-2">
                        {!! icon('check-circle', 'h-4 w-4') !!}
                        تم التسليم في {{ dt($r->completed_at, true) }}
                        @if ($r->hasWarranty()) — ضمان {{ $r->warranty_months }} شهر حتى {{ dt($r->warranty_end_date) }} @endif
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="card p-10 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-muted text-muted-foreground">
                {!! icon('hard-hat', 'h-7 w-7') !!}
            </div>
            <h3 class="font-bold mb-1">مفيش طلبات معاك دلوقتي</h3>
            <p class="text-muted-foreground text-sm">هتلاقي الطلبات هنا أول ما الإدارة تعينهالك — وهيوصلك إشعار كمان</p>
        </div>
    @endforelse

    {{ $requests->links() }}
</div>

<script>
// ربط اختيار الضمان بالحقل المخفي
document.querySelectorAll('input[name="warranty_opt"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
        const form = radio.closest('form');
        const hidden = form.querySelector('input[name="warranty_months"]');
        if (hidden) hidden.value = radio.value;
    });
});
</script>
@endsection
