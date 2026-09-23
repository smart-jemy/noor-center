@extends('admin.layout')
@section('title', 'أداء الفنيين — لوحة التحكم')
@section('admin_title', 'أداء الفنيين')
@section('admin_subtitle', 'إحصائيات كل فني: الطلبات، الإكمال، التقييم، الإيراد')

@section('admin_content')
<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table-noor min-w-[760px]">
            <thead>
                <tr>
                    <th>الفني</th>
                    <th>القسم</th>
                    <th>المسند له</th>
                    <th>نشط</th>
                    <th>مكتمل</th>
                    <th>نسبة الإكمال</th>
                    <th>متوسط التقييم</th>
                    <th>إيراده</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($technicians as $t)
                    @php $completion = $t->total_assigned > 0 ? round(($t->completed_count / $t->total_assigned) * 100) : 0; @endphp
                    <tr>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-primary to-primary/70 text-primary-foreground text-xs font-bold shrink-0">{{ mb_substr($t->name, 0, 1) }}</div>
                                <div>
                                    <div class="font-bold">{{ $t->name }}</div>
                                    @if ($t->specialty)<div class="text-[10px] text-muted-foreground">{{ $t->specialty }}</div>@endif
                                </div>
                            </div>
                        </td>
                        <td class="text-xs">{{ $t->department?->name ?? '—' }}</td>
                        <td><b>{{ $t->total_assigned }}</b></td>
                        <td>{{ $t->active_count }}</td>
                        <td>{{ $t->completed_count }}</td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="w-16 h-1.5 rounded-full bg-muted overflow-hidden">
                                    <div class="h-full rounded-full {{ $completion >= 70 ? 'bg-green-500' : ($completion >= 40 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ $completion }}%"></div>
                                </div>
                                <span class="text-xs font-bold">{{ $completion }}%</span>
                            </div>
                        </td>
                        <td>
                            @if ($t->avg_rating > 0)
                                <span class="flex items-center gap-1 text-amber-500 text-xs font-bold">
                                    {!! icon('star', 'h-3.5 w-3.5 fill-amber-500 text-amber-500') !!}
                                    {{ number_format($t->avg_rating, 1) }}
                                </span>
                            @else
                                <span class="text-muted-foreground text-xs">—</span>
                            @endif
                        </td>
                        <td class="font-bold text-primary">{{ money($t->revenue) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted-foreground py-8">مفيش فنيين — أضف فنيين من صفحة الموظفين</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- الأعلى إيراداً --}}
@if ($technicians->isNotEmpty())
<div class="card p-5 mt-4">
    <h2 class="font-bold mb-4 text-sm flex items-center gap-2">{!! icon('award', 'h-4 w-4 text-primary') !!} ترتيب الفنيين بالإيراد</h2>
    @php $top = $technicians->sortByDesc('revenue')->take(5); $maxRev = max($top->max('revenue'), 1); @endphp
    <div class="space-y-3">
        @foreach ($top as $i => $t)
            <div>
                <div class="flex justify-between text-xs mb-1">
                    <span><b>{{ $i + 1 }}. {{ $t->name }}</b></span>
                    <b class="text-primary">{{ money($t->revenue) }}</b>
                </div>
                <div class="h-2.5 rounded-full bg-muted overflow-hidden">
                    <div class="h-full rounded-full bg-gradient-to-l from-primary to-primary/60" style="width: {{ ($t->revenue / $maxRev) * 100 }}%"></div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif
@endsection
