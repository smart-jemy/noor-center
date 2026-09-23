@extends('layouts.app')
@section('title', 'الإشعارات — قسم '.($dept->name ?? ''))

@section('content')
<div class="container mx-auto max-w-3xl px-4 sm:px-6 py-8">
    <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
        <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary shrink-0">
                {!! icon('bell', 'h-6 w-6') !!}
            </div>
            <div>
                <h1 class="text-2xl font-extrabold mb-0.5">الإشعارات</h1>
                <p class="text-muted-foreground text-sm">{{ $notifications->total() }} إشعار — {{ $unreadBadge }} غير مقروء</p>
            </div>
        </div>
        @if ($unreadBadge > 0)
            <form method="POST" action="{{ route('department.notifications.read') }}">
                @csrf
                <button class="btn btn-outline">{!! icon('check', 'h-4 w-4') !!} تعليم الكل كمقروء</button>
            </form>
        @endif
    </div>

    @include('department.partials.tabs')

    <div class="space-y-2">
        @forelse ($notifications as $n)
            <div class="card p-4 {{ $n->is_read ? '' : 'border-primary/40 bg-primary/5' }}">
                <div class="flex items-start gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl shrink-0 bg-muted text-muted-foreground">
                        {!! icon('bell', 'h-4 w-4') !!}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <p class="font-bold text-sm">{{ $n->title }}</p>
                            <span class="text-[10px] text-muted-foreground">{{ dt($n->created_at, true) }}</span>
                        </div>
                        <p class="text-sm text-muted-foreground mt-0.5 leading-relaxed">{{ $n->message }}</p>
                        @if ($n->request_id && $n->request && $n->request->department_id === auth()->user()->department_id)
                            <a href="{{ route('department.show', $n->request_id) }}" class="text-xs text-primary hover:underline mt-1.5 inline-flex items-center gap-1">
                                فتح الطلب {!! icon('chevron-left', 'h-3 w-3') !!}
                            </a>
                        @endif
                    </div>
                    @if (! $n->is_read)
                        <span class="h-2 w-2 rounded-full bg-primary shrink-0 mt-1.5"></span>
                    @endif
                </div>
            </div>
        @empty
            <div class="card p-10 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-muted text-muted-foreground">
                    {!! icon('bell', 'h-7 w-7') !!}
                </div>
                <h3 class="font-bold mb-1">مفيش إشعارات</h3>
                <p class="text-muted-foreground text-sm">كل جديد هيظهر هنا</p>
            </div>
        @endforelse
    </div>

    {{ $notifications->links() }}
</div>
@endsection
