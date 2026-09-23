@extends('layouts.app')
@section('title', 'مخزن قسم '.$dept->name)

@section('content')
<div class="container mx-auto max-w-6xl px-4 sm:px-6 py-8">
    <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
        <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary shrink-0">
                {!! icon('package', 'h-6 w-6') !!}
            </div>
            <div>
                <h1 class="text-2xl font-extrabold mb-0.5">مخزن القسم</h1>
                <p class="text-muted-foreground text-sm">قطع غيار قسم {{ $dept->name }} — عرض وإضافة وتحديث</p>
            </div>
        </div>
        <button onclick="document.getElementById('dept-item-modal').showModal()" class="btn btn-primary">
            {!! icon('plus', 'h-4 w-4') !!}
            إضافة صنف
        </button>
    </div>

    @include('department.partials.tabs')

    {{-- إحصائيات --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <div class="card p-4 text-center card-hover">
            <div class="text-2xl font-extrabold text-primary">{{ $stats['total'] }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">إجمالي الأصناف</div>
        </div>
        <div class="card p-4 text-center card-hover">
            <div class="text-2xl font-extrabold text-green-600 dark:text-green-400">{{ money($stats['stockValue']) }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">قيمة المخزن</div>
        </div>
        <div class="card p-4 text-center card-hover">
            <div class="text-2xl font-extrabold text-amber-600 dark:text-amber-400">{{ $stats['lowStock'] }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">مخزون منخفض</div>
        </div>
        <div class="card p-4 text-center card-hover">
            <div class="text-2xl font-extrabold text-red-600 dark:text-red-400">{{ $stats['outOfStock'] }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">نفد المخزون</div>
        </div>
    </div>

    {{-- فلاتر --}}
    <form method="GET" class="card p-4 mb-4">
        <div class="flex flex-wrap gap-3 items-center">
            <select name="category" class="input w-auto">
                <option value="">كل التصنيفات</option>
                @foreach (\App\Models\InventoryItem::CATEGORY_LABELS as $key => $label)
                    <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <div class="relative flex-1 min-w-[200px]">
                {!! icon('search', 'h-4 w-4 absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground') !!}
                <input name="q" value="{{ request('q') }}" placeholder="بحث بالاسم، الماركة، رقم القطعة..." class="input pr-10">
            </div>
            <button type="submit" class="btn btn-outline">{!! icon('filter', 'h-4 w-4') !!} فلترة</button>
            @if (request()->hasAny(['category', 'q']))
                <a href="{{ route('department.inventory') }}" class="btn btn-ghost btn-sm">مسح</a>
            @endif
        </div>
    </form>

    {{-- الجدول --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-noor min-w-[640px]">
                <thead>
                    <tr>
                        <th>الصنف</th>
                        <th class="text-center">الكمية</th>
                        <th>سعر الشراء</th>
                        <th>سعر البيع</th>
                        <th>تحديث</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr x-data="{ edit: false }">
                            <td>
                                <div class="font-bold">{{ $item->name }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ $item->categoryLabel() }}{{ $item->brand ? ' • '.$item->brand : '' }}
                                    @if ($item->location) • {{ $item->location }} @endif
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="font-bold {{ $item->quantity <= 0 ? 'text-red-600' : ($item->isLowStock() ? 'text-amber-600' : 'text-green-600') }}">{{ $item->quantity }}</span>
                                <span class="text-[10px] text-muted-foreground block">{{ $item->unit }}</span>
                            </td>
                            <td class="text-sm">{{ $item->cost_price ? money($item->cost_price) : '—' }}</td>
                            <td class="text-sm">{{ $item->sell_price ? money($item->sell_price) : '—' }}</td>
                            <td>
                                <button @click="edit = !edit" class="btn btn-outline btn-sm">{!! icon('edit', 'h-3.5 w-3.5') !!}</button>
                                <form x-show="edit" x-transition method="POST" action="{{ route('department.inventory.update', $item) }}" class="mt-3 p-3 rounded-xl bg-muted/30 border border-border/60 grid grid-cols-3 gap-2" style="display:none">
                                    @csrf
                                    @method('PUT')
                                    <input name="quantity" type="number" step="0.01" min="0" value="{{ $item->quantity }}" class="input py-1.5 text-xs" dir="ltr" required placeholder="كمية">
                                    <input name="sell_price" type="number" step="0.01" min="0" value="{{ $item->sell_price }}" class="input py-1.5 text-xs" dir="ltr" placeholder="سعر البيع">
                                    <button type="submit" class="btn btn-primary btn-sm py-1.5 text-xs col-span-1">حفظ</button>
                                    <input name="location" value="{{ $item->location }}" class="input py-1.5 text-xs col-span-3" placeholder="مكان التخزين">
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted-foreground py-8">مفيش أصناف في مخزن القسم</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $items->links() }}
</div>

{{-- مودال إضافة صنف --}}
<dialog id="dept-item-modal" class="modal-backdrop">
    <form method="POST" action="{{ route('department.inventory.store') }}" class="modal-box max-w-lg">
        @csrf
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg">إضافة صنف لمخزن {{ $dept->name }}</h3>
            <button type="button" onclick="document.getElementById('dept-item-modal').close()" class="flex h-8 w-8 items-center justify-center rounded-lg hover:bg-muted/60 cursor-pointer">{!! icon('x', 'h-4 w-4') !!}</button>
        </div>
        <div class="space-y-3">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="label">اسم الصنف *</label>
                    <input name="name" value="{{ old('name') }}" placeholder="مثال: كاباكيتور 35 ميكرو" class="input {{ $errors->has('name') ? 'input-error' : '' }}" required>
                    @error('name')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">التصنيف *</label>
                    <select name="category" class="input" required>
                        @foreach (\App\Models\InventoryItem::CATEGORY_LABELS as $key => $label)
                            <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="label">الماركة</label>
                    <input name="brand" value="{{ old('brand') }}" placeholder="اختياري" class="input">
                </div>
                <div>
                    <label class="label">رقم القطعة</label>
                    <input name="part_number" value="{{ old('part_number') }}" placeholder="اختياري" class="input" dir="ltr">
                </div>
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="label">الكمية *</label>
                    <input name="quantity" type="number" step="0.01" min="0" value="{{ old('quantity', 1) }}" class="input" dir="ltr" required>
                </div>
                <div>
                    <label class="label">الوحدة</label>
                    <input name="unit" value="{{ old('unit', 'قطعة') }}" class="input">
                </div>
                <div>
                    <label class="label">حد التنبيه</label>
                    <input name="min_quantity" type="number" step="0.01" min="0" value="{{ old('min_quantity', 0) }}" class="input" dir="ltr">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="label">سعر الشراء</label>
                    <input name="cost_price" type="number" step="0.01" min="0" value="{{ old('cost_price') }}" class="input" dir="ltr" placeholder="0.00">
                </div>
                <div>
                    <label class="label">سعر البيع</label>
                    <input name="sell_price" type="number" step="0.01" min="0" value="{{ old('sell_price') }}" class="input" dir="ltr" placeholder="0.00">
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-full">{!! icon('plus', 'h-4 w-4') !!} إضافة لمخزن القسم</button>
        </div>
    </form>
</dialog>

<style>
    dialog.modal-backdrop { border: none; background: transparent; padding: 0; max-width: none; max-height: none; }
    dialog.modal-backdrop::backdrop { background: rgb(0 0 0 / 0.5); backdrop-filter: blur(4px); }
    dialog.modal-box { background: var(--card); color: var(--foreground); border-radius: 1rem; padding: 1.5rem; width: 90vw; max-height: 85vh; overflow-y: auto; border: 1px solid var(--border); box-shadow: 0 20px 40px -10px rgb(0 0 0 / 0.3); }
</style>
@endsection
