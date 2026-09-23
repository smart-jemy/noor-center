@extends('layouts.app')
@section('title', 'عملاء قسم '.$dept->name)

@section('content')
<div class="container mx-auto max-w-6xl px-4 sm:px-6 py-8">
    <div class="flex items-center gap-3 mb-6">
        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary shrink-0">
            {!! icon('users', 'h-6 w-6') !!}
        </div>
        <div>
            <h1 class="text-2xl font-extrabold mb-0.5">عملاء القسم</h1>
            <p class="text-muted-foreground text-sm">{{ $customers->total() }} عميل — اللي قدّموا طلبات لقسمك</p>
        </div>
    </div>

    @include('department.partials.tabs')

    {{-- بحث --}}
    <form method="GET" class="card p-4 mb-4">
        <div class="flex flex-wrap gap-3 items-center">
            <div class="relative flex-1 min-w-[200px]">
                {!! icon('search', 'h-4 w-4 absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground') !!}
                <input name="q" value="{{ request('q') }}" placeholder="بحث بالاسم أو رقم الموبايل..." class="input pr-10">
            </div>
            <button type="submit" class="btn btn-outline">{!! icon('search', 'h-4 w-4') !!} بحث</button>
            @if (request('q'))
                <a href="{{ route('department.customers') }}" class="btn btn-ghost btn-sm">مسح</a>
            @endif
        </div>
    </form>

    {{-- قائمة العملاء --}}
    <div class="space-y-1.5">
        @forelse ($customers as $c)
            <div class="card p-3 fade-in-up border-r-4 border-r-primary/40">
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
                            {{ $c->dept_requests_count }} طلب لقسمك • سُجّل: {{ dt($c->created_at) }}
                            @if ($c->total_spent > 0) • أنفق {{ money($c->total_spent) }} @endif
                        </p>
                    </div>
                    <a href="tel:{{ $c->phone }}" class="btn btn-outline btn-sm shrink-0">{!! icon('phone', 'h-3.5 w-3.5') !!}</a>
                </div>
            </div>
        @empty
            <div class="card p-10 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-muted text-muted-foreground">
                    {!! icon('users', 'h-7 w-7') !!}
                </div>
                <h3 class="font-bold mb-1">مفيش عملاء لقسمك لسه</h3>
                <p class="text-muted-foreground text-sm">أول ما ييجي طلب لقسمك هيظهر العميل هنا</p>
            </div>
        @endforelse
    </div>

    {{ $customers->links() }}
</div>
@endsection
