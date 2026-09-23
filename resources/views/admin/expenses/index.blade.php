@extends('admin.layout')
@section('title', 'المصروفات — لوحة التحكم')
@section('admin_title', 'المصروفات')
@section('admin_subtitle', 'مصروفات المركز اليومية — بتخصم من إيراد اليوم')

@section('admin_actions')
    <button onclick="document.getElementById('expense-modal').showModal()" class="btn btn-primary btn-sm">{!! icon('plus', 'h-3.5 w-3.5') !!} مصروف جديد</button>
@endsection

@section('admin_content')
{{-- إحصائيات --}}
<div class="grid grid-cols-3 gap-3 mb-4">
    <div class="card p-4 text-center card-hover">
        <div class="text-2xl font-extrabold text-primary">{{ money($stats['today']) }}</div>
        <div class="text-xs text-muted-foreground mt-0.5">مصروفات اليوم ({{ $stats['todayCount'] }})</div>
    </div>
    <div class="card p-4 text-center card-hover">
        <div class="text-2xl font-extrabold text-amber-600 dark:text-amber-400">{{ money($stats['month']) }}</div>
        <div class="text-xs text-muted-foreground mt-0.5">هذا الشهر</div>
    </div>
    <div class="card p-4 text-center card-hover">
        <div class="text-2xl font-extrabold text-red-600 dark:text-red-400">{{ money($stats['total']) }}</div>
        <div class="text-xs text-muted-foreground mt-0.5">إجمالي كل المصروفات</div>
    </div>
</div>

{{-- توزيع الفئات هذا الشهر --}}
@if ($byCategory->isNotEmpty())
<div class="card p-5 mb-4">
    <h2 class="font-bold mb-4 text-sm flex items-center gap-2">{!! icon('pie-chart', 'h-4 w-4 text-primary') !!} توزيع مصروفات الشهر حسب الفئة</h2>
    @php $monthTotal = max($byCategory->sum('total'), 1); @endphp
    <div class="space-y-2.5">
        @foreach ($byCategory as $cat)
            <div>
                <div class="flex justify-between text-xs mb-1">
                    <span>{{ \App\Models\Expense::CATEGORY_LABELS[$cat->category] ?? $cat->category }} ({{ $cat->c }})</span>
                    <b>{{ money($cat->total) }}</b>
                </div>
                <div class="h-2 rounded-full bg-muted overflow-hidden">
                    <div class="h-full rounded-full bg-red-400 dark:bg-red-800" style="width: {{ ($cat->total / $monthTotal) * 100 }}%"></div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

{{-- فلاتر --}}
<form method="GET" class="card p-4 mb-4">
    <div class="flex flex-wrap gap-3 items-center">
        <select name="category" class="input w-auto">
            <option value="">كل الفئات</option>
            @foreach (\App\Models\Expense::CATEGORY_LABELS as $key => $label)
                <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}" class="input w-auto">
        <span class="text-muted-foreground text-xs">إلى</span>
        <input type="date" name="to" value="{{ request('to') }}" class="input w-auto">
        <button type="submit" class="btn btn-primary btn-sm">{!! icon('filter', 'h-3.5 w-3.5') !!} فلترة</button>
        @if (request()->hasAny(['category', 'from', 'to']))
            <a href="{{ route('admin.expenses.index') }}" class="btn btn-ghost btn-sm">مسح</a>
        @endif
    </div>
</form>

{{-- الجدول --}}
<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table-noor min-w-[680px]">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>الوصف</th>
                    <th>الفئة</th>
                    <th>المبلغ</th>
                    <th>سجّله</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($expenses as $e)
                    <tr x-data="{ edit: false }">
                        <td class="text-xs">{{ dt($e->date) }}</td>
                        <td class="font-bold">{{ $e->description }}</td>
                        <td><span class="badge bg-muted text-muted-foreground border-border text-[10px]">{{ $e->categoryLabel() }}</span></td>
                        <td><b class="text-red-600 dark:text-red-400">{{ money($e->amount) }}</b></td>
                        <td class="text-xs">{{ $e->createdBy?->name ?? '—' }}</td>
                        <td>
                            <div class="flex items-center gap-1.5">
                                <button @click="edit = !edit" class="btn btn-outline btn-sm">{!! icon('edit', 'h-3.5 w-3.5') !!}</button>
                                <form method="POST" action="{{ route('admin.expenses.destroy', $e) }}" onsubmit="return confirm('حذف المصروف؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm">{!! icon('trash', 'h-3.5 w-3.5') !!}</button>
                                </form>
                            </div>

                            <form x-show="edit" x-transition method="POST" action="{{ route('admin.expenses.update', $e) }}" class="mt-3 p-3 rounded-xl bg-muted/30 border border-border/60 grid grid-cols-2 gap-2" style="display:none">
                                @csrf
                                @method('PUT')
                                <input name="description" value="{{ $e->description }}" class="input py-1.5 text-xs col-span-2" required>
                                <input name="amount" type="number" step="0.01" value="{{ $e->amount }}" class="input py-1.5 text-xs" dir="ltr" required>
                                <input name="date" type="date" value="{{ $e->date->format('Y-m-d') }}" class="input py-1.5 text-xs" required>
                                <select name="category" class="input py-1.5 text-xs col-span-2">
                                    @foreach (\App\Models\Expense::CATEGORY_LABELS as $key => $label)
                                        <option value="{{ $key }}" {{ $e->category === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-primary btn-sm col-span-2">حفظ</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted-foreground py-8">مفيش مصروفات في الفترة دي</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $expenses->links() }}

{{-- مودال إضافة مصروف --}}
<dialog id="expense-modal" class="modal-backdrop">
    <form method="POST" action="{{ route('admin.expenses.store') }}" class="modal-box max-w-md">
        @csrf
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg">تسجيل مصروف</h3>
            <button type="button" onclick="document.getElementById('expense-modal').close()" class="flex h-8 w-8 items-center justify-center rounded-lg hover:bg-muted/60 cursor-pointer">{!! icon('x', 'h-4 w-4') !!}</button>
        </div>
        <div class="space-y-3">
            <div>
                <label class="label">الوصف *</label>
                <input name="description" value="{{ old('description') }}" placeholder="مثال: إيجار المكان، مرتب أحمد..." class="input {{ $errors->has('description') ? 'input-error' : '' }}" required>
                @error('description')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="label">المبلغ (ج.م) *</label>
                    <input name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount') }}" class="input {{ $errors->has('amount') ? 'input-error' : '' }}" dir="ltr" required>
                    @error('amount')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">التاريخ *</label>
                    <input name="date" type="date" value="{{ old('date', now()->toDateString()) }}" class="input" required>
                </div>
            </div>
            <div>
                <label class="label">الفئة *</label>
                <select name="category" class="input" required>
                    @foreach (\App\Models\Expense::CATEGORY_LABELS as $key => $label)
                        <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary w-full">{!! icon('plus', 'h-4 w-4') !!} تسجيل المصروف</button>
        </div>
    </form>
</dialog>

<style>
    dialog.modal-backdrop { border: none; background: transparent; padding: 0; max-width: none; max-height: none; }
    dialog.modal-backdrop::backdrop { background: rgb(0 0 0 / 0.5); backdrop-filter: blur(4px); }
    dialog.modal-box { background: var(--card); color: var(--foreground); border-radius: 1rem; padding: 1.5rem; width: 90vw; max-height: 85vh; overflow-y: auto; border: 1px solid var(--border); box-shadow: 0 20px 40px -10px rgb(0 0 0 / 0.3); }
</style>
@endsection
