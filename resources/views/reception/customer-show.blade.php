@extends('layouts.app')
@section('title', $user->name.' — ملف العميل')

@section('content')
<div class="container mx-auto max-w-4xl px-4 sm:px-6 py-8">
    <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
        <div>
            <a href="{{ route('reception.customers') }}" class="text-xs text-muted-foreground hover:text-primary mb-1 inline-flex items-center gap-1">
                {!! icon('arrow-right', 'h-3 w-3') !!} رجوع للعملاء
            </a>
            <h1 class="text-2xl font-extrabold flex items-center gap-3 flex-wrap">
                <span class="flex h-11 w-11 items-center justify-center rounded-full bg-primary/10 text-primary text-lg font-bold">{{ mb_substr($user->name, 0, 1) }}</span>
                {{ $user->name }}
            </h1>
            <p class="text-muted-foreground text-sm mt-1">
                <span dir="ltr"><a href="tel:{{ $user->phone }}" class="text-primary hover:underline">{{ $user->phone }}</a></span>
                • عميل منذ {{ dt($user->created_at) }}
            </p>
        </div>
    </div>

    @include('reception.partials.tabs')

    {{-- ملخص العميل --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <div class="card p-4 text-center card-hover">
            <div class="text-2xl font-extrabold text-primary">{{ $user->requests_count }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">إجمالي الطلبات</div>
        </div>
        <div class="card p-4 text-center card-hover">
            <div class="text-2xl font-extrabold text-green-600 dark:text-green-400">{{ money($user->total_spent) }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">إجمالي الإنفاق</div>
        </div>
        <div class="card p-4 text-center card-hover">
            <div class="text-2xl font-extrabold text-amber-600 dark:text-amber-400">{{ $user->loyalty_points }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">نقاط الولاء</div>
        </div>
        <div class="card p-4 text-center card-hover">
            <div class="text-2xl font-extrabold text-purple-600 dark:text-purple-400">{{ $completedCount }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">طلبات مكتملة</div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-4">
        {{-- سجل الطلبات --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="card p-5">
                <h2 class="font-bold mb-4 flex items-center gap-2">{!! icon('clipboard-list', 'h-4 w-4 text-primary') !!} سجل الطلبات</h2>
                <div class="space-y-2">
                    @forelse ($requests as $r)
                        <a href="{{ route('reception.show', $r) }}" class="block p-3 rounded-xl bg-muted/40 hover:bg-muted/70 transition-colors">
                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <div class="text-sm">
                                    <b>{{ $r->device_type }}</b>{{ $r->brand ? ' <span class="text-muted-foreground">'.$r->brand.'</span>' : '' }}
                                    <span class="text-xs text-muted-foreground block mt-0.5">
                                        <span dir="ltr">{{ $r->order_number }}</span> • {{ dt($r->created_at) }}
                                        @if ($r->assignedTechnician) • فني: {{ $r->assignedTechnician->name }} @endif
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if ($r->price)<span class="text-sm font-bold text-primary">{{ money($r->price) }}</span>@endif
                                    <span class="badge {{ $r->statusColor() }}">{{ $r->statusLabel() }}</span>
                                </div>
                            </div>
                        </a>
                    @empty
                        <p class="text-sm text-muted-foreground text-center py-6">العميل ده مقدمش طلبات لسه</p>
                    @endforelse
                </div>
                {{ $requests->links() }}
            </div>
        </div>

        {{-- ملاحظات العميل --}}
        <div>
            <div class="card p-5" x-data="{ adding: false }">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-bold flex items-center gap-2">{!! icon('message', 'h-4 w-4 text-primary') !!} ملاحظات عن العميل</h2>
                    <button @click="adding = !adding" class="btn btn-outline btn-sm">{!! icon('plus', 'h-3.5 w-3.5') !!}</button>
                </div>

                <form x-show="adding" x-transition method="POST" action="{{ route('reception.customers.notes', $user) }}" class="mb-3 p-3 rounded-xl bg-muted/30 border border-border/60 space-y-2" style="display:none">
                    @csrf
                    <textarea name="content" rows="3" placeholder="ملاحظة داخلية عن العميل (تظهر للطاقم فقط)..." class="input" required></textarea>
                    <button type="submit" class="btn btn-primary btn-sm w-full">{!! icon('save', 'h-3.5 w-3.5') !!} حفظ</button>
                </form>

                @forelse ($notes as $note)
                    <div class="p-3 rounded-xl bg-muted/40 mb-2">
                        <div class="flex items-center gap-2 text-xs mb-1">
                            <b>{{ $note->author?->name }}</b>
                            <span class="text-muted-foreground">{{ dt($note->created_at, true) }}</span>
                        </div>
                        <p class="text-sm leading-relaxed">{{ $note->content }}</p>
                    </div>
                @empty
                    <p class="text-sm text-muted-foreground text-center py-4">مفيش ملاحظات عن العميل ده</p>
                @endforelse
            </div>

            {{-- إجراء سريع --}}
            <div class="card p-5 text-center">
                <p class="text-xs text-muted-foreground mb-3">تسجيل جهاز جديد للعميل ده</p>
                <a href="{{ route('reception.index') }}" class="btn btn-primary w-full">
                    {!! icon('plus', 'h-4 w-4') !!}
                    تسجيل جهاز واصل
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
