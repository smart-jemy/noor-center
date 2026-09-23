@extends('layouts.app')
@section('title', 'المبيعات — قسم '.$dept->name)

@section('content')
<div class="container mx-auto max-w-6xl px-4 sm:px-6 py-8" x-data="{ saleOpen: false }">
    <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
        <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary shrink-0">
                {!! icon('shopping-cart', 'h-6 w-6') !!}
            </div>
            <div>
                <h1 class="text-2xl font-extrabold mb-0.5">مبيعات القسم</h1>
                <p class="text-muted-foreground text-sm">بيع قطع الغيار من المخزن — بيخصم من المخزن ويتحسب في إيراد اليوم</p>
            </div>
        </div>
        <button @click="saleOpen = !saleOpen" class="btn btn-primary">
            {!! icon('plus', 'h-4 w-4') !!}
            تسجيل بيع
        </button>
    </div>

    @include('department.partials.tabs')

    {{-- إحصائيات --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <div class="card p-4 text-center card-hover">
            <div class="text-2xl font-extrabold text-green-600 dark:text-green-400">{{ money($todayTotal) }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">مبيعات اليوم ({{ $todayCount }})</div>
        </div>
        <div class="card p-4 text-center card-hover">
            <div class="text-2xl font-extrabold text-primary">{{ money($total) }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">إجمالي الفترة ({{ $count }})</div>
        </div>
        <div class="card p-4 text-center card-hover">
            <div class="text-2xl font-extrabold text-blue-600 dark:text-blue-400">{{ $items->count() }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">أصناف متاحة للبيع</div>
        </div>
    </div>

    {{-- نموذج تسجيل بيع --}}
    <div x-show="saleOpen" x-transition class="card p-5 mb-4 border-primary/30" style="display: none">
        <h2 class="font-bold mb-4 flex items-center gap-2">{!! icon('shopping-cart', 'h-4 w-4 text-primary') !!} تسجيل بيع قطعة من المخزن</h2>
        <form method="POST" action="{{ route('department.sales.store') }}" class="space-y-3">
            @csrf
            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="label">الصنف من المخزن <span class="text-destructive">*</span></label>
                    <select name="inventory_item_id" class="input" required>
                        <option value="">— اختر الصنف —</option>
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}{{ $item->brand ? ' ('.$item->brand.')' : '' }} — متاح {{ $item->quantity }} {{ $item->unit }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">اسم العميل</label>
                    <input name="customer_name" placeholder="اختياري — لو نقدي سبه فاضي" class="input">
                </div>
            </div>
            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="label">الكمية <span class="text-destructive">*</span></label>
                    <input name="quantity" type="number" step="0.01" min="0.01" value="1" class="input" dir="ltr" required>
                </div>
                <div>
                    <label class="label">سعر الوحدة (ج.م) <span class="text-destructive">*</span></label>
                    <input name="unit_price" type="number" step="0.01" min="0" class="input" dir="ltr" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-full">{!! icon('save', 'h-4 w-4') !!} تسجيل البيع</button>
        </form>
    </div>

    {{-- فلاتر الفترة --}}
    <form method="GET" class="card p-4 mb-4">
        <div class="flex flex-wrap gap-3 items-center">
            <input type="date" name="from" value="{{ $from }}" class="input w-auto">
            <span class="text-muted-foreground text-xs">إلى</span>
            <input type="date" name="to" value="{{ $to }}" class="input w-auto">
            <button type="submit" class="btn btn-outline btn-sm">{!! icon('filter', 'h-3.5 w-3.5') !!} فلترة</button>
        </div>
    </form>

    {{-- سجل المبيعات --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-noor min-w-[560px]">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>الصنف</th>
                        <th class="text-center">الكمية</th>
                        <th>سعر الوحدة</th>
                        <th>الإجمالي</th>
                        <th>العميل</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <td class="text-xs">{{ dt($sale['created_at'], true) }}</td>
                            <td class="font-bold">{{ $sale['item'] }}</td>
                            <td class="text-center">{{ $sale['qty'] }}</td>
                            <td class="text-sm">{{ money($sale['price']) }}</td>
                            <td><b class="text-primary">{{ money($sale['total']) }}</b></td>
                            <td class="text-sm">{{ $sale['customer'] ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted-foreground py-8">مفيش مبيعات في الفترة دي</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
