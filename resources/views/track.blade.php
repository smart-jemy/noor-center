@extends('layouts.app')
@section('title', 'تتبع طلبك')

@section('content')
<div class="container mx-auto max-w-2xl px-4 sm:px-6 py-8">
    <div class="text-center mb-8">
        <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-xl bg-primary/10 text-primary">
            {!! icon('search', 'h-7 w-7') !!}
        </div>
        <h1 class="text-2xl md:text-3xl font-extrabold mb-2">تتبع طلبك</h1>
        <p class="text-muted-foreground text-sm">تابع حالة طلب الصيانة برقم الطلب ورقم الهاتف</p>
    </div>

    {{-- نموذج البحث --}}
    <div class="card p-6 mb-6">
        <form method="POST" action="{{ route('track.search') }}" class="space-y-4">
            @csrf
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="label" for="order_number">رقم الطلب</label>
                    <input id="order_number" name="order_number" placeholder="NC 0001 {{ now()->year }}"
                           class="input {{ $errors->has('order_number') ? 'input-error' : '' }}" dir="ltr" style="text-align: right"
                           value="{{ old('order_number') ?? ($result->order_number ?? '') }}">
                </div>
                <div>
                    <label class="label" for="phone">رقم الهاتف</label>
                    <input id="phone" name="phone" value="{{ old('phone') ?? ($result->phone ?? '') }}" placeholder="01xxxxxxxxx"
                           class="input {{ $errors->has('phone') ? 'input-error' : '' }}" dir="ltr" style="text-align: right">
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-full">
                {!! icon('search', 'h-4 w-4') !!}
                ابحث عن طلبي
            </button>
        </form>
    </div>

    @guest
    <div class="card p-4 flex items-center gap-3 bg-primary/5 border-primary/20">
        {!! icon('info', 'h-5 w-5 text-primary shrink-0') !!}
        <p class="text-sm text-muted-foreground">
            عندك حساب؟ <a href="{{ route('login') }}" class="text-primary font-bold hover:underline">سجل دخولك</a> وشوف كل طلباتك وتفاصيلها.
        </p>
    </div>
    @endguest

    {{-- النتيجة --}}
    @if ($result ?? null)
        @php
            $statusFlow = [
                'PENDING' => ['كشف', 'طلبك وصلنا وهنكشف على الجهاز'],
                'CONTACTED' => ['تواصل', 'بنتواصل معاك لتحديد التفاصيل'],
                'CONFIRMED' => ['تأكيد', 'تم تأكيد المعاد — الفني هيجيلك في المعاد المحدد'],
                'IN_PROGRESS' => ['تنفيذ', 'الفني بدأ شغل الصيانة'],
                'READY' => ['جاهز', 'الإصلاح خلص — جهازك جاهز للاستلام'],
                'COMPLETED' => ['تسليم', 'تم التسليم وتحصيل الأجر — شكراً ليك'],
            ];
            $flowKeys = array_keys($statusFlow);
            $statusIndex = array_search($result->status, $flowKeys);
        @endphp

        <div class="card p-6 fade-in-up">
            <div class="flex items-center justify-between flex-wrap gap-2 mb-4">
                <div>
                    <div class="text-xs text-muted-foreground">رقم الطلب</div>
                    <div class="text-xl font-extrabold" dir="ltr">{{ $result->order_number }}</div>
                </div>
                <span class="badge {{ $result->statusColor() }} text-sm px-3 py-1">{{ $result->statusLabel() }}</span>
            </div>

            {{-- مسار الحالة --}}
            @if ($result->status !== 'CANCELLED')
                <div class="relative mb-6">
                    <div class="absolute top-4 right-4 left-4 h-0.5 bg-border"></div>
                    <div class="absolute top-4 right-4 h-0.5 bg-primary transition-all" style="width: {{ ($statusIndex !== false ? $statusIndex / (count($flowKeys) - 1) : 0) * 100 }}%"></div>
                    <div class="relative grid grid-cols-6 text-center">
                        @foreach ($statusFlow as $key => $step)
                            @php $done = $statusIndex !== false && array_search($key, $flowKeys) <= $statusIndex; @endphp
                            <div>
                                <div class="mx-auto flex h-8 w-8 items-center justify-center rounded-full border-2 mb-2 transition-colors {{ $done ? 'bg-primary border-primary text-primary-foreground' : 'bg-card border-border text-muted-foreground' }}">
                                    {!! $done ? icon('check', 'h-4 w-4') : icon('clock', 'h-4 w-4') !!}
                                </div>
                                <div class="text-[11px] font-bold {{ $done ? 'text-foreground' : 'text-muted-foreground' }}">{{ $step[0] }}</div>
                                <div class="text-[10px] text-muted-foreground hidden sm:block">{{ $step[1] }}</div>
                            </div>
                        @endforeach
                    </div>
                    @if ($result->status === 'RETURNED')
                        <div class="mt-3 text-center text-xs font-bold text-orange-700 dark:text-orange-300">مرتجع للفني — الجهاز رجع للشركة لإعادة الإصلاح</div>
                    @endif
                </div>
            @else
                <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900 text-red-700 dark:text-red-300 text-sm">
                    تم إلغاء هذا الطلب. لو ده غلط اتصل بينا: <a href="tel:{{ $siteSettings->phone }}" class="font-bold underline" dir="ltr">{{ $siteSettings->phone }}</a>
                </div>
            @endif

            {{-- التفاصيل --}}
            <div class="grid sm:grid-cols-2 gap-3 text-sm">
                <div class="flex justify-between p-3 rounded-xl bg-muted/40"><span class="text-muted-foreground">نوع الجهاز</span><b>{{ $result->device_type }}</b></div>
                @if ($result->brand)<div class="flex justify-between p-3 rounded-xl bg-muted/40"><span class="text-muted-foreground">الماركة</span><b>{{ $result->brand }}</b></div>@endif
                <div class="flex justify-between p-3 rounded-xl bg-muted/40"><span class="text-muted-foreground">المنطقة</span><b>{{ $result->areaLabel() }}</b></div>
                <div class="flex justify-between p-3 rounded-xl bg-muted/40"><span class="text-muted-foreground">الميعاد</span><b>{{ dt($result->preferred_date) }} — {{ $result->timeSlotLabel() }}</b></div>
                @if ($result->price !== null && $result->status === 'COMPLETED')
                    <div class="flex justify-between p-3 rounded-xl bg-muted/40"><span class="text-muted-foreground">التكلفة</span><b class="text-primary">{{ money($result->price) }}</b></div>
                @endif
                @if ($result->hasWarranty())
                    <div class="flex justify-between p-3 rounded-xl bg-muted/40"><span class="text-muted-foreground">الضمان</span><b class="{{ $result->warrantyActive() ? 'text-green-600' : 'text-muted-foreground' }}">{{ $result->warranty_months }} شهر — {{ $result->warrantyActive() ? 'ساري' : 'منتهي' }}</b></div>
                @endif
                @if ($result->assignedTechnician && in_array($result->status, ['CONFIRMED', 'IN_PROGRESS', 'READY', 'COMPLETED']))
                    <div class="flex justify-between p-3 rounded-xl bg-muted/40"><span class="text-muted-foreground">الفني المسؤول</span><b>{{ $result->assignedTechnician->name }}</b></div>
                @endif
            </div>

            @if ($result->status === 'COMPLETED' && $result->warranty_months > 0)
                <div class="mt-4 p-3 rounded-xl bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-900 flex items-center gap-2 text-sm text-green-700 dark:text-green-300">
                    {!! icon('shield-check', 'h-5 w-5') !!}
                    الضمان ساري حتى {{ dt($result->warranty_end_date) }} — أي عطل نفسه في الفترة دي الصيانة مجانية.
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
