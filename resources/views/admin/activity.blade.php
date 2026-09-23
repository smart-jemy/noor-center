@extends('admin.layout')
@section('title', 'سجل النشاط — لوحة التحكم')
@section('admin_title', 'سجل النشاط')
@section('admin_subtitle', 'كل عملية تمت على النظام — من عملها ومتى')

@section('admin_content')
<div class="space-y-2">
    @forelse ($logs as $log)
        <div class="card p-4 flex items-start gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl {{ $log->actionColor() }} shrink-0">
                {!! icon('user', 'h-5 w-5') !!}
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap mb-0.5">
                    <b class="text-sm">{{ $log->user?->name ?? 'نظام' }}</b>
                    <span class="badge {{ $log->actionColor() }} text-[10px]">{{ $log->actionLabel() }}</span>
                </div>
                <p class="text-sm text-muted-foreground leading-relaxed">{{ $log->details }}</p>
                <div class="text-[10px] text-muted-foreground mt-1">
                    {{ dt($log->created_at, true) }}
                    @if ($log->ip_address) • <span dir="ltr">{{ $log->ip_address }}</span> @endif
                </div>
            </div>
        </div>
    @empty
        <div class="card p-10 text-center">
            <p class="text-muted-foreground text-sm">مفيش نشاط مسجل بعد</p>
        </div>
    @endforelse
</div>

{{ $logs->links() }}
@endsection
