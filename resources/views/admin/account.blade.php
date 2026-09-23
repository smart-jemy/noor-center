@extends('admin.layout')
@section('title', 'حسابي — لوحة التحكم')
@section('admin_title', 'حسابي الشخصي')
@section('admin_subtitle', 'بيانات دخول حساب الأدمن')

@section('admin_content')
<div class="grid md:grid-cols-2 gap-4">

    {{-- البيانات --}}
    <div class="card p-5">
        <h2 class="font-bold mb-4 flex items-center gap-2">{!! icon('user-cog', 'h-4 w-4 text-primary') !!} بيانات الحساب</h2>
        <form method="POST" action="{{ route('admin.account.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="flex items-center gap-3 mb-2">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-primary to-primary/70 text-primary-foreground text-xl font-extrabold">
                    {{ mb_substr($user->name, 0, 1) }}
                </div>
                <div>
                    <div class="font-bold">{{ $user->name }}</div>
                    <div class="text-xs text-muted-foreground">{{ $user->roleLabel() }} — منذ {{ dt($user->created_at) }}</div>
                </div>
            </div>

            <div>
                <label class="label">الاسم</label>
                <input name="name" value="{{ old('name', $user->name) }}" class="input {{ $errors->has('name') ? 'input-error' : '' }}" required>
                @error('name')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="label">رقم الموبايل</label>
                <input name="phone" type="tel" value="{{ old('phone', $user->phone) }}" dir="ltr" style="text-align: right" class="input {{ $errors->has('phone') ? 'input-error' : '' }}" required>
                @error('phone')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="label">كلمة مرور جديدة <span class="text-muted-foreground font-normal">(اتركها فاضية لو مش عايز تغيرها)</span></label>
                <input name="password" type="password" placeholder="6 أحرف على الأقل" class="input {{ $errors->has('password') ? 'input-error' : '' }}">
                <input name="password_confirmation" type="password" placeholder="تأكيد كلمة المرور" class="input mt-2">
                @error('password')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="btn btn-primary w-full">{!! icon('save', 'h-4 w-4') !!} حفظ البيانات</button>
        </form>
    </div>

    {{-- أدوات النظام --}}
    <div class="space-y-4">
        <div class="card p-5">
            <h2 class="font-bold mb-4 flex items-center gap-2">{!! icon('download', 'h-4 w-4 text-primary') !!} نسخة احتياطية</h2>
            <p class="text-sm text-muted-foreground leading-relaxed mb-4">
                نزّل نسخة كاملة من قاعدة البيانات (كل الطلبات والعملاء والإعدادات) —
                احتفظ بها في مكان آمن وارفعها مرة أخرى لو حصلت أي مشكلة.
            </p>
            <a href="{{ route('admin.backup') }}" class="btn btn-outline w-full">{!! icon('download', 'h-4 w-4') !!} تنزيل النسخة الاحتياطية</a>
        </div>

        <div class="card p-5 border-red-200 dark:border-red-900">
            <h2 class="font-bold mb-2 flex items-center gap-2 text-red-600 dark:text-red-400">{!! icon('warning', 'h-4 w-4') !!} منطقة الخطر</h2>
            <p class="text-sm text-muted-foreground leading-relaxed mb-4">
                تصفير النظام بيمسح <b>كل</b> البيانات: الطلبات، العملاء، المخزون، الشركاء، المصروفات —
                وبيسيب حسابات الأدمن فقط. الإجراء ده <b>مش بيرجع</b>.
            </p>
            <form method="POST" action="{{ route('admin.reset') }}" onsubmit="return confirm('⚠ متأكد؟ ده هيمسح كل البيانات نهائياً!')">
                @csrf
                <div class="flex gap-2">
                    <input name="confirm" placeholder="اكتب RESET للتأكيد" class="input flex-1" dir="ltr" style="text-align: right">
                    <button type="submit" class="btn btn-danger">{!! icon('trash', 'h-4 w-4') !!} تصفير</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
