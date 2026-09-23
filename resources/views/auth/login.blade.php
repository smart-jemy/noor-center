@extends('layouts.app')
@section('title', 'تسجيل الدخول')

@section('content')
<div class="container mx-auto max-w-md px-4 sm:px-6 py-12">
    <div class="text-center mb-8 fade-in-up">
        <img src="{{ asset('logo.jpg') }}" alt="مركز نور" class="mx-auto h-16 w-16 rounded-2xl object-cover shadow-lg ring-1 ring-foreground/5 mb-4">
        <h1 class="text-2xl font-extrabold mb-1">أهلاً بعودتك 👋</h1>
        <p class="text-muted-foreground text-sm">سجل دخولك بموبايلك وكمّل من حيث وقفت</p>
    </div>

    <div class="card p-6 md:p-8">
        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label class="label" for="phone">رقم الموبايل</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="01xxxxxxxxx" dir="ltr" style="text-align: right"
                       class="input {{ $errors->has('phone') ? 'input-error' : '' }}" required autofocus autocomplete="tel">
            </div>

            <div>
                <label class="label" for="password">كلمة المرور</label>
                <div class="relative">
                    <input id="password" name="password" type="password" placeholder="••••••••"
                           class="input {{ $errors->has('password') ? 'input-error' : '' }} pl-11" required autocomplete="current-password">
                    <button type="button" onclick="togglePassword(this)" class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground cursor-pointer">
                        {!! icon('eye', 'h-4 w-4') !!}
                    </button>
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-muted-foreground cursor-pointer select-none">
                <input type="checkbox" name="remember" class="accent-primary w-4 h-4 rounded">
                خليني داخل على الجهاز ده
            </label>

            <button type="submit" class="btn btn-primary w-full btn-lg">
                {!! icon('log-in', 'h-4 w-4') !!}
                تسجيل الدخول
            </button>
        </form>

        <div class="mt-6 text-center text-sm text-muted-foreground">
            معندكش حساب؟
            <a href="{{ route('register') }}" class="text-primary font-bold hover:underline">إنشاء حساب جديد</a>
        </div>
    </div>

    <div class="mt-6 card p-4 bg-primary/5 border-primary/20">
        <div class="flex items-start gap-2 text-xs text-muted-foreground leading-relaxed">
            {!! icon('info', 'h-4 w-4 text-primary shrink-0 mt-0.5') !!}
            <div>
                كل الأدوار بتدخل من هنا: <b>العميل، الاستقبال، الفني، مدير القسم، الأدمن</b> —
                كل واحد يوصل للوحته الخاصة بيه تلقائياً بعد الدخول.
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(btn) {
    const input = document.getElementById('password');
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>
@endsection
