@extends('layouts.app')
@section('title', 'حسابي الشخصي')

@section('content')
<div class="container mx-auto max-w-2xl px-4 sm:px-6 py-8">
    <div class="text-center mb-8">
        <div class="mx-auto mb-3 flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-primary to-primary/70 text-primary-foreground text-3xl font-extrabold shadow-lg shadow-primary/20">
            {{ auth()->user()->initials() }}
        </div>
        <h1 class="text-2xl font-extrabold mb-0.5">{{ auth()->user()->name }}</h1>
        <p class="text-muted-foreground text-sm">
            {{ auth()->user()->roleLabel() }} —
            <span dir="ltr">{{ auth()->user()->phone }}</span>
        </p>
        <p class="text-muted-foreground text-xs mt-1">عضو منذ {{ dt(auth()->user()->created_at) }}</p>
    </div>

    {{-- إحصائيات (للعميل) --}}
    @if (auth()->user()->role === 'CUSTOMER')
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div class="card p-4 text-center card-hover">
            <div class="text-2xl font-extrabold text-primary">{{ auth()->user()->requests_count }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">إجمالي الطلبات</div>
        </div>
        <div class="card p-4 text-center card-hover">
            <div class="text-2xl font-extrabold text-green-600 dark:text-green-400">{{ $completed }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">صيانات مكتملة</div>
        </div>
        <div class="card p-4 text-center card-hover">
            <div class="text-2xl font-extrabold text-amber-600 dark:text-amber-400">{{ auth()->user()->loyalty_points }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">{!! 'نقاط ولاء' !!}</div>
        </div>
        <div class="card p-4 text-center card-hover">
            <div class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400">{{ $warrantyActive }}</div>
            <div class="text-xs text-muted-foreground mt-0.5">ضمان ساري</div>
        </div>
    </div>

    @if (auth()->user()->loyalty_points > 0)
        <div class="card p-4 mb-6 bg-gradient-to-l from-amber-50 to-transparent dark:from-amber-950/30 border-amber-200 dark:border-amber-900">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 shrink-0">
                    {!! icon('gift', 'h-5 w-5') !!}
                </div>
                <div class="flex-1">
                    <p class="text-sm font-bold">عندك {{ auth()->user()->loyalty_points }} نقطة ولاء</p>
                    <p class="text-xs text-muted-foreground">إجمالي إنفاقك: {{ money(auth()->user()->total_spent) }} — كل جنيه صيانة = نقطة</p>
                </div>
            </div>
        </div>
    @endif
    @endif

    {{-- تعديل البيانات --}}
    <div class="card p-5 md:p-6 mb-4">
        <h2 class="font-bold mb-4 flex items-center gap-2">{!! icon('user-cog', 'h-4 w-4 text-primary') !!} البيانات الشخصية</h2>
        <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="label" for="name">الاسم</label>
                <input id="name" name="name" value="{{ old('name', auth()->user()->name) }}" class="input {{ $errors->has('name') ? 'input-error' : '' }}" required>
                @error('name')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="label" for="phone">رقم الموبايل</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone', auth()->user()->phone) }}" dir="ltr" style="text-align: right"
                       class="input {{ $errors->has('phone') ? 'input-error' : '' }}" required>
                @error('phone')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            @if (auth()->user()->role === 'TECHNICIAN')
                <div>
                    <label class="label" for="specialty">التخصص</label>
                    <input id="specialty" name="specialty" value="{{ old('specialty', auth()->user()->specialty) }}" placeholder="مثال: تكييف، غسالات" class="input">
                </div>
            @endif

            <button type="submit" class="btn btn-primary">
                {!! icon('save', 'h-4 w-4') !!}
                حفظ البيانات
            </button>
        </form>
    </div>

    {{-- تغيير كلمة المرور --}}
    <div class="card p-5 md:p-6">
        <h2 class="font-bold mb-4 flex items-center gap-2">{!! icon('key', 'h-4 w-4 text-primary') !!} تغيير كلمة المرور</h2>
        <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="label" for="current_password">كلمة المرور الحالية</label>
                <input id="current_password" name="current_password" type="password" class="input {{ $errors->has('current_password') ? 'input-error' : '' }}" required>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="label" for="password">كلمة المرور الجديدة</label>
                    <input id="password" name="password" type="password" placeholder="6 أحرف على الأقل" class="input {{ $errors->has('password') ? 'input-error' : '' }}" required>
                </div>
                <div>
                    <label class="label" for="password_confirmation">تأكيد كلمة المرور</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="input" required>
                </div>
            </div>
            @error('password')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror

            <button type="submit" class="btn btn-outline">
                {!! icon('lock', 'h-4 w-4') !!}
                تغيير كلمة المرور
            </button>
        </form>
    </div>
</div>
@endsection
