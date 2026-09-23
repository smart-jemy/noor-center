@extends('admin.layout')
@section('title', 'المخزون — لوحة التحكم')
@section('admin_title', 'المخزون')
@section('admin_subtitle', 'قطع الغيار والأدوات — مع تنبيهات النقص')

@section('admin_actions')
    <button onclick="document.getElementById('item-modal').showModal()" class="btn btn-primary btn-sm">{!! icon('plus', 'h-3.5 w-3.5') !!} صنف جديد</button>
@endsection

@section('admin_content')
{{-- إحصائيات --}}
<div class="grid grid-cols-3 gap-3 mb-4">
    <div class="card p-4 text-center card-hover">
        <div class="text-2xl font-extrabold text-primary">{{ $stats['total'] }}</div>
        <div class="text-xs text-muted-foreground mt-0.5">إجمالي الأصناف</div>
    </div>
    <div class="card p-4 text-center card-hover {{ $stats['lowStock'] > 0 ? 'border-red-300 dark:border-red-900' : '' }}">
        <div class="text-2xl font-extrabold {{ $stats['lowStock'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-muted-foreground' }}">{{ $stats['lowStock'] }}</div>
        <div class="text-xs text-muted-foreground mt-0.5">ناقص (تحت الحد)</div>
    </div>
    <div class="card p-4 text-center card-hover">
        <div class="text-2xl font-extrabold text-green-600 dark:text-green-400">{{ money($stats['stockValue']) }}</div>
        <div class="text-xs text-muted-foreground mt-0.5">قيمة المخزون (تكلفة)</div>
    </div>
</div>

{{-- فلاتر --}}
<form method="GET" class="card p-4 mb-4">
    <div class="flex flex-wrap gap-3 items-center">
        <div class="relative flex-1 min-w-[200px]">
            {!! icon('search', 'h-4 w-4 absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground') !!}
            <input name="q" value="{{ request('q') }}" placeholder="بحث بالاسم / الماركة / رقم القطعة..." class="input pr-10">
        </div>
        <select name="category" class="input w-auto">
            <option value="">كل التصنيفات</option>
            @foreach (\App\Models\InventoryItem::CATEGORY_LABELS as $key => $label)
                <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <label class="flex items-center gap-2 text-sm text-muted-foreground cursor-pointer select-none">
            <input type="checkbox" name="low_stock" value="1" {{ request('low_stock') ? 'checked' : '' }} class="accent-primary w-4 h-4">
            الناقص فقط
        </label>
        <button type="submit" class="btn btn-primary btn-sm">{!! icon('filter', 'h-3.5 w-3.5') !!} فلترة</button>
        @if (request()->hasAny(['q', 'category', 'low_stock']))
            <a href="{{ route('admin.inventory.index') }}" class="btn btn-ghost btn-sm">مسح</a>
        @endif
    </div>
</form>

{{-- الجدول --}}
<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table-noor min-w-[860px]">
            <thead>
                <tr>
                    <th>الصنف</th>
                    <th>التصنيف</th>
                    <th>الكمية</th>
                    <th>سعر الشراء</th>
                    <th>سعر البيع</th>
                    <th>القسم</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr x-data="{ edit: false }" class="{{ $item->isLowStock() ? 'bg-red-50/50 dark:bg-red-950/20' : '' }}">
                        <td>
                            <div class="font-bold">{{ $item->name }}</div>
                            <div class="text-[10px] text-muted-foreground">
                                @if ($item->brand){{ $item->brand }} @endif
                                @if ($item->part_number)• {{ $item->part_number }} @endif
                                @if ($item->location)• {{ $item->location }} @endif
                            </div>
                        </td>
                        <td><span class="badge bg-muted text-muted-foreground border-border text-[10px]">{{ $item->categoryLabel() }}</span></td>
                        <td>
                            <div class="flex items-center gap-1.5">
                                <b class="{{ $item->isLowStock() ? 'text-red-600 dark:text-red-400' : '' }}">{{ $item->quantity }}</b>
                                <span class="text-muted-foreground text-xs">{{ $item->unit }}</span>
                                @if ($item->isLowStock())
                                    <span class="badge bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-300 border-red-200 dark:border-red-900 text-[9px]">ناقص!</span>
                                @endif
                            </div>
                            @if ($item->min_quantity > 0)<div class="text-[10px] text-muted-foreground">حد التنبيه: {{ $item->min_quantity }}</div>@endif
                        </td>
                        <td class="text-xs">{{ money($item->cost_price) }}</td>
                        <td class="text-xs font-bold text-primary">{{ money($item->sell_price) }}</td>
                        <td class="text-xs">{{ $item->department?->name ?? 'عام' }}</td>
                        <td>
                            <div class="flex items-center gap-1.5">
                                {{-- تعديل سريع للكمية --}}
                                <form method="POST" action="{{ route('admin.inventory.adjust', $item) }}" class="flex items-center gap-1">
                                    @csrf
                                    <input type="hidden" name="direction" value="{{ $item->isLowStock() ? 'add' : 'remove' }}">
                                    <input name="amount" type="number" step="0.5" min="0.01" placeholder="كمية" class="input py-1 w-16 text-xs" dir="ltr" required>
                                    <button class="btn btn-sm {{ $item->isLowStock() ? 'btn-success' : 'btn-outline' }}" title="{{ $item->isLowStock() ? 'إضافة كمية' : 'خصم كمية' }}">
                                        {!! icon($item->isLowStock() ? 'plus' : 'alert-circle', 'h-3 w-3') !!}
                                    </button>
                                </form>
                                <button @click="edit = !edit" class="btn btn-outline btn-sm" title="تعديل">{!! icon('edit', 'h-3.5 w-3.5') !!}</button>
                                <form method="POST" action="{{ route('admin.inventory.destroy', $item) }}" onsubmit="return confirm('حذف {{ $item->name }}؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm">{!! icon('trash', 'h-3.5 w-3.5') !!}</button>
                                </form>
                            </div>

                            {{-- تعديل كامل --}}
                            <form x-show="edit" x-transition method="POST" action="{{ route('admin.inventory.update', $item) }}" class="mt-3 p-3 rounded-xl bg-muted/30 border border-border/60 grid grid-cols-2 gap-2" style="display:none">
                                @csrf
                                @method('PUT')
                                <input name="name" value="{{ $item->name }}" class="input py-1.5 text-xs col-span-2" required>
                                <input name="brand" value="{{ $item->brand }}" placeholder="الماركة" class="input py-1.5 text-xs">
                                <input name="part_number" value="{{ $item->part_number }}" placeholder="رقم القطعة" class="input py-1.5 text-xs">
                                <select name="category" class="input py-1.5 text-xs">
                                    @foreach (\App\Models\InventoryItem::CATEGORY_LABELS as $key => $label)
                                        <option value="{{ $key }}" {{ $item->category === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <input name="unit" value="{{ $item->unit }}" placeholder="الوحدة" class="input py-1.5 text-xs">
                                <input name="quantity" type="number" step="0.5" value="{{ $item->quantity }}" class="input py-1.5 text-xs" dir="ltr" required>
                                <input name="min_quantity" type="number" step="0.5" value="{{ $item->min_quantity }}" class="input py-1.5 text-xs" dir="ltr">
                                <input name="cost_price" type="number" step="0.01" value="{{ $item->cost_price }}" class="input py-1.5 text-xs" dir="ltr">
                                <input name="sell_price" type="number" step="0.01" value="{{ $item->sell_price }}" class="input py-1.5 text-xs" dir="ltr">
                                <input name="location" value="{{ $item->location }}" placeholder="المكان" class="input py-1.5 text-xs col-span-2">
                                <select name="department_id" class="input py-1.5 text-xs col-span-2">
                                    <option value="">مخزن عام</option>
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ $item->department_id === $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-primary btn-sm col-span-2">حفظ التعديل</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted-foreground py-8">المخزن فاضي — أضف أول صنف</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $items->links() }}

{{-- مودال إضافة صنف --}}
<dialog id="item-modal" class="modal-backdrop">
    <form method="POST" action="{{ route('admin.inventory.store') }}" class="modal-box max-w-lg">
        @csrf
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg">إضافة صنف للمخزن</h3>
            <button type="button" onclick="document.getElementById('item-modal').close()" class="flex h-8 w-8 items-center justify-center rounded-lg hover:bg-muted/60 cursor-pointer">{!! icon('x', 'h-4 w-4') !!}</button>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div class="col-span-2">
                <label class="label">اسم الصنف *</label>
                <input name="name" value="{{ old('name') }}" class="input {{ $errors->has('name') ? 'input-error' : '' }}" required>
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
            <div>
                <label class="label">الوحدة</label>
                <input name="unit" value="{{ old('unit', 'قطعة') }}" class="input">
            </div>
            <div>
                <label class="label">الكمية *</label>
                <input name="quantity" type="number" step="0.5" min="0" value="{{ old('quantity') }}" class="input" dir="ltr" required>
            </div>
            <div>
                <label class="label">حد التنبيه</label>
                <input name="min_quantity" type="number" step="0.5" min="0" value="{{ old('min_quantity') }}" class="input" dir="ltr">
            </div>
            <div>
                <label class="label">سعر الشراء</label>
                <input name="cost_price" type="number" step="0.01" min="0" value="{{ old('cost_price') }}" class="input" dir="ltr">
            </div>
            <div>
                <label class="label">سعر البيع</label>
                <input name="sell_price" type="number" step="0.01" min="0" value="{{ old('sell_price') }}" class="input" dir="ltr">
            </div>
            <div>
                <label class="label">الماركة</label>
                <input name="brand" value="{{ old('brand') }}" class="input">
            </div>
            <div>
                <label class="label">رقم القطعة</label>
                <input name="part_number" value="{{ old('part_number') }}" class="input">
            </div>
            <div class="col-span-2">
                <label class="label">القسم (المخزن العام لو فاضي)</label>
                <select name="department_id" class="input">
                    <option value="">مخزن عام</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-2">
                <label class="label">مكان التخزين</label>
                <input name="location" value="{{ old('location') }}" placeholder="رف 3 - رف علوي..." class="input">
            </div>
            <button type="submit" class="btn btn-primary col-span-2">{!! icon('plus', 'h-4 w-4') !!} إضافة الصنف</button>
        </div>
    </form>
</dialog>

<style>
    dialog.modal-backdrop { border: none; background: transparent; padding: 0; max-width: none; max-height: none; }
    dialog.modal-backdrop::backdrop { background: rgb(0 0 0 / 0.5); backdrop-filter: blur(4px); }
    dialog.modal-box { background: var(--card); color: var(--foreground); border-radius: 1rem; padding: 1.5rem; width: 90vw; max-height: 85vh; overflow-y: auto; border: 1px solid var(--border); box-shadow: 0 20px 40px -10px rgb(0 0 0 / 0.3); }
</style>
@endsection
