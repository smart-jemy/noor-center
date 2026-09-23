@extends('admin.layout')
@section('title', 'أنواع الأجهزة — لوحة التحكم')
@section('admin_title', 'أنواع الأجهزة')
@section('admin_subtitle', 'الأجهزة اللي بيظهر للعملاء في صفحة الطلب + الملحقات')

@section('admin_actions')
    <button onclick="document.getElementById('device-modal').showModal()" class="btn btn-primary btn-sm">{!! icon('plus', 'h-3.5 w-3.5') !!} نوع جديد</button>
@endsection

@section('admin_content')
<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table-noor min-w-[680px]">
            <thead>
                <tr>
                    <th>الجهاز</th>
                    <th>طلبات مرتبطة</th>
                    <th>الملحقات</th>
                    <th>الترتيب</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($deviceTypes as $dt)
                    <tr x-data="{ edit: false }">
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10 text-primary shrink-0">
                                    {!! device_icon($dt->name, 'h-4 w-4') !!}
                                </div>
                                <b>{{ $dt->name }}</b>
                            </div>
                        </td>
                        <td>{{ $counts[$dt->name] ?? 0 }}</td>
                        <td>
                            @if ($dt->accessories)
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($dt->accessories as $acc)
                                        <span class="badge bg-muted text-muted-foreground border-border text-[10px]">{{ $acc }}</span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-muted-foreground text-xs">—</span>
                            @endif
                        </td>
                        <td><span class="badge bg-muted text-muted-foreground border-border text-[10px]">{{ $dt->sort_order }}</span></td>
                        <td>
                            <span class="badge {{ $dt->is_active ? 'bg-green-100 dark:bg-green-950/40 text-green-700 dark:text-green-300 border-green-200 dark:border-green-900' : 'bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-300 border-red-200 dark:border-red-900' }} text-[10px]">
                                {{ $dt->is_active ? 'ظاهر' : 'مخفي' }}
                            </span>
                        </td>
                        <td>
                            <div class="flex items-center gap-1.5">
                                <button @click="edit = !edit" class="btn btn-outline btn-sm">{!! icon('edit', 'h-3.5 w-3.5') !!}</button>
                                <form method="POST" action="{{ route('admin.device-types.destroy', $dt) }}" onsubmit="return confirm('حذف {{ $dt->name }}؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm">{!! icon('trash', 'h-3.5 w-3.5') !!}</button>
                                </form>
                            </div>

                            <form x-show="edit" x-transition method="POST" action="{{ route('admin.device-types.update', $dt) }}" class="mt-3 p-3 rounded-xl bg-muted/30 border border-border/60 space-y-2" style="display:none">
                                @csrf
                                @method('PUT')
                                <input name="name" value="{{ $dt->name }}" class="input py-1.5 text-xs" required>
                                <input name="accessories" value="{{ implode('، ', $dt->accessories ?: []) }}" placeholder="الملحقات (بالفاصلة)" class="input py-1.5 text-xs">
                                <div class="grid grid-cols-2 gap-2">
                                    <input name="sort_order" type="number" value="{{ $dt->sort_order }}" class="input py-1.5 text-xs" dir="ltr">
                                    <label class="flex items-center gap-2 text-xs cursor-pointer select-none">
                                        <input type="checkbox" name="is_active" value="1" {{ $dt->is_active ? 'checked' : '' }} class="accent-primary w-4 h-4">
                                        ظاهر للعملاء
                                    </label>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm w-full">حفظ</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted-foreground py-8">مفيش أنواع أجهزة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- مودال إضافة نوع --}}
<dialog id="device-modal" class="modal-backdrop">
    <form method="POST" action="{{ route('admin.device-types.store') }}" class="modal-box max-w-md">
        @csrf
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg">إضافة نوع جهاز</h3>
            <button type="button" onclick="document.getElementById('device-modal').close()" class="flex h-8 w-8 items-center justify-center rounded-lg hover:bg-muted/60 cursor-pointer">{!! icon('x', 'h-4 w-4') !!}</button>
        </div>
        <div class="space-y-3">
            <div>
                <label class="label">اسم النوع *</label>
                <input name="name" value="{{ old('name') }}" placeholder="مثال: مروحة" class="input {{ $errors->has('name') ? 'input-error' : '' }}" required>
                @error('name')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">الملحقات (مفصولة بفاصلة — 5 كحد أقصى)</label>
                <input name="accessories" value="{{ old('accessories') }}" placeholder="ريموت، فلتر، خرطوم" class="input">
                <p class="text-[10px] text-muted-foreground mt-1">الملحقات بتظهر للعميل مع الجهاز عند الطلب — بتساعد الفني يجيب القطع الصح</p>
            </div>
            <div>
                <label class="label">ترتيب الظهور</label>
                <input name="sort_order" type="number" value="{{ old('sort_order', 99) }}" class="input" dir="ltr">
            </div>
            <button type="submit" class="btn btn-primary w-full">{!! icon('plus', 'h-4 w-4') !!} إضافة</button>
        </div>
    </form>
</dialog>

<style>
    dialog.modal-backdrop { border: none; background: transparent; padding: 0; max-width: none; max-height: none; }
    dialog.modal-backdrop::backdrop { background: rgb(0 0 0 / 0.5); backdrop-filter: blur(4px); }
    dialog.modal-box { background: var(--card); color: var(--foreground); border-radius: 1rem; padding: 1.5rem; width: 90vw; max-height: 85vh; overflow-y: auto; border: 1px solid var(--border); box-shadow: 0 20px 40px -10px rgb(0 0 0 / 0.3); }
</style>
@endsection
