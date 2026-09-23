@extends('admin.layout')
@section('title', 'التقرير المالي — لوحة التحكم')
@section('admin_title', 'التقرير المالي')
@section('admin_subtitle', 'إيرادات ومصروفات وصافي الربح + أرصدة الشركاء والعملاء')

@section('admin_content')
{{-- فترة التقرير --}}
<form method="GET" class="card p-4 mb-4">
    <div class="flex flex-wrap gap-3 items-center">
        <div>
            <label class="label">من</label>
            <input type="date" name="from" value="{{ $from }}" class="input w-auto">
        </div>
        <div>
            <label class="label">إلى</label>
            <input type="date" name="to" value="{{ $to }}" class="input w-auto">
        </div>
        <button type="submit" class="btn btn-primary btn-sm mt-5">{!! icon('filter', 'h-3.5 w-3.5') !!} عرض التقرير</button>
    </div>
</form>

{{-- الملخص --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
    <div class="card p-4 card-hover">
        <div class="text-xs text-muted-foreground mb-1 flex items-center gap-1.5">{!! icon('trending-up', 'h-3.5 w-3.5 text-primary') !!} إيراد الصيانات</div>
        <div class="text-xl font-extrabold text-primary">{{ money($summary['revenue']) }}</div>
        <div class="text-[10px] text-muted-foreground mt-1">{{ $summary['completedCount'] }} صيانة مكتملة</div>
    </div>
    <div class="card p-4 card-hover">
        <div class="text-xs text-muted-foreground mb-1 flex items-center gap-1.5">{!! icon('banknote', 'h-3.5 w-3.5 text-green-600') !!} محصّل فعلياً</div>
        <div class="text-xl font-extrabold text-green-600 dark:text-green-400">{{ money($summary['collected']) }}</div>
        <div class="text-[10px] text-muted-foreground mt-1">آجل على العملاء: {{ money($summary['receivable']) }}</div>
    </div>
    <div class="card p-4 card-hover">
        <div class="text-xs text-muted-foreground mb-1 flex items-center gap-1.5">{!! icon('wallet', 'h-3.5 w-3.5 text-red-600') !!} مصروفات</div>
        <div class="text-xl font-extrabold text-red-600 dark:text-red-400">{{ money($summary['expensesTotal']) }}</div>
        <div class="text-[10px] text-muted-foreground mt-1">{{ $summary['expensesCount'] }} مصروف</div>
    </div>
    <div class="card p-4 card-hover {{ $summary['net'] >= 0 ? 'bg-gradient-to-br from-green-50 dark:from-green-950/30 to-transparent border-green-200 dark:border-green-900' : 'bg-gradient-to-br from-red-50 dark:from-red-950/30 to-transparent border-red-200 dark:border-red-900' }}">
        <div class="text-xs text-muted-foreground mb-1 flex items-center gap-1.5">{!! icon('dollar', 'h-3.5 w-3.5 '.($summary['net'] >= 0 ? 'text-green-600' : 'text-red-600')) !!} الصافي</div>
        <div class="text-xl font-extrabold {{ $summary['net'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ money($summary['net']) }}</div>
        <div class="text-[10px] text-muted-foreground mt-1">محصل + شركاء − مصروفات</div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-4">

    {{-- تفصيل يومي --}}
    <div class="lg:col-span-2 card p-5">
        <h2 class="font-bold mb-4 flex items-center gap-2">{!! icon('chart', 'h-4 w-4 text-primary') !!} التدفق اليومي ({{ $days->count() }} يوم)</h2>

        {{-- رسم بياني --}}
        @php $maxDay = max($days->max('maintenance') + $days->max('partner'), $days->max('expenses'), 1); @endphp
        @if ($days->count() <= 31)
            <div class="flex items-end gap-1 h-36 mb-4" dir="ltr">
                @foreach ($days as $day)
                    <div class="flex-1 flex flex-col items-center gap-0.5 group relative">
                        <div class="w-full flex items-end justify-center h-32 gap-0.5">
                            <div class="w-1/2 max-w-4 rounded-t bg-primary/70 group-hover:bg-primary transition-all" style="height: {{ max(2, ((($day['maintenance'] + $day['partner']) / $maxDay) * 100)) }}%"></div>
                            <div class="w-1/2 max-w-4 rounded-t bg-red-400/70 dark:bg-red-900/70 group-hover:bg-red-500" style="height: {{ max(2, ($day['expenses'] / $maxDay) * 100) }}%"></div>
                        </div>
                        <span class="text-[8px] text-muted-foreground">{{ \Carbon\Carbon::parse($day['date'])->format('j') }}</span>
                        <div class="absolute bottom-full mb-1 hidden group-hover:block z-10 card p-2 text-[10px] whitespace-nowrap">
                            {{ dt($day['date']) }}<br>
                            صيانات: {{ money($day['maintenance']) }}<br>
                            شركاء: {{ money($day['partner']) }}<br>
                            مصروفات: {{ money($day['expenses']) }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- جدول --}}
        <div class="overflow-x-auto max-h-80 overflow-y-auto">
            <table class="table-noor min-w-[480px]">
                <thead>
                    <tr>
                        <th>اليوم</th>
                        <th>محصل صيانات</th>
                        <th>شركاء</th>
                        <th>مصروفات</th>
                        <th>صافي اليوم</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($days->reverse() as $day)
                        @php $dayNet = $day['maintenance'] + $day['partner'] - $day['expenses']; @endphp
                        <tr>
                            <td class="text-xs">{{ dt($day['date']) }}</td>
                            <td class="text-xs">{{ money($day['maintenance']) }}</td>
                            <td class="text-xs">{{ money($day['partner']) }}</td>
                            <td class="text-xs text-red-600 dark:text-red-400">{{ money($day['expenses']) }}</td>
                            <td class="text-xs font-bold {{ $dayNet >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ money($dayNet) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- الجانب --}}
    <div class="space-y-4">
        {{-- تفصيل الشركاء --}}
        <div class="card p-5">
            <h2 class="font-bold mb-3 text-sm flex items-center gap-2">{!! icon('repeat', 'h-4 w-4 text-primary') !!} حركة الشركاء في الفترة</h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-muted-foreground">مدفوع فوري (صيانات)</span><b>{{ money($summary['partnerPaid']) }}</b></div>
                <div class="flex justify-between"><span class="text-muted-foreground">تسويات مستلمة</span><b>{{ money($summary['settlementsTotal']) }}</b></div>
                <div class="flex justify-between"><span class="text-muted-foreground">آجل على الشركاء</span><b class="text-amber-600 dark:text-amber-400">{{ money($summary['partnerDeferred']) }}</b></div>
                <div class="flex justify-between text-xs text-muted-foreground"><span>{{ $summary['partnerRepairsCount'] }} صيانة + {{ $summary['settlementsCount'] }} تسوية</span></div>
            </div>
        </div>

        {{-- المصروفات حسب الفئة --}}
        @if ($expensesByCategory->isNotEmpty())
            <div class="card p-5">
                <h2 class="font-bold mb-3 text-sm flex items-center gap-2">{!! icon('pie-chart', 'h-4 w-4 text-primary') !!} المصروفات حسب الفئة</h2>
                @php $catTotal = max($expensesByCategory->sum(), 1); @endphp
                <div class="space-y-2.5">
                    @foreach ($expensesByCategory as $cat => $amount)
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span>{{ \App\Models\Expense::CATEGORY_LABELS[$cat] ?? $cat }}</span>
                                <b>{{ money($amount) }}</b>
                            </div>
                            <div class="h-2 rounded-full bg-muted overflow-hidden">
                                <div class="h-full rounded-full bg-red-400 dark:bg-red-800" style="width: {{ ($amount / $catTotal) * 100 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- أرصدة الشركاء الحالية --}}
        @if ($partnerBalances->isNotEmpty())
            <div class="card p-5">
                <h2 class="font-bold mb-3 text-sm flex items-center gap-2">{!! icon('alert-circle', 'h-4 w-4 text-amber-600') !!} مستحق على الشركاء (حالياً)</h2>
                <div class="space-y-2">
                    @foreach ($partnerBalances as $pb)
                        <div class="flex justify-between items-center p-2.5 rounded-xl bg-muted/40 text-sm">
                            <span><b>{{ $pb['name'] }}</b> <span class="text-[10px] text-muted-foreground">({{ $pb['repairs'] }} صيانة)</span></span>
                            <b class="text-amber-600 dark:text-amber-400">{{ money($pb['balance']) }}</b>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
