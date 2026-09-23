@extends('admin.layout')
@section('title', 'الأقسام — لوحة التحكم')
@section('admin_title', 'الأقسام')
@section('admin_subtitle', 'أقسام المركز — لكل قسم مدير وفنيون ومخزن وطلبات')

@section('admin_actions')
    <button onclick="document.getElementById('dept-modal').showModal()" class="btn btn-primary btn-sm">{!! icon('plus', 'h-3.5 w-3.5') !!} قسم جديد</button>
@endsection

@section('admin_content')
<div class="grid md:grid-cols-2 gap-4">
    @forelse ($departments as $dept)
        <div class="card p-5" x-data="{ edit: false }">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary shrink-0">
                        {!! icon($dept->icon ?: 'layers', 'h-5 w-5') !!}
                    </div>
                    <div>
                        <h3 class="font-bold">{{ $dept->name }}</h3>
                        <p class="text-xs text-muted-foreground">{{ $dept->manager?->name ? 'مدير: '.$dept->manager->name : '⚠ بدون مدير' }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="badge {{ $dept->is_active ? 'bg-green-100 dark:bg-green-950/40 text-green-700 dark:text-green-300 border-green-200 dark:border-green-900' : 'bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-300 border-red-200 dark:border-red-900' }} text-[10px]">{{ $dept->is_active ? 'نشط' : 'معطل' }}</span>
                    <button @click="edit = !edit" class="btn btn-outline btn-sm">{!! icon('edit', 'h-3.5 w-3.5') !!}</button>
                    <form method="POST" action="{{ route('admin.departments.destroy', $dept) }}" onsubmit="return confirm('حذف قسم {{ $dept->name }}؟ (ممكن لو مفيش طلبات مرتبطة)')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm">{!! icon('trash', 'h-3.5 w-3.5') !!}</button>
                    </form>
                </div>
            </div>

            @if ($dept->device_types)
                <div class="flex flex-wrap gap-1.5 mb-3">
                    @foreach ($dept->device_types as $dt)
                        <span class="badge bg-muted text-muted-foreground border-border text-[10px]">{{ $dt }}</span>
                    @endforeach
                </div>
            @endif

            @if ($dept->description)
                <p class="text-xs text-muted-foreground mb-3 leading-relaxed">{{ $dept->description }}</p>
            @endif

            <div class="grid grid-cols-3 gap-2 text-center">
                <div class="p-2 rounded-xl bg-muted/40">
                    <div class="font-extrabold text-sm">{{ $dept->requests_count }}</div>
                    <div class="text-[10px] text-muted-foreground">طلب</div>
                </div>
                <div class="p-2 rounded-xl bg-muted/40">
                    <div class="font-extrabold text-sm">{{ $dept->technicians_count }}</div>
                    <div class="text-[10px] text-muted-foreground">فني</div>
                </div>
                <div class="p-2 rounded-xl bg-muted/40">
                    <div class="font-extrabold text-sm">{{ $dept->items_count }}</div>
                    <div class="text-[10px] text-muted-foreground">صنف مخزن</div>
                </div>
            </div>

            {{-- تعديل --}}
            <form x-show="edit" x-transition method="POST" action="{{ route('admin.departments.update', $dept) }}" class="mt-4 pt-4 border-t border-border/60 space-y-3" style="display:none">
                @csrf
                @method('PUT')
                <div>
                    <label class="label">اسم القسم</label>
                    <input name="name" value="{{ $dept->name }}" class="input" required>
                </div>
                <div>
                    <label class="label">أنواع الأجهزة (مفصولة بفاصلة)</label>
                    <input name="device_types" value="{{ implode('، ', $dept->device_types ?: []) }}" class="input" placeholder="تكييف، سبليت، مروحة">
                    <p class="text-[10px] text-muted-foreground mt-1">الطلبات بتتوجه للقسم تلقائياً حسب نوع الجهاز</p>
                </div>
                <div>
                    <label class="label">الوصف</label>
                    <input name="description" value="{{ $dept->description }}" class="input">
                </div>
                <label class="flex items-center gap-2 text-sm cursor-pointer select-none">
                    <input type="checkbox" name="is_active" value="1" {{ $dept->is_active ? 'checked' : '' }} class="accent-primary w-4 h-4">
                    القسم نشط
                </label>
                <button type="submit" class="btn btn-primary btn-sm w-full">حفظ التعديلات</button>
            </form>
        </div>
    @empty
        <div class="card p-8 text-center md:col-span-2">
            <p class="text-muted-foreground text-sm mb-3">مفيش أقسام — أضف أول قسم للمركز</p>
            <button onclick="document.getElementById('dept-modal').showModal()" class="btn btn-primary btn-sm">{!! icon('plus', 'h-3.5 w-3.5') !!} إضافة قسم</button>
        </div>
    @endforelse
</div>

{{-- مودال إضافة قسم --}}
<dialog id="dept-modal" class="modal-backdrop">
    <form method="POST" action="{{ route('admin.departments.store') }}" class="modal-box max-w-md">
        @csrf
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg">إضافة قسم جديد</h3>
            <button type="button" onclick="document.getElementById('dept-modal').close()" class="flex h-8 w-8 items-center justify-center rounded-lg hover:bg-muted/60 cursor-pointer">{!! icon('x', 'h-4 w-4') !!}</button>
        </div>
        <div class="space-y-3">
            <div>
                <label class="label">اسم القسم *</label>
                <input name="name" value="{{ old('name') }}" placeholder="مثال: تكييف وتبريد" class="input {{ $errors->has('name') ? 'input-error' : '' }}" required>
                @error('name')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">أنواع الأجهزة (مفصولة بفاصلة)</label>
                <input name="device_types" value="{{ old('device_types') }}" placeholder="تكييف، سبليت، مروحة" class="input">
            </div>
            <div>
                <label class="label">الوصف</label>
                <input name="description" value="{{ old('description') }}" class="input">
            </div>
            <button type="submit" class="btn btn-primary w-full">{!! icon('plus', 'h-4 w-4') !!} إضافة القسم</button>
        </div>
    </form>
</dialog>

<style>
    dialog.modal-backdrop { border: none; background: transparent; padding: 0; max-width: none; max-height: none; }
    dialog.modal-backdrop::backdrop { background: rgb(0 0 0 / 0.5); backdrop-filter: blur(4px); }
    dialog.modal-box { background: var(--card); color: var(--foreground); border-radius: 1rem; padding: 1.5rem; width: 90vw; max-height: 85vh; overflow-y: auto; border: 1px solid var(--border); box-shadow: 0 20px 40px -10px rgb(0 0 0 / 0.3); }
</style>
@endsection
