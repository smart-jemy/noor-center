@extends('layouts.app')
@section('title', 'طلب '.$serviceRequest->order_number)

@section('content')
<div class="container mx-auto max-w-3xl px-4 sm:px-6 py-8">
    {{-- رأس الطلب --}}
    <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
        <div>
            <a href="{{ route('requests.index') }}" class="text-xs text-muted-foreground hover:text-primary mb-1 inline-flex items-center gap-1">
                {!! icon('arrow-right', 'h-3 w-3') !!} رجوع لطلباتي
            </a>
            <h1 class="text-2xl font-extrabold flex items-center gap-3 flex-wrap">
                {{ $serviceRequest->device_type }}
                @if ($serviceRequest->brand)<span class="text-muted-foreground text-lg font-normal">— {{ $serviceRequest->brand }}</span>@endif
            </h1>
            <p class="text-muted-foreground text-sm mt-0.5" dir="ltr">{{ $serviceRequest->order_number }}</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="badge {{ $serviceRequest->statusColor() }} text-sm px-3 py-1">{{ $serviceRequest->statusLabel() }}</span>
            <a href="{{ route('requests.receipt', $serviceRequest) }}" target="_blank" class="btn btn-outline btn-sm" title="طباعة إيصال الاستلام">
                {!! icon('printer', 'h-3.5 w-3.5') !!}
                إيصال
            </a>
        </div>
    </div>

    {{-- مسار الحالة: كشف ← تواصل ← تأكيد ← تنفيذ ← جاهز ← تسليم --}}
    @php $statusFlow = \App\Models\ServiceRequest::LIFE_CYCLE; $statusIndex = array_search($serviceRequest->status, $statusFlow); $flowCount = count($statusFlow); @endphp
    @if ($serviceRequest->status !== 'CANCELLED')
        <div class="card p-5 mb-4">
            <div class="relative">
                <div class="absolute top-4 right-4 left-4 h-0.5 bg-border"></div>
                <div class="absolute top-4 right-4 h-0.5 bg-primary" style="width: {{ ($statusIndex !== false ? $statusIndex / ($flowCount - 1) : 0) * 100 }}%"></div>
                <div class="relative grid grid-cols-6 text-center">
                    @foreach ($statusFlow as $i => $key)
                        @php $done = $statusIndex !== false && $i <= $statusIndex; @endphp
                        <div>
                            <div class="mx-auto flex h-8 w-8 items-center justify-center rounded-full border-2 mb-2 transition-colors {{ $done ? 'bg-primary border-primary text-primary-foreground' : 'bg-card border-border text-muted-foreground' }}">
                                {!! $done ? icon('check', 'h-4 w-4') : icon('clock', 'h-4 w-4') !!}
                            </div>
                            <div class="text-[11px] font-bold {{ $done ? 'text-foreground' : 'text-muted-foreground' }}">{{ \App\Models\ServiceRequest::STATUS_LABELS[$key] }}</div>
                        </div>
                    @endforeach
                </div>
                @if ($serviceRequest->status === 'RETURNED')
                    <div class="mt-3 text-center text-xs font-bold text-orange-700 dark:text-orange-300">مرتجع للفني — الجهاز رجع للشركة لإعادة الإصلاح</div>
                @endif
            </div>
        </div>
    @else
        <div class="mb-4 p-4 rounded-xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900 text-red-700 dark:text-red-300 text-sm">
            تم إلغاء هذا الطلب.
        </div>
    @endif

    {{-- التفاصيل --}}
    <div class="card p-5 md:p-6 mb-4">
        <h2 class="font-bold mb-4 flex items-center gap-2">{!! icon('file-text', 'h-4 w-4 text-primary') !!} تفاصيل الطلب</h2>
        <div class="grid sm:grid-cols-2 gap-3 text-sm">
            <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">المنطقة</span><b>{{ $serviceRequest->areaLabel() }}</b></div>
            <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">الميعاد المطلوب</span><b>{{ dt($serviceRequest->preferred_date) }}</b></div>
            <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">الفترة</span><b>{{ $serviceRequest->timeSlotLabel() }}</b></div>
            <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">هاتف التواصل</span><b dir="ltr">{{ $serviceRequest->phone }}</b></div>
            @if ($serviceRequest->department)
                <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">القسم المسؤول</span><b>{{ $serviceRequest->department->name }}</b></div>
            @endif
            @if ($serviceRequest->assignedTechnician)
                <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">الفني المسؤول</span><b>{{ $serviceRequest->assignedTechnician->name }} {{ $serviceRequest->assignedTechnician->specialty ? '('.$serviceRequest->assignedTechnician->specialty.')' : '' }}</b></div>
            @endif
            @if ($serviceRequest->status === 'COMPLETED' && $serviceRequest->completed_at)
                <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">تاريخ التسليم</span><b>{{ dt($serviceRequest->completed_at, true) }}</b></div>
            @endif
            <div class="sm:col-span-2 p-3 rounded-xl bg-muted/40">
                <div class="text-muted-foreground text-xs mb-1">العنوان</div>
                <div>{{ $serviceRequest->address }}</div>
            </div>
            <div class="sm:col-span-2 p-3 rounded-xl bg-muted/40">
                <div class="text-muted-foreground text-xs mb-1">وصف العطل</div>
                <div class="leading-relaxed">{{ $serviceRequest->issue_description }}</div>
            </div>
        </div>

        {{-- السعر والضمان — الأجر يتحصل كاملاً عند التسليم --}}
        @if ($serviceRequest->price !== null && in_array($serviceRequest->status, ['COMPLETED', 'IN_PROGRESS', 'READY']))
            <div class="mt-4 grid sm:grid-cols-3 gap-3">
                <div class="p-4 rounded-xl bg-primary/5 border border-primary/10 text-center">
                    <div class="text-[10px] text-muted-foreground mb-0.5">أجر الصيانة</div>
                    <div class="text-xl font-extrabold text-primary">{{ money($serviceRequest->price) }}</div>
                </div>
                <div class="p-4 rounded-xl bg-muted/40 text-center">
                    <div class="text-[10px] text-muted-foreground mb-0.5">طريقة الدفع</div>
                    <div class="text-xl font-extrabold text-green-600 dark:text-green-400">{{ $serviceRequest->paymentLabel() !== '—' ? $serviceRequest->paymentLabel() : 'كاش عند التسليم' }}</div>
                </div>
                <div class="p-4 rounded-xl bg-muted/40 text-center">
                    <div class="text-[10px] text-muted-foreground mb-0.5">الضمان</div>
                    <div class="text-xl font-extrabold">{{ $serviceRequest->hasWarranty() ? $serviceRequest->warranty_months.' شهر' : '—' }}</div>
                </div>
            </div>
        @endif

        @if ($serviceRequest->hasWarranty())
            <div class="mt-4 p-4 rounded-xl {{ $serviceRequest->warrantyActive() ? 'bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-900 text-green-700 dark:text-green-300' : 'bg-muted/40 border border-border text-muted-foreground' }} flex items-center gap-3 text-sm">
                {!! icon('shield-check', 'h-5 w-5 shrink-0') !!}
                <div>
                    <b>ضمان {{ $serviceRequest->warranty_months }} شهر</b> —
                    {{ $serviceRequest->warrantyActive() ? 'ساري حتى '.dt($serviceRequest->warranty_end_date) : 'انتهى في '.dt($serviceRequest->warranty_end_date) }}
                </div>
            </div>
        @endif

        {{-- الصور --}}
        @if ($serviceRequest->photos && count($serviceRequest->photos) > 0)
            <div class="mt-4">
                <div class="text-muted-foreground text-xs mb-2">صور مرفقة</div>
                <div class="flex flex-wrap gap-3">
                    @foreach ($serviceRequest->photos as $photo)
                        <a href="{{ asset(ltrim($photo, '/')) }}" target="_blank" class="block w-24 h-24 rounded-xl overflow-hidden border-2 border-border hover:border-primary/40 transition-colors">
                            <img src="{{ asset(ltrim($photo, '/')) }}" class="w-full h-full object-cover" alt="صورة العطل">
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- القطع المستخدمة --}}
    @if ($serviceRequest->usedParts->isNotEmpty())
        <div class="card p-5 md:p-6 mb-4">
            <h2 class="font-bold mb-4 flex items-center gap-2">{!! icon('package', 'h-4 w-4 text-primary') !!} قطع الغيار المستخدمة</h2>
            <div class="space-y-2">
                @foreach ($serviceRequest->usedParts as $part)
                    <div class="flex items-center justify-between p-3 rounded-xl bg-muted/40 text-sm">
                        <div class="flex items-center gap-2">
                            {!! icon('wrench', 'h-4 w-4 text-muted-foreground') !!}
                            <b>{{ $part->name() }}</b>
                            <span class="text-muted-foreground text-xs">× {{ $part->quantity }}</span>
                        </div>
                        <b>{{ money($part->total()) }}</b>
                    </div>
                @endforeach
                <div class="flex items-center justify-between p-3 rounded-xl bg-primary/5 border border-primary/10 text-sm">
                    <b>إجمالي القطع</b>
                    <b class="text-primary">{{ money($serviceRequest->partsTotal()) }}</b>
                </div>
            </div>
        </div>
    @endif

    {{-- ملاحظة الإدارة --}}
    @if ($serviceRequest->admin_notes)
        <div class="card p-5 mb-4">
            <h2 class="font-bold mb-3 flex items-center gap-2">{!! icon('info', 'h-4 w-4 text-primary') !!} ملاحظة من المركز</h2>
            <p class="text-sm text-muted-foreground leading-relaxed">{{ $serviceRequest->admin_notes }}</p>
        </div>
    @endif

    {{-- التقييم --}}
    @if ($serviceRequest->status === 'COMPLETED')
        <div class="card p-5 md:p-6">
            @if ($serviceRequest->review)
                <h2 class="font-bold mb-4 flex items-center gap-2">{!! icon('star', 'h-4 w-4 text-primary') !!} تقييمك</h2>
                <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                    <div class="flex gap-1 text-amber-500">
                        @for ($i = 1; $i <= 5; $i++)
                            {!! icon('star', 'h-5 w-5'.($i <= $serviceRequest->review->rating ? ' fill-amber-500 text-amber-500' : '')) !!}
                        @endfor
                    </div>
                    <span class="badge {{ $serviceRequest->review->statusColor() }}">{{ $serviceRequest->review->statusLabel() }}</span>
                </div>
                <p class="text-sm text-muted-foreground leading-relaxed">{{ $serviceRequest->review->comment }}</p>
                @if ($serviceRequest->review->admin_reply)
                    <div class="mt-3 p-3 rounded-xl bg-primary/5 border border-primary/10">
                        <div class="text-[10px] font-bold text-primary mb-0.5">رد مركز نور</div>
                        <p class="text-xs text-muted-foreground leading-relaxed">{{ $serviceRequest->review->admin_reply }}</p>
                    </div>
                @endif
            @else
                <h2 class="font-bold mb-1 flex items-center gap-2">{!! icon('star', 'h-4 w-4 text-primary') !!} قيّم خدمتك</h2>
                <p class="text-muted-foreground text-xs mb-4">رأيك بيساعدنا نحسّن الخدمة — وساعد غيرك يختار صح</p>
                <form method="POST" action="{{ route('requests.review', $serviceRequest) }}" x-data="{ rating: 0, hover: 0 }" class="space-y-4">
                    @csrf
                    <div class="flex gap-1.5" dir="ltr" style="justify-content: flex-end">
                        @for ($i = 1; $i <= 5; $i++)
                            <button type="button" @click="rating = {{ $i }}" @mouseenter="hover = {{ $i }}" @mouseleave="hover = 0" class="cursor-pointer p-1 transition-transform hover:scale-110">
                                @php $active = old('rating') ? old('rating') >= $i : false; @endphp
                                <span :class="(hover || rating) >= {{ $i }} ? 'text-amber-500 fill-amber-500 scale-110' : 'text-muted-foreground'" class="inline-block transition-all">
                                    {!! icon('star', 'h-7 w-7') !!}
                                </span>
                            </button>
                        @endfor
                    </div>
                    <input type="hidden" name="rating" :value="rating">
                    @error('rating')<p class="text-destructive text-xs">{{ $message }}</p>@enderror
                    <textarea name="comment" rows="3" placeholder="إزاي كانت تجربتك مع الخدمة؟" class="input {{ $errors->has('comment') ? 'input-error' : '' }}">{{ old('comment') }}</textarea>
                    @error('comment')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
                    <button type="submit" class="btn btn-primary" onclick="if(!this.form.querySelector('[name=rating]').value) { event.preventDefault(); alert('اختر عدد النجوم الأول'); }">
                        {!! icon('star', 'h-4 w-4') !!}
                        إرسال التقييم
                    </button>
                </form>
            @endif
        </div>
    @endif
</div>
@endsection
