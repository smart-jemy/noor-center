@extends('layouts.app')
@section('title', 'إنشاء حساب')

@section('content')
<div class="container mx-auto max-w-md px-4 sm:px-6 py-12">
    <div class="text-center mb-8 fade-in-up">
        <img src="{{ asset('logo.jpg') }}" alt="مركز نور" class="mx-auto h-16 w-16 rounded-2xl object-cover shadow-lg ring-1 ring-foreground/5 mb-4">
        <h1 class="text-2xl font-extrabold mb-1">انضم لمركز نور</h1>
        <p class="text-muted-foreground text-sm">احتفظ بسجل صياناتك، الضمان، ونقاط الولاء في مكان واحد</p>
    </div>

    <div class="card p-6 md:p-8">
        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <div>
                <label class="label" for="name">الاسم بالكامل</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="مثال: محمد أحمد"
                       class="input {{ $errors->has('name') ? 'input-error' : '' }}" required autofocus autocomplete="name">
            </div>

            <div>
                <label class="label" for="phone">رقم الموبايل</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="01xxxxxxxxx" dir="ltr" style="text-align: right"
                       class="input {{ $errors->has('phone') ? 'input-error' : '' }}" required autocomplete="tel">
                <p class="text-[10px] text-muted-foreground mt-1">هذا الرقم سيكون وسيلة الدخول والتواصل بخصوص طلباتك</p>
            </div>

            <div>
                <label class="label" for="password">كلمة المرور</label>
                <input id="password" name="password" type="password" placeholder="6 أحرف على الأقل"
                       class="input {{ $errors->has('password') ? 'input-error' : '' }}" required autocomplete="new-password">
            </div>

            <div>
                <label class="label" for="password_confirmation">تأكيد كلمة المرور</label>
                <input id="password_confirmation" name="password_confirmation" type="password" placeholder="كرر كلمة المرور"
                       class="input" required autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-primary w-full btn-lg">
                {!! icon('user-plus', 'h-4 w-4') !!}
                إنشاء الحساب
            </button>
        </form>

        <div class="mt-6 text-center text-sm text-muted-foreground">
            عندك حساب بالفعل؟
            <a href="{{ route('login') }}" class="text-primary font-bold hover:underline">سجل دخولك</a>
        </div>
    </div>

    <div class="mt-6 grid gap-2">
        <div class="flex items-center gap-2 text-xs text-muted-foreground"><span class="flex h-6 w-6 items-center justify-center rounded-lg bg-primary/10 text-primary shrink-0">{!! icon('gift', 'h-3.5 w-3.5') !!}</span> نقاط ولاء مع كل صيانة (1 ج.م = 1 نقطة)</div>
        <div class="flex items-center gap-2 text-xs text-muted-foreground"><span class="flex h-6 w-6 items-center justify-center rounded-lg bg-primary/10 text-primary shrink-0">{!! icon('shield', 'h-3.5 w-3.5') !!}</span> سجل كامل لفترات الضمان بتاعتك</div>
        <div class="flex items-center gap-2 text-xs text-muted-foreground"><span class="flex h-6 w-6 items-center justify-center rounded-lg bg-primary/10 text-primary shrink-0">{!! icon('history', 'h-3.5 w-3.5') !!}</span> تاريخ كل طلباتك وتقييماتك</div>
    </div>
</div>
@endsection
