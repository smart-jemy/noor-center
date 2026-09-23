@extends('layouts.app')
@section('title', 'العملاء — استقبال')

@section('content')
<div class="container mx-auto max-w-6xl px-4 sm:px-6 py-8">
    <div class="flex items-center gap-3 mb-6">
        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary shrink-0">
            {!! icon('users', 'h-6 w-6') !!}
        </div>
        <div>
            <h1 class="text-2xl font-extrabold mb-0.5">العملاء</h1>
            <p class="text-muted-foreground text-sm">بحث وسجل أي عميل — تفادي التكرار عند التسجيل</p>
        </div>
    </div>

    @include('reception.partials.tabs')

    {{-- إحصائيات — KPI حديث --}}
    <div class="grid grid-cols-2 gap-2.5 mb-4 stagger">
        <div class="kpi" style="--kpi-accent: var(--color-primary)">
            <div class="flex items-center justify-between">
                <div>
                    <div class="kpi-value">{{ $stats['total'] }}</div>
                    <div class="kpi-label">إجمالي العملاء</div>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10 text-primary shrink-0">{!! icon('users', 'h-4 w-4') !!}</div>
            </div>
        </div>
        <div class="kpi" style="--kpi-accent: var(--color-green-500)">
            <div class="flex items-center justify-between">
                <div>
                    <div class="kpi-value">{{ $stats['newThisMonth'] }}</div>
                    <div class="kpi-label">عملاء جدد الشهر ده</div>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-green-500/10 text-green-500 shrink-0">{!! icon('user-plus', 'h-4 w-4') !!}</div>
            </div>
        </div>
    </div>

    {{-- بحث — شريط أدوات حديث --}}
    <form method="GET" class="toolbar p-3 mb-4">
        <div class="flex flex-wrap gap-2.5 items-center">
            <div class="relative flex-1 min-w-[180px] max-w-md">
                {!! icon('search', 'h-4 w-4 absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none') !!}
                <input name="q" value="{{ request('q') }}" placeholder="بحث بالاسم أو رقم الموبايل..." class="input pr-10 h-10" autocomplete="off">
            </div>
            <button type="submit" class="btn btn-outline btn-sm h-10">{!! icon('search', 'h-4 w-4') !!} بحث</button>
            @if (request('q'))
                <a href="{{ route('reception.customers') }}" class="btn btn-ghost btn-sm h-10">مسح</a>
            @endif
            <span class="text-xs text-muted-foreground mr-auto font-medium">{{ $customers->total() }} عميل</span>
        </div>
    </form>

    {{-- قائمة العملاء (زي الأصل: كروت بحرف الاسم) --}}
    <div class="space-y-1.5 stagger">
        @forelse ($customers as $c)
            <a href="{{ route('reception.customers.show', $c) }}" class="req-card block p-3 border-r-4 border-r-primary/40 hover:border-r-primary">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10 text-primary text-sm font-bold shrink-0">
                        {{ mb_substr($c->name, 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="font-bold text-sm truncate">{{ $c->name }}</p>
                            <span class="text-xs text-muted-foreground" dir="ltr">{{ $c->phone }}</span>
                        </div>
                        <p class="text-xs text-muted-foreground mt-0.5">
                            {{ $c->requests_count }} طلب • سُجّل: {{ dt($c->created_at) }}
                            @if ($c->total_spent > 0) • أنفق {{ money($c->total_spent) }} @endif
                        </p>
                    </div>
                    {!! icon('chevron-left', 'h-4 w-4 text-muted-foreground shrink-0') !!}
                </div>
            </a>
        @empty
            <div class="card p-10 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-muted text-muted-foreground">
                    {!! icon('users', 'h-7 w-7') !!}
                </div>
                <h3 class="font-bold mb-1">مفيش عملاء مطابقين</h3>
                <p class="text-muted-foreground text-sm">جرب كلمة بحث تانية</p>
            </div>
        @endforelse
    </div>

    {{ $customers->links() }}
</div>
@endsection
