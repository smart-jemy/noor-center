{{-- سجل النشاط المباشر — من activity_logs --}}
{{-- $activity متغير من الكنترولر (المدير: آخر 100 حركة — الاستقبال: آخر 50) --}}
@php
    $feedColors = [
        'REQUEST_UPDATED' => 'bg-blue-500',
        'REQUEST_RETURNED' => 'bg-orange-500',
        'REQUEST_ASSIGNED' => 'bg-purple-500',
        'NEW_REQUEST' => 'bg-primary',
        'INVENTORY_ADDED' => 'bg-cyan-500',
        'COMPLETED' => 'bg-green-500',
    ];
    $feedIcons = [
        'REQUEST_UPDATED' => 'edit',
        'REQUEST_RETURNED' => 'undo',
        'REQUEST_ASSIGNED' => 'user-cog',
        'NEW_REQUEST' => 'package-plus',
        'INVENTORY_ADDED' => 'package',
    ];
    // مساحة التمرير بتكبر مع عدد الحركات (100 للمدير / 50 للاستقبال)
    $feedMaxH = $activity->count() > 20 ? 'max-h-[620px]' : 'max-h-[340px]';
@endphp
<div class="card p-5 h-full flex flex-col">
    <div class="flex items-center justify-between mb-3">
        <h2 class="font-extrabold text-sm flex items-center gap-2">
            <span class="live-dot"></span>
            النشاط المباشر
        </h2>
        <span class="text-[10px] font-bold text-muted-foreground bg-muted/60 rounded-full px-2 py-0.5">آخر {{ $activity->count() }} حركة</span>
    </div>

    @if ($activity->isEmpty())
        <p class="text-sm text-muted-foreground text-center py-8 flex-1">لسه مفيش حركة مسجلة</p>
    @else
        <div class="overflow-y-auto flex-1 {{ $feedMaxH }} space-y-0.5">
            @foreach ($activity as $log)
                <div class="feed-item">
                    <span class="feed-dot {{ $feedColors[$log->action] ?? 'bg-muted-foreground/50' }}"></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold leading-snug">{{ $log->details }}</p>
                        <p class="text-[10px] text-muted-foreground mt-0.5">
                            {{ $log->user?->name ?? 'النظام' }}
                            @if ($log->user?->roleLabel()) · {{ $log->user->roleLabel() }} @endif
                        </p>
                    </div>
                    <span class="feed-time">{{ $log->created_at->format('H:i') }}</span>
                </div>
            @endforeach
        </div>
    @endif
</div>
