@extends('layouts.app')
@section('title', 'طلباتي')

@section('content')
<div class="container mx-auto max-w-4xl px-4 sm:px-6 py-8">
    <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-extrabold mb-1">طلباتي</h1>
            <p class="text-muted-foreground text-sm">كل طلبات الصيانة بتاعتك في مكان واحد</p>
        </div>
        <a href="{{ route('requests.create') }}" class="btn btn-primary">
            {!! icon('plus', 'h-4 w-4') !!}
            طلب جديد
        </a>
    </div>

    {{-- إحصائيات سريعة — KPI حديث --}}
    <div class="grid grid-cols-4 gap-2.5 mb-5 stagger">
        <a href="{{ route('requests.index') }}" class="kpi !p-3.5 {{ ! request('status') ? 'selected' : '' }}" style="--kpi-accent: var(--color-primary)">
            <div class="kpi-value">{{ $stats['total'] }}</div>
            <div class="kpi-label">إجمالي الطلبات</div>
        </a>
        <a href="{{ route('requests.index', ['status' => 'PENDING']) }}" class="kpi !p-3.5 {{ request('status') === 'PENDING' ? 'selected' : '' }}" style="--kpi-accent: var(--color-amber-500)">
            <div class="kpi-value">{{ $stats['pending'] }}</div>
            <div class="kpi-label">كشف / تواصل</div>
        </a>
        <a href="{{ route('requests.index', ['status' => 'IN_PROGRESS']) }}" class="kpi !p-3.5 {{ request('status') === 'IN_PROGRESS' ? 'selected' : '' }}" style="--kpi-accent: var(--color-purple-500)">
            <div class="kpi-value">{{ $stats['inProgress'] }}</div>
            <div class="kpi-label">تنفيذ / جاهز</div>
        </a>
        <a href="{{ route('requests.index', ['status' => 'COMPLETED']) }}" class="kpi !p-3.5 {{ request('status') === 'COMPLETED' ? 'selected' : '' }}" style="--kpi-accent: var(--color-green-500)">
            <div class="kpi-value">{{ $stats['completed'] }}</div>
            <div class="kpi-label">تم التسليم</div>
        </a>
    </div>

    {{-- فلاتر الحالة --}}
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('requests.index') }}" class="badge {{ ! request('status') ? 'bg-primary/10 text-primary border-primary/20' : 'bg-card text-muted-foreground border-border' }} px-3.5 py-1.5 cursor-pointer">الكل</a>
        @foreach (\App\Models\ServiceRequest::STATUSES as $status)
            <a href="{{ route('requests.index', ['status' => $status]) }}"
               class="badge {{ request('status') === $status ? \App\Models\ServiceRequest::STATUS_COLORS[$status] : 'bg-card text-muted-foreground border-border' }} px-3.5 py-1.5 cursor-pointer">
                {{ \App\Models\ServiceRequest::STATUS_LABELS[$status] }}
            </a>
        @endforeach
    </div>

    {{-- القائمة — تفاصيل كاملة في الكارت زي الأصل --}}
    @forelse ($requests as $r)
        <a href="{{ route('requests.show', $r) }}" class="req-card block p-4 md:p-5 mb-3 border-r-4 {{ match($r->status) {
            'PENDING' => 'border-r-amber-400',
            'CONTACTED' => 'border-r-cyan-400',
            'CONFIRMED' => 'border-r-blue-400',
            'IN_PROGRESS' => 'border-r-purple-400',
            'READY' => 'border-r-teal-400',
            'RETURNED' => 'border-r-orange-400',
            'COMPLETED' => 'border-r-green-400',
            'CANCELLED' => 'border-r-gray-300',
            default => 'border-r-border',
        } }}">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary shrink-0">
                        {!! device_icon($r->device_type, 'h-5 w-5') !!}
                    </div>
                    <div>
                        <div class="font-bold text-sm">
                            {{ $r->device_type }}
                            @if ($r->brand)<span class="text-muted-foreground font-normal">— {{ $r->brand }}</span>@endif
                        </div>
                        <div class="text-xs text-muted-foreground mt-0.5">
                            <span dir="ltr">{{ $r->order_number }}</span> • {{ dt($r->created_at) }}
                            @if ($r->assignedTechnician && in_array($r->status, ['CONFIRMED', 'IN_PROGRESS', 'READY', 'COMPLETED']))
                                • الفني: {{ $r->assignedTechnician->name }}
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if ($r->price)
                        <span class="text-sm font-extrabold text-primary">السعر: {{ money($r->price) }}</span>
                    @endif
                    @if ($r->hasWarranty() && $r->warrantyActive())
                        <span class="badge bg-green-100 dark:bg-green-950/40 text-green-700 dark:text-green-300 border-green-200 dark:border-green-900" title="ضمان ساري حتى {{ dt($r->warranty_end_date) }}">
                            {!! icon('shield-check', 'h-3 w-3') !!} ضمان
                        </span>
                    @endif
                    <span class="badge {{ $r->statusColor() }}">{{ $r->statusLabel() }}</span>
                </div>
            </div>
            {{-- تفاصيل إضافية زي الأصل: وصف العطل + المنطقة + الميعاد + عدد الصور --}}
            <div class="mt-3 pt-3 border-t border-border/60 space-y-1.5">
                <p class="text-xs text-muted-foreground leading-relaxed line-clamp-2">{{ \Illuminate\Support\Str::limit($r->issue_description, 120) }}</p>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-muted-foreground">
                    <span class="flex items-center gap-1">{!! icon('map-pin', 'h-3 w-3') !!} {{ $r->areaLabel() }}</span>
                    <span class="flex items-center gap-1">{!! icon('calendar', 'h-3 w-3') !!} {{ dt($r->preferred_date) }} — {{ $r->timeSlotLabel() }}</span>
                    @if ($r->admin_notes)
                        <span class="flex items-center gap-1">{!! icon('message', 'h-3 w-3') !!} ملاحظات من الفني</span>
                    @endif
                    @if (count($r->photos ?? []))
                        <span class="flex items-center gap-1">{!! icon('camera', 'h-3 w-3') !!} {{ count($r->photos) }} صور</span>
                    @endif
                    @if ($r->hasWarranty())
                        <span class="flex items-center gap-1 text-green-700 dark:text-green-400">{!! icon('shield-check', 'h-3 w-3') !!} ضمان {{ $r->warranty_months }} شهر</span>
                    @endif
                </div>
            </div>
        </a>
    @empty
        <div class="card p-10 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-muted text-muted-foreground">
                {!! icon('clipboard-list', 'h-7 w-7') !!}
            </div>
            <h3 class="font-bold mb-1">لسه مفيش طلبات</h3>
            <p class="text-muted-foreground text-sm mb-4">اطلب أول صيانة وهيظهر هنا مع حالتها لحظة بلحظة</p>
            <a href="{{ route('requests.create') }}" class="btn btn-primary">{!! icon('wrench', 'h-4 w-4') !!} اطلب صيانة الآن</a>
        </div>
    @endforelse

    {{ $requests->links() }}
</div>
@endsection
