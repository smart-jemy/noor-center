@extends('admin.layout')
@section('title', 'أوقات الذروة — لوحة التحكم')
@section('admin_title', 'أوقات الذروة')
@section('admin_subtitle', 'خريطة حرارية لأيام وأوقات الطلبات — تساعدك في توزيع الفنيين')

@section('admin_content')
<div class="card p-5">
    @if ($max > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-separate border-spacing-1 min-w-[520px]">
                <thead>
                    <tr>
                        <th class="text-xs text-muted-foreground font-bold p-2 text-right">اليوم \ الفترة</th>
                        @foreach ($slots as $slotLabel)
                            <th class="text-xs text-muted-foreground font-bold p-2">{{ $slotLabel }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($days as $dayKey => $dayLabel)
                        <tr>
                            <td class="p-2 font-bold text-xs text-right">{{ $dayLabel }}</td>
                            @foreach (array_keys($slots) as $slotKey)
                                @php $count = $grid[$dayKey][$slotKey] ?? 0; $intensity = $max > 0 ? $count / $max : 0; @endphp
                                <td class="p-0">
                                    <div class="rounded-xl h-14 flex flex-col items-center justify-center cursor-default transition-transform hover:scale-105 {{ $count === 0 ? 'bg-muted/40' : '' }}"
                                         style="{{ $count > 0 ? 'background: color-mix(in oklch, var(--primary) '.(15 + $intensity * 85).'%, transparent)' : '' }}"
                                         title="{{ $dayLabel }} — {{ $slots[$slotKey] }}: {{ $count }} طلب">
                                        <span class="text-sm font-extrabold {{ $intensity > 0.5 ? 'text-white' : '' }}" style="{{ $intensity <= 0.5 && $count > 0 ? 'color: var(--primary)' : '' }}">{{ $count }}</span>
                                        @if ($count === $max && $max > 0)
                                            <span class="text-[8px] {{ $intensity > 0.5 ? 'text-white' : '' }}" style="{{ $intensity <= 0.5 ? 'color: var(--primary)' : '' }}">الأعلى</span>
                                        @endif
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-center gap-2 mt-5 text-xs text-muted-foreground">
            <span>أقل</span>
            @for ($i = 1; $i <= 6; $i++)
                <span class="w-6 h-3 rounded" style="background: color-mix(in oklch, var(--primary) {{ 15 + ($i / 6) * 85 }}%, transparent)"></span>
            @endfor
            <span>أعلى ({{ $max }})</span>
        </div>
    @else
        <div class="text-center py-10">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-muted text-muted-foreground">
                {!! icon('activity', 'h-7 w-7') !!}
            </div>
            <h3 class="font-bold mb-1">لسه مفيش بيانات كفاية</h3>
            <p class="text-muted-foreground text-sm">الخريطة بتتعبى تلقائياً مع كل طلب جديد حسب اليوم والفترة المطلوبة</p>
        </div>
    @endif
</div>

@if ($max > 0)
    <div class="card p-4 mt-4 bg-primary/5 border-primary/20">
        <div class="flex items-start gap-2 text-sm">
            {!! icon('info', 'h-4 w-4 text-primary shrink-0 mt-0.5') !!}
            <p class="text-muted-foreground leading-relaxed">
                استخدم الخريطة لتوزيع الفنيين على الأوقات المزدحمة وتجنب تأجيل الطلبات —
                الخلايا الأغمق تعني عدد طلبات أعلى في هذه الفترة.
            </p>
        </div>
    </div>
@endif
@endsection
