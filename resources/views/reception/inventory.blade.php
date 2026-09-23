@extends('layouts.app')
@section('title', 'المخزن — استقبال')

@section('content')
<div class="container mx-auto max-w-6xl px-4 sm:px-6 py-8">
    <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
        <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary shrink-0">
                {!! icon('package', 'h-6 w-6') !!}
            </div>
            <div>
                <h1 class="text-2xl font-extrabold mb-0.5">المخزن</h1>
                <p class="text-muted-foreground text-sm">قطع الغيار والمستلزمات — عرض وإضافة</p>
            </div>
        </div>
        <button onclick="document.getElementById('item-modal').showModal()" class="btn btn-primary">
            {!! icon('plus', 'h-4 w-4') !!}
            إضافة صنف
        </button>
    </div>

    @include('reception.partials.tabs')

    {{-- إحصائيات — KPI حديث --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5 mb-4 stagger">
        <div class="kpi" style="--kpi-accent: var(--color-primary)">
            <div class="flex items-center justify-between">
                <div>
                    <div class="kpi-value">{{ $stats['total'] }}</div>
                    <div class="kpi-label">إجمالي الأصناف</div>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10 text-primary shrink-0">{!! icon('package', 'h-4 w-4') !!}</div>
            </div>
        </div>
        <div class="kpi" style="--kpi-accent: var(--color-green-500)">
            <div class="flex items-center justify-between">
                <div>
                    <div class="kpi-value">{{ money($stats['stockValue']) }}</div>
                    <div class="kpi-label">قيمة المخزن</div>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-green-500/10 text-green-500 shrink-0">{!! icon('dollar', 'h-4 w-4') !!}</div>
            </div>
        </div>
        <div class="kpi {{ $stats['lowStock'] > 0 ? 'selected' : '' }}" style="--kpi-accent: var(--color-amber-500)">
            <div class="flex items-center justify-between">
                <div>
                    <div class="kpi-value">{{ $stats['lowStock'] }}</div>
                    <div class="kpi-label">مخزون منخفض</div>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500 shrink-0">{!! icon('alert-circle', 'h-4 w-4') !!}</div>
            </div>
        </div>
        <div class="kpi {{ $stats['outOfStock'] > 0 ? 'selected' : '' }}" style="--kpi-accent: var(--color-red-500)">
            <div class="flex items-center justify-between">
                <div>
                    <div class="kpi-value">{{ $stats['outOfStock'] }}</div>
                    <div class="kpi-label">نفد المخزون</div>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-500/10 text-red-500 shrink-0">{!! icon('x', 'h-4 w-4') !!}</div>
            </div>
        </div>
    </div>

    {{-- فلاتر — شريط أدوات حديث --}}
    <form method="GET" class="toolbar p-3 mb-4">
        <div class="flex flex-wrap gap-2.5 items-center">
            <select name="category" class="input w-auto h-10">
                <option value="">كل التصنيفات</option>
                @foreach (\App\Models\InventoryItem::CATEGORY_LABELS as $key => $label)
                    <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <div class="relative flex-1 min-w-[180px] max-w-md">
                {!! icon('search', 'h-4 w-4 absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none') !!}
                <input name="q" value="{{ request('q') }}" placeholder="بحث بالاسم، الماركة، رقم القطعة..." class="input pr-10 h-10" autocomplete="off">
            </div>
            <button type="submit" class="btn btn-outline btn-sm h-10">{!! icon('filter', 'h-4 w-4') !!} فلترة</button>
            @if (request()->hasAny(['category', 'q']))
                <a href="{{ route('reception.inventory') }}" class="btn btn-ghost btn-sm h-10">مسح</a>
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
                        <th>القسم</th>
                        <th class="text-center">الكمية</th>
                        <th>سعر الشراء</th>
                        <th>سعر البيع</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td>
                                <div class="font-bold">{{ $item->name }}</div>
                                <div class="text-xs text-muted-foreground">
                                    @if ($item->part_number)<span dir="ltr">{{ $item->part_number }}</span> • @endif
                                    {{ $item->categoryLabel() }}{{ $item->brand ? ' • '.$item->brand : '' }}
                                </div>
                            </td>
                            <td><span class="badge bg-muted text-muted-foreground border-border text-[10px]">{{ $item->department?->name ?? 'عام' }}</span></td>
                            <td class="text-center">
                                <span class="font-bold {{ $item->quantity <= 0 ? 'text-red-600' : ($item->isLowStock() ? 'text-amber-600' : 'text-green-600') }}">{{ $item->quantity }}</span>
                                <span class="text-[10px] text-muted-foreground block">{{ $item->unit }}</span>
                            </td>
                            <td class="text-sm">{{ $item->cost_price ? money($item->cost_price) : '—' }}</td>
                            <td class="text-sm">{{ $item->sell_price ? money($item->sell_price) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted-foreground py-8">مفيش أصناف مطابقة</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $items->links() }}
</div>

{{-- مودال إضافة صنف --}}
<dialog id="item-modal" class="modal-backdrop">
    <form method="POST" action="{{ route('reception.inventory.store') }}" class="modal-box max-w-lg">
        @csrf
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg">إضافة صنف للمخزن</h3>
            <button type="button" onclick="document.getElementById('item-modal').close()" class="flex h-8 w-8 items-center justify-center rounded-lg hover:bg-muted/60 cursor-pointer">{!! icon('x', 'h-4 w-4') !!}</button>
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
            <div>
                <label class="label">القسم</label>
                <select name="department_id" class="input">
                    <option value="">مخزن عام</option>
                    @foreach (\App\Models\Department::where('is_active', true)->orderBy('name')->get() as $dept)
                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-full">{!! icon('plus', 'h-4 w-4') !!} إضافة للمخزن</button>
        </div>
    </form>
</dialog>

<style>
    dialog.modal-backdrop { border: none; background: transparent; padding: 0; max-width: none; max-height: none; }
    dialog.modal-backdrop::backdrop { background: rgb(0 0 0 / 0.5); backdrop-filter: blur(4px); }
    dialog.modal-box { background: var(--card); color: var(--foreground); border-radius: 1rem; padding: 1.5rem; width: 90vw; max-height: 85vh; overflow-y: auto; border: 1px solid var(--border); box-shadow: 0 20px 40px -10px rgb(0 0 0 / 0.3); }
</style>
@endsection
