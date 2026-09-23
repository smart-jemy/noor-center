{{-- لوحة الإنذارات النشطة — من محرك Alerts.php --}}
{{-- $alerts متغير من الكنترولر --}}
<div class="card p-5 h-full flex flex-col">
    <div class="flex items-center justify-between mb-3">
        <h2 class="font-extrabold text-sm flex items-center gap-2">
            {!! icon('alert-triangle', 'h-4 w-4 text-destructive') !!}
            الإنذارات النشطة
        </h2>
        @if (count($alerts) > 0)
            <span class="badge bg-red-100 text-red-800 border-red-300 dark:bg-red-950/50 dark:text-red-300 dark:border-red-800">
                {{ array_sum(array_column($alerts, 'count')) }} حالة
            </span>
        @endif
    </div>

    @if (count($alerts) === 0)
        <div class="flex-1 flex flex-col items-center justify-center py-8 text-center">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-green-100 dark:bg-green-950/40 text-green-600 dark:text-green-400 mb-3">
                {!! icon('check-circle', 'h-7 w-7') !!}
            </div>
            <p class="font-bold text-sm">كل الأنظمة مستقرة</p>
            <p class="text-xs text-muted-foreground mt-1">مفيش أي إنذارات نشطة دلوقتي</p>
        </div>
    @else
        <div class="space-y-2 overflow-y-auto flex-1 max-h-[340px] pl-0.5">
            @foreach ($alerts as $alert)
                <a href="{{ $alert['url'] }}" class="alert-item alert-sev-{{ $alert['severity'] }}">
                    <div class="alert-count">{{ $alert['count'] }}</div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[13px] font-extrabold leading-tight">{{ $alert['label'] }}</p>
                        <p class="text-[10px] text-muted-foreground leading-snug mt-0.5">{{ $alert['hint'] }}</p>
                    </div>
                    <span class="alert-sev-dot shrink-0"></span>
                </a>
            @endforeach
        </div>
    @endif
</div>
