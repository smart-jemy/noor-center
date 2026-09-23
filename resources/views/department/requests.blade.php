@extends('layouts.app')
@section('title', 'طلبات قسم '.$dept->name)

@section('content')
<div class="container mx-auto max-w-6xl px-4 sm:px-6 py-8">
    <div class="flex items-center gap-3 mb-6">
        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary shrink-0">
            {!! icon('clipboard-list', 'h-6 w-6') !!}
        </div>
        <div>
            <h1 class="text-2xl font-extrabold mb-0.5">طلبات قسم {{ $dept->name }}</h1>
            <p class="text-muted-foreground text-sm">{{ $requests->total() }} طلب — كل طلبات قسمك فقط</p>
        </div>
    </div>

    @include('department.partials.tabs')

    {{-- البحث والفلاتر — شريط أدوات حديث --}}
    <form method="GET" action="{{ route('department.requests') }}" class="toolbar p-3 mb-4">
        <div class="flex flex-wrap gap-2.5 items-center">
            <div class="relative flex-1 min-w-[180px] max-w-md">
                {!! icon('search', 'h-4 w-4 absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none') !!}
                <input name="q" value="{{ request('q') }}" placeholder="بحث برقم الطلب / الاسم / الهاتف / الجهاز..." class="input pr-10 h-10" autocomplete="off">
            </div>
            <select name="status" class="input w-auto h-10">
                <option value="">كل الحالات</option>
                @foreach (\App\Models\ServiceRequest::STATUSES as $status)
                    <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ \App\Models\ServiceRequest::STATUS_LABELS[$status] }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-outline btn-sm h-10">{!! icon('filter', 'h-4 w-4') !!} فلترة</button>
            @if (request()->hasAny(['q', 'status']))
                <a href="{{ route('department.requests') }}" class="btn btn-ghost btn-sm h-10">مسح</a>
            @endif
        </div>
    </form>

    {{-- قائمة الطلبات --}}
    <div class="space-y-2 stagger">
        @forelse ($requests as $r)
            @php
                $sideColor = $r->isOverdue() ? 'border-r-red-500' : match($r->status) {
                    'PENDING' => 'border-r-amber-400',
                    'CONTACTED' => 'border-r-cyan-400',
                    'CONFIRMED' => 'border-r-blue-400',
                    'IN_PROGRESS' => 'border-r-purple-400',
                    'READY' => 'border-r-teal-400',
                    'RETURNED' => 'border-r-orange-400',
                    'COMPLETED' => 'border-r-green-400',
                    'CANCELLED' => 'border-r-gray-300',
                    default => 'border-r-border',
                };
            @endphp
            <a href="{{ route('department.show', $r) }}" class="req-card block border-r-4 {{ $sideColor }} p-3.5">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary shrink-0">
                            {!! device_icon($r->device_type, 'h-5 w-5') !!}
                        </div>
                        <div class="min-w-0">
                            <div class="font-bold text-sm truncate">
                                {{ $r->customer->name }}
                                <span class="text-muted-foreground font-normal">— {{ $r->device_type }}{{ $r->brand ? ' '.$r->brand : '' }}</span>
                            </div>
                            <div class="text-[11px] text-muted-foreground mt-0.5 flex items-center flex-wrap gap-x-2">
                                <span class="font-mono" dir="ltr">{{ $r->order_number }}</span>
                                <span>•</span>
                                <span>{{ $r->areaLabel() }}</span>
                                <span>•</span>
                                <span dir="ltr">{{ $r->customer->phone }}</span>
                                @if ($r->assignedTechnician)<span>•</span><span class="text-primary font-medium">{{ $r->assignedTechnician->name }}</span>@endif
                                <span>•</span>
                                <span class="text-muted-foreground/80">{{ $r->timeAgo() }}</span>
                                @if ($r->isOverdue())<span class="text-red-600 dark:text-red-400 font-bold">• متأخر!</span>@endif
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        @if ($r->price)<span class="text-sm font-extrabold text-primary">{{ money($r->price) }}</span>@endif
                        <span class="badge {{ $r->statusColor() }}"><span class="h-1.5 w-1.5 rounded-full {{ $r->statusDot() }}"></span>{{ $r->statusLabel() }}</span>
                        {!! icon('chevron-left', 'h-4 w-4 text-muted-foreground/60') !!}
                    </div>
                </div>
            </a>
        @empty
            <div class="card p-10 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-muted text-muted-foreground">
                    {!! icon('clipboard-list', 'h-7 w-7') !!}
                </div>
                <h3 class="font-bold mb-1">مفيش طلبات مطابقة</h3>
                <p class="text-muted-foreground text-sm">جرب تغير الفلتر أو البحث بكلمة تانية</p>
            </div>
        @endforelse
    </div>

    {{ $requests->links() }}
</div>
@endsection
