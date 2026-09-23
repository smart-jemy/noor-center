@extends('layouts.app')
@section('title', 'التقرير المالي — قسم '.$dept->name)

@section('content')
<div class="container mx-auto max-w-6xl px-4 sm:px-6 py-8">
    <div class="flex items-center gap-3 mb-6">
        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary shrink-0">
            {!! icon('bar-chart', 'h-6 w-6') !!}
        </div>
        <div>
            <h1 class="text-2xl font-extrabold mb-0.5">التقرير المالي</h1>
            <p class="text-muted-foreground text-sm">قسم {{ $dept->name }} — {{ $periodLabel }} ({{ dt($from) }} إلى {{ dt($to) }})</p>
        </div>
    </div>

    @include('department.partials.tabs')

    {{-- اختيار الفترة --}}
    <div class="card p-4 mb-4">
        <div class="flex flex-wrap gap-2">
            @foreach ([['key' => 'today', 'label' => 'اليوم'], ['key' => 'week', 'label' => 'آخر 7 أيام'], ['key' => 'month', 'label' => 'الشهر الحالي']] as $p)
                <a href="{{ route('department.financial', ['period' => $p['key']]) }}"
                   class="btn btn-sm {{ $period === $p['key'] ? 'btn-primary' : 'btn-outline' }}">{{ $p['label'] }}</a>
            @endforeach
        </div>
    </div>

    {{-- الملخص --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
        <div class="card p-5 border-green-200/60 dark:border-green-900/50 bg-green-50/30 dark:bg-green-950/20">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-green-100 dark:bg-green-950/60 shrink-0">
                    {!! icon('wrench', 'h-5 w-5 text-green-600 dark:text-green-400') !!}
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">إيراد الصيانات</p>
                    <p class="text-2xl font-extrabold text-green-700 dark:text-green-400">{{ money($repairsRevenue) }}</p>
                </div>
            </div>
        </div>
        <div class="card p-5 border-blue-200/60 dark:border-blue-900/50 bg-blue-50/30 dark:bg-blue-950/20">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-950/60 shrink-0">
                    {!! icon('shopping-cart', 'h-5 w-5 text-blue-600 dark:text-blue-400') !!}
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">إيراد المبيعات ({{ $salesCount }} عملية)</p>
                    <p class="text-2xl font-extrabold text-blue-700 dark:text-blue-400">{{ money($salesRevenue) }}</p>
                </div>
            </div>
        </div>
        <div class="card p-5 border-2 border-primary/40 bg-primary/5">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 shrink-0">
                    {!! icon('trending-up', 'h-5 w-5 text-primary') !!}
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">إجمالي الإيراد</p>
                    <p class="text-2xl font-extrabold text-primary">{{ money($totalRevenue) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- الرسم اليومي --}}
    <div class="card p-5">
        <h2 class="font-bold mb-4 flex items-center gap-2">{!! icon('bar-chart', 'h-4 w-4 text-primary') !!} الإيراد اليومي خلال {{ $periodLabel }}</h2>
        @if ($days->isNotEmpty())
            <div class="flex items-end justify-between gap-1.5 h-48 px-2">
                @foreach ($days as $day)
                    <div class="flex-1 flex flex-col items-center gap-1.5">
                        <div class="text-[9px] font-bold text-muted-foreground">{{ $day['total'] > 0 ? number_format($day['total'], 0) : '' }}</div>
                        <div class="w-full flex-1 flex items-end gap-0.5">
                            @if ($day['repairs'] > 0)
                                <div class="flex-1 rounded-t-md bg-green-400 dark:bg-green-800" style="height: {{ ($day['repairs'] / $maxDay) * 100 }}%"></div>
                            @endif
                            @if ($day['sales'] > 0)
                                <div class="flex-1 rounded-t-md bg-blue-400 dark:bg-blue-800" style="height: {{ ($day['sales'] / $maxDay) * 100 }}%"></div>
                            @endif
                            @if ($day['total'] == 0)
                                <div class="w-full h-[3px] rounded-full bg-muted"></div>
                            @endif
                        </div>
                        <div class="text-[9px] text-muted-foreground">{{ $day['day'] }}</div>
                    </div>
                @endforeach
            </div>
            <div class="flex items-center justify-center gap-4 mt-4 text-xs text-muted-foreground">
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-green-400 dark:bg-green-800"></span> صيانات</span>
                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-blue-400 dark:bg-blue-800"></span> مبيعات</span>
            </div>
        @else
            <p class="text-sm text-muted-foreground text-center py-8">مفيش بيانات للفترة دي</p>
        @endif
    </div>
</div>
@endsection
