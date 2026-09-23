@extends('admin.layout')
@section('title', 'الإشعارات — لوحة التحكم')
@section('admin_title', 'الإشعارات')
@section('admin_subtitle', 'آخر تحديثات النظام')

@section('admin_actions')
    @if ($notifications->where('is_read', false)->count() > 0 || $unreadBadge > 0)
        <form method="POST" action="{{ route('admin.notifications.read') }}">
            @csrf
            <button class="btn btn-outline btn-sm">{!! icon('check-circle', 'h-3.5 w-3.5') !!} تعليم الكل كمقروء</button>
        </form>
    @endif
@endsection

@section('admin_content')
<div class="space-y-2">
    @forelse ($notifications as $n)
        <a href="{{ $n->request ? route('admin.requests.show', $n->request) : '#' }}"
           class="block card p-4 {{ ! $n->is_read ? 'border-primary/40 bg-primary/5' : '' }} card-hover">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl shrink-0 {{ $n->is_read ? 'bg-muted text-muted-foreground' : 'bg-primary/10 text-primary' }}">
                    {!! icon($n->icon(), 'h-5 w-5') !!}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <b class="text-sm">{{ $n->title }}</b>
                        @if (! $n->is_read)
                            <span class="badge bg-red-500 text-white border-red-500 text-[9px]">جديد</span>
                        @endif
                    </div>
                    <p class="text-sm text-muted-foreground leading-relaxed mt-0.5">{{ $n->message }}</p>
                    <div class="text-[10px] text-muted-foreground mt-1">{{ $n->timeAgo() }}</div>
                </div>
                @if ($n->request)
                    <span class="badge bg-muted text-muted-foreground border-border text-[10px] shrink-0" dir="ltr">{{ $n->request->order_number }}</span>
                @endif
            </div>
        </a>
    @empty
        <div class="card p-10 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-muted text-muted-foreground">
                {!! icon('bell', 'h-7 w-7') !!}
            </div>
            <h3 class="font-bold mb-1">مفيش إشعارات</h3>
            <p class="text-muted-foreground text-sm">هتلاقي هنا كل الجديد: طلبات، تقييمات، تحديثات حالات</p>
        </div>
    @endforelse
</div>

{{ $notifications->links() }}
@endsection
