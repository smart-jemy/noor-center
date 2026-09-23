@extends('layouts.app')
@section('title', 'طلب '.$serviceRequest->order_number.' — قسم '.$dept->name)

@section('content')
<div class="container mx-auto max-w-4xl px-4 sm:px-6 py-8">
    <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
        <div>
            <a href="{{ route('department.requests') }}" class="text-xs text-muted-foreground hover:text-primary mb-1 inline-flex items-center gap-1">
                {!! icon('arrow-right', 'h-3 w-3') !!} رجوع لطلبات القسم
            </a>
            <h1 class="text-2xl font-extrabold flex items-center gap-2 flex-wrap">
                {{ $serviceRequest->customer->name }}
                <span class="text-muted-foreground text-lg font-normal">— {{ $serviceRequest->device_type }}{{ $serviceRequest->brand ? ' '.$serviceRequest->brand : '' }}</span>
            </h1>
            <p class="text-muted-foreground text-sm mt-0.5"><span dir="ltr">{{ $serviceRequest->order_number }}</span> • {{ dt($serviceRequest->created_at, true) }}</p>
        </div>
        <span class="badge {{ $serviceRequest->statusColor() }} text-sm px-3 py-1">{{ $serviceRequest->statusLabel() }}</span>
    </div>

    @include('department.partials.tabs')

    <div class="grid lg:grid-cols-3 gap-4">
        {{-- تفاصيل --}}
        <div class="lg:col-span-2 card p-5">
            <h2 class="font-bold mb-4 flex items-center gap-2">{!! icon('file-text', 'h-4 w-4 text-primary') !!} تفاصيل الطلب</h2>
            <div class="grid sm:grid-cols-2 gap-3 text-sm">
                <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">العميل</span><b>{{ $serviceRequest->customer->name }}</b></div>
                <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">هاتف</span><b dir="ltr"><a href="tel:{{ $serviceRequest->customer->phone }}" class="text-primary hover:underline">{{ $serviceRequest->customer->phone }}</a></b></div>
                <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">المنطقة</span><b>{{ $serviceRequest->areaLabel() }}</b></div>
                <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">الميعاد</span><b>{{ dt($serviceRequest->preferred_date) }} — {{ $serviceRequest->timeSlotLabel() }}</b></div>
                <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">الفني المسؤول</span><b>{{ $serviceRequest->assignedTechnician->name ?? '—' }}</b></div>
                @if ($serviceRequest->price !== null)
                    <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">السعر</span><b class="text-primary">{{ money($serviceRequest->price) }}</b></div>
                @endif
                <div class="sm:col-span-2 p-3 rounded-xl bg-muted/40">
                    <div class="text-muted-foreground text-xs mb-1">العنوان</div>
                    <div>{{ $serviceRequest->address }}</div>
                </div>
                <div class="sm:col-span-2 p-3 rounded-xl bg-muted/40">
                    <div class="text-muted-foreground text-xs mb-1">وصف العطل</div>
                    <div class="leading-relaxed">{{ $serviceRequest->issue_description }}</div>
                </div>
                @if ($serviceRequest->admin_notes)
                    <div class="sm:col-span-2 p-3 rounded-xl bg-blue-50 dark:bg-blue-950/40">
                        <div class="text-xs text-blue-700 dark:text-blue-300 mb-1 font-bold">ملاحظة الإدارة</div>
                        <div>{{ $serviceRequest->admin_notes }}</div>
                    </div>
                @endif
            </div>
        </div>

        {{-- القطع المستخدمة --}}
        <div class="card p-5">
            <h2 class="font-bold mb-3 flex items-center gap-2">{!! icon('package', 'h-4 w-4 text-primary') !!} قطع مستخدمة ({{ $serviceRequest->usedParts->count() }})</h2>
            <div class="space-y-2">
                @forelse ($serviceRequest->usedParts as $part)
                    <div class="p-3 rounded-xl bg-muted/40 text-sm flex justify-between">
                        <b>{{ $part->name() }} × {{ $part->quantity }}</b>
                        <span>{{ money($part->total()) }}</span>
                    </div>
                @empty
                    <p class="text-xs text-muted-foreground text-center py-3">لسه مفيش قطع مسجلة</p>
                @endforelse
                @if ($serviceRequest->usedParts->count())
                    <div class="p-3 rounded-xl bg-primary/5 border border-primary/10 text-sm flex justify-between">
                        <b>الإجمالي</b>
                        <b class="text-primary">{{ money($serviceRequest->partsTotal()) }}</b>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
