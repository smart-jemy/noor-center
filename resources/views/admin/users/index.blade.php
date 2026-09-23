@extends('admin.layout')
@section('title', 'الموظفون — لوحة التحكم')
@section('admin_title', 'الموظفون')
@section('admin_subtitle', 'إدارة حسابات الفريق: فنيون، استقبال، مديري أقسام، أدمنز')

@section('admin_actions')
    <button x-data="{}" @click="document.getElementById('user-modal').showModal()" class="btn btn-primary btn-sm">{!! icon('user-plus', 'h-3.5 w-3.5') !!} موظف جديد</button>
@endsection

@section('admin_content')
{{-- كروت إحصائية للفريق — زي الأصل --}}
@php
    $totalTechnicians = \App\Models\User::where('role', 'TECHNICIAN')->count();
    $activeTechnicians = \App\Models\User::where('role', 'TECHNICIAN')->where('is_active', true)->count();
    $totalReception = \App\Models\User::where('role', 'RECEPTION')->count();
    $unassignedPending = \App\Models\ServiceRequest::where('status', 'PENDING')->whereNull('assigned_technician_id')->count();
@endphp
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
    <div class="card p-4 text-center">
        <div class="text-2xl font-extrabold text-primary">{{ $totalTechnicians }}</div>
        <div class="text-xs text-muted-foreground mt-0.5">إجمالي الفنيين</div>
    </div>
    <div class="card p-4 text-center">
        <div class="text-2xl font-extrabold text-green-600 dark:text-green-400">{{ $activeTechnicians }}</div>
        <div class="text-xs text-muted-foreground mt-0.5">فنيين نشطين</div>
    </div>
    <div class="card p-4 text-center">
        <div class="text-2xl font-extrabold text-blue-600 dark:text-blue-400">{{ $totalReception }}</div>
        <div class="text-xs text-muted-foreground mt-0.5">مستقبلين</div>
    </div>
    <a href="{{ route('admin.requests.index', ['status' => 'PENDING']) }}" class="card p-4 text-center card-hover {{ $unassignedPending > 0 ? 'border-red-300 dark:border-red-900/60 bg-red-50/60 dark:bg-red-950/30' : '' }}">
        <div class="text-2xl font-extrabold text-red-600 dark:text-red-400">{{ $unassignedPending }}</div>
        <div class="text-xs text-muted-foreground mt-0.5">طلبات بدون فني</div>
    </a>
</div>

<form method="GET" class="card p-4 mb-4">
    <div class="flex flex-wrap gap-3 items-center">
        <div class="relative flex-1 min-w-[200px]">
            {!! icon('search', 'h-4 w-4 absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground') !!}
            <input name="q" value="{{ request('q') }}" placeholder="بحث بالاسم أو الهاتف..." class="input pr-10">
        </div>
        <select name="role" class="input w-auto">
            <option value="">كل الأدوار</option>
            @foreach (\App\Models\User::ROLES as $key => $label)
                <option value="{{ $key }}" {{ request('role') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-primary btn-sm">{!! icon('filter', 'h-3.5 w-3.5') !!} فلترة</button>
        @if (request()->hasAny(['q', 'role']))
            <a href="{{ route('admin.users.index') }}" class="btn btn-ghost btn-sm">مسح</a>
        @endif
    </div>
</form>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table-noor min-w-[760px]">
            <thead>
                <tr>
                    <th>الموظف</th>
                    <th>الدور</th>
                    <th>القسم / التخصص</th>
                    <th>الطلبات المسندة</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $u)
                    <tr x-data="{ edit: false }">
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-primary to-primary/70 text-primary-foreground text-xs font-bold shrink-0">{{ mb_substr($u->name, 0, 1) }}</div>
                                <div>
                                    <div class="font-bold">{{ $u->name }}</div>
                                    <div class="text-[10px] text-muted-foreground" dir="ltr">{{ $u->phone }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-primary/10 text-primary border-primary/20">{{ $u->roleLabel() }}</span></td>
                        <td class="text-xs">
                            @if ($u->role === 'TECHNICIAN')
                                {{ $u->specialty ?: '—' }}
                                @if ($u->manager) <div class="text-muted-foreground">تحت: {{ $u->manager->name }}</div> @endif
                            @elseif ($u->role === 'DEPARTMENT_MANAGER')
                                {{ $u->department?->name ?: '⚠ بدون قسم' }}
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $u->role === 'TECHNICIAN' ? $u->assigned_count : '—' }}</td>
                        <td>
                            <span class="badge {{ $u->is_active ? 'bg-green-100 dark:bg-green-950/40 text-green-700 dark:text-green-300 border-green-200 dark:border-green-900' : 'bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-300 border-red-200 dark:border-red-900' }}">
                                {{ $u->is_active ? 'نشط' : 'معطل' }}
                            </span>
                        </td>
                        <td>
                            <div class="flex items-center gap-1.5">
                                <button @click="edit = !edit" class="btn btn-outline btn-sm" title="تعديل">{!! icon('edit', 'h-3.5 w-3.5') !!}</button>
                                <form method="POST" action="{{ route('admin.users.toggle', $u) }}" onsubmit="return confirm('{{ $u->is_active ? 'تعطيل' : 'تفعيل' }} حساب {{ $u->name }}؟')">
                                    @csrf
                                    <button class="btn btn-sm {{ $u->is_active ? 'btn-danger' : 'btn-success' }}" title="{{ $u->is_active ? 'تعطيل' : 'تفعيل' }}">
                                        {!! icon('power', 'h-3.5 w-3.5') !!}
                                    </button>
                                </form>
                                {{-- حذف الموظف — زي الأصل (ممنوع للأدمن) --}}
                                @if ($u->role !== 'ADMIN')
                                    <form method="POST" action="{{ route('admin.users.destroy', $u) }}" onsubmit="return confirm('حذف حساب {{ $u->name }} نهائياً؟{{ $u->role === 'TECHNICIAN' ? ' طلباته المسلَّمة هتفضل موجودة بدون فني.' : '' }}')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm bg-red-100 dark:bg-red-950/60 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-900 hover:bg-red-200 dark:hover:bg-red-900" title="حذف">
                                            {!! icon('trash', 'h-3.5 w-3.5') !!}
                                        </button>
                                    </form>
                                @endif
                            </div>

                            {{-- نموذج التعديل --}}
                            <form x-show="edit" x-transition method="POST" action="{{ route('admin.users.update', $u) }}" class="mt-3 p-3 rounded-xl bg-muted/30 border border-border/60 space-y-2" style="display:none">
                                @csrf
                                @method('PUT')
                                <input name="name" value="{{ $u->name }}" class="input py-1.5 text-xs" required>
                                <input name="phone" value="{{ $u->phone }}" class="input py-1.5 text-xs" dir="ltr" required>
                                <input name="password" type="password" placeholder="كلمة مرور جديدة (اتركها فاضية)" class="input py-1.5 text-xs">
                                <select name="role" class="input py-1.5 text-xs">
                                    @foreach (\App\Models\User::ROLES as $key => $label)
                                        <option value="{{ $key }}" {{ $u->role === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <input name="specialty" value="{{ $u->specialty }}" placeholder="تخصص الفني" class="input py-1.5 text-xs">
                                <select name="department_id" class="input py-1.5 text-xs">
                                    <option value="">— بدون قسم —</option>
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ $u->department_id === $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                                <select name="managed_by" class="input py-1.5 text-xs">
                                    <option value="">— بدون مدير —</option>
                                    @foreach ($managers as $mgr)
                                        <option value="{{ $mgr->id }}" {{ $u->managed_by === $mgr->id ? 'selected' : '' }}>{{ $mgr->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-primary btn-sm w-full">حفظ التعديل</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted-foreground py-8">مفيش موظفين مطابقين</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{ $users->links() }}

{{-- مودال إضافة موظف --}}
<dialog id="user-modal" class="modal-backdrop">
    <form method="POST" action="{{ route('admin.users.store') }}" class="modal-box max-w-md">
        @csrf
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg">إضافة موظف جديد</h3>
            <button type="button" onclick="document.getElementById('user-modal').close()" class="flex h-8 w-8 items-center justify-center rounded-lg hover:bg-muted/60 cursor-pointer">{!! icon('x', 'h-4 w-4') !!}</button>
        </div>
        <div class="space-y-3">
            <div>
                <label class="label">الاسم *</label>
                <input name="name" value="{{ old('name') }}" class="input {{ $errors->has('name') ? 'input-error' : '' }}" required>
                @error('name')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">رقم الموبايل *</label>
                <input name="phone" type="tel" value="{{ old('phone') }}" dir="ltr" style="text-align: right" class="input {{ $errors->has('phone') ? 'input-error' : '' }}" required>
                @error('phone')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">كلمة المرور *</label>
                <input name="password" type="password" placeholder="6 أحرف على الأقل" class="input {{ $errors->has('password') ? 'input-error' : '' }}" required>
                @error('password')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">الدور *</label>
                <select name="role" class="input" required>
                    @foreach (\App\Models\User::ROLES as $key => $label)
                        @if ($key !== 'CUSTOMER')
                            <option value="{{ $key }}" {{ old('role') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">التخصص (للفني)</label>
                <input name="specialty" value="{{ old('specialty') }}" placeholder="مثال: تكييف" class="input">
            </div>
            <div>
                <label class="label">القسم (لمدير القسم)</label>
                <select name="department_id" class="input">
                    <option value="">— قسم جديد بالبيانات دي —</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
                {{-- بيانات القسم الجديد — زي الأصل --}}
                <div class="mt-2 p-3 rounded-xl bg-muted/30 border border-border/60 space-y-2">
                    <p class="text-xs font-bold text-muted-foreground">بيانات القسم الجديد <span class="font-normal">(لو اخترت «قسم جديد»)</span></p>
                    <input name="new_department_name" value="{{ old('new_department_name') }}" placeholder="اسم القسم *" class="input py-1.5 text-xs">
                    @error('new_department_name')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
                    <input name="new_department_device_types" value="{{ old('new_department_device_types') }}" placeholder="أنواع الأجهزة (افصل بفاصلة) *" class="input py-1.5 text-xs">
                    @error('new_department_device_types')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
                    <input name="new_department_description" value="{{ old('new_department_description') }}" placeholder="وصف القسم (اختياري)" class="input py-1.5 text-xs">
                </div>
                @error('department_id')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn btn-primary w-full">{!! icon('user-plus', 'h-4 w-4') !!} إنشاء الحساب</button>
        </div>
    </form>
</dialog>

<style>
    dialog.modal-backdrop { border: none; background: transparent; padding: 0; max-width: none; max-height: none; }
    dialog.modal-backdrop::backdrop { background: rgb(0 0 0 / 0.5); backdrop-filter: blur(4px); }
    dialog.modal-box { background: var(--card); color: var(--foreground); border-radius: 1rem; padding: 1.5rem; width: 90vw; max-height: 85vh; overflow-y: auto; border: 1px solid var(--border); box-shadow: 0 20px 40px -10px rgb(0 0 0 / 0.3); }
</style>
@endsection
