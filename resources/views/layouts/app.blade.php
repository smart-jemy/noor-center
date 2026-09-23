<!DOCTYPE html>
<html lang="ar" dir="rtl" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="{{ in_array(request()->routeIs('admin.*'), [true]) ? 'noindex,nofollow' : 'index,follow' }}">
    <title>@yield('title', 'مركز نور') — مركز نور لصيانة الأجهزة</title>
    <meta name="description" content="@yield('meta_desc', 'صيانة جميع الأجهزة المنزلية في 6 أكتوبر وحدائق الأهرام — فنيون معتمدون، خدمة في نفس اليوم، ضمان على الإصلاح.')">

    {{-- الخطوط المحلية --}}
    <link rel="preload" href="{{ asset('fonts/cairo-arabic.woff2') }}" as="font" type="font/woff2" crossorigin>
    <style>
        @font-face { font-family: 'Cairo'; font-style: normal; font-weight: 400 900; font-display: swap;
            src: url('{{ asset('fonts/cairo-arabic.woff2') }}') format('woff2');
            unicode-range: U+0600-06FF, U+0750-077F, U+FB50-FDFF, U+FE70-FEFC, U+200C-200E, U+2010-2011; }
        @font-face { font-family: 'Cairo'; font-style: normal; font-weight: 400 900; font-display: swap;
            src: url('{{ asset('fonts/cairo-latin.woff2') }}') format('woff2');
            unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD; }
        :root { --font-cairo: 'Cairo', 'Segoe UI', Tahoma, Arial, sans-serif; }
        html:not(.js) .js-only { display: none !important; }
    </style>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="icon" type="image/jpeg" href="{{ asset('logo.jpg') }}">

    {{-- الوضع الليلي فوري (بدون وميض) --}}
    <script>
        document.documentElement.classList.remove('no-js');
        document.documentElement.classList.add('js');
        (function () {
            try {
                var t = localStorage.getItem('noor-theme');
                if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>
    <script defer src="{{ asset('js/alpine.min.js') }}"></script>
    @stack('head')
</head>
<body class="font-sans min-h-screen flex flex-col">
{{-- شاشة الإقلاع 3D (لوجو NC) — بعد دخول الأدمن/الاستقبال، 3 ثواني --}}
@include('partials.boot')

{{-- ===== الهيدر ===== --}}
<header x-data="{ mobileOpen: false, scrolled: false }"
        x-init="window.addEventListener('scroll', () => scrolled = window.scrollY > 8, { passive: true })"
        class="sticky top-0 z-50 w-full transition-all duration-300"
        :class="scrolled ? 'glass border-b border-border/40 shadow-lg shadow-foreground/5' : 'bg-card/60 backdrop-blur-sm border-b border-transparent'">
    <div class="container mx-auto max-w-7xl px-4 sm:px-6">
        <div class="flex h-16 items-center justify-between gap-4">

            {{-- اللوجو --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 transition-transform hover:scale-[1.02] group shrink-0">
                <div class="relative">
                    <img src="{{ asset('logo.jpg') }}" alt="مركز نور"
                         class="h-11 w-11 rounded-xl object-cover shadow-md ring-1 ring-foreground/5 group-hover:shadow-lg transition-shadow">
                </div>
                <div class="flex flex-col items-start leading-tight">
                    <span class="text-lg font-extrabold tracking-tight gradient-text">مركز نور</span>
                    <span class="text-[10px] text-muted-foreground -mt-0.5">لصيانة الأجهزة</span>
                </div>
            </a>

            {{-- التنقل ديسكتوب --}}
            <nav class="hidden md:flex items-center gap-1 p-1 rounded-2xl bg-muted/40 backdrop-blur-sm">
                @php
                    $navItems = [];
                    if (!auth()->check() || auth()->user()->role === 'CUSTOMER') {
                        $navItems[] = ['url' => route('home'), 'label' => 'الرئيسية', 'icon' => 'home', 'active' => request()->routeIs('home')];
                        $navItems[] = ['url' => auth()->check() ? route('requests.create') : route('register'), 'label' => 'اطلب صيانة', 'icon' => 'wrench', 'active' => request()->routeIs('requests.create')];
                    }
                    if (auth()->check()) {
                        $navItems = match (auth()->user()->role) {
                            'ADMIN' => [['url' => route('admin.dashboard'), 'label' => 'لوحة التحكم', 'icon' => 'dashboard', 'active' => request()->routeIs('admin.*'), 'badge' => $unreadBadge]],
                            'RECEPTION' => [['url' => route('reception.index'), 'label' => 'لوحة الاستقبال', 'icon' => 'phone-call', 'active' => request()->routeIs('reception.*'), 'badge' => $unreadBadge]],
                            'DEPARTMENT_MANAGER' => [['url' => route('department.index'), 'label' => 'لوحة القسم', 'icon' => 'layers', 'active' => request()->routeIs('department.*'), 'badge' => $unreadBadge]],
                            'TECHNICIAN' => [['url' => route('technician'), 'label' => 'طلباتي (فني)', 'icon' => 'hard-hat', 'active' => request()->routeIs('technician'), 'badge' => $unreadBadge]],
                            default => [['url' => route('requests.index'), 'label' => 'طلباتي', 'icon' => 'clipboard-list', 'active' => request()->routeIs('requests.*')]],
                        };
                    }
                @endphp
                @foreach ($navItems as $item)
                    <a href="{{ $item['url'] }}"
                       class="relative flex items-center gap-2 rounded-xl px-3.5 py-2 text-sm font-medium transition-all duration-200 {{ ($item['active'] ?? false) ? 'bg-card text-primary shadow-sm' : 'text-muted-foreground hover:text-foreground hover:bg-card/50' }}">
                        {!! icon($item['icon'], 'h-4 w-4') !!}
                        {{ $item['label'] }}
                        @if (($item['badge'] ?? 0) > 0)
                            <span class="absolute -top-1 -left-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white ring-2 ring-card pulse-soft">
                                {{ $item['badge'] > 99 ? '99+' : $item['badge'] }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </nav>

            {{-- الإجراءات يمين --}}
            <div class="flex items-center gap-1.5">
                {{-- الوضع الليلي --}}
                <button onclick="toggleTheme()" aria-label="تبديل الوضع الليلي"
                        class="flex h-9 w-9 items-center justify-center rounded-xl hover:bg-muted/60 text-foreground cursor-pointer">
                    <span class="dark:hidden">{!! icon('moon', 'h-4 w-4') !!}</span>
                    <span class="hidden dark:inline text-amber-500">{!! icon('sun', 'h-4 w-4') !!}</span>
                </button>

                @auth
                    {{-- الإشعارات (للطاقم) --}}
                    @if (auth()->user()->isStaff() && $unreadBadge > 0)
                        <a href="{{ match(auth()->user()->role) {
                            'ADMIN' => route('admin.notifications'),
                            'RECEPTION' => route('reception.notifications'),
                            'DEPARTMENT_MANAGER' => route('department.notifications'),
                            default => url('/technician'),
                        } }}"
                           class="relative flex h-9 w-9 items-center justify-center rounded-xl hover:bg-muted/60 cursor-pointer" aria-label="الإشعارات">
                            {!! icon('bell', 'h-5 w-5') !!}
                            <span class="absolute -top-0.5 -left-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white ring-2 ring-card pulse-soft">
                                {{ $unreadBadge > 99 ? '99+' : $unreadBadge }}
                            </span>
                        </a>
                    @endif

                    {{-- قائمة المستخدم --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" @click.outside="open = false"
                                class="flex items-center gap-2 rounded-xl pl-2 pr-3 py-1.5 hover:bg-muted/60 cursor-pointer">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-primary to-primary/70 text-primary-foreground text-sm font-bold shadow-sm">
                                {{ auth()->user()->initials() }}
                            </div>
                            <span class="hidden sm:inline max-w-[100px] truncate font-medium text-sm">{{ auth()->user()->name }}</span>
                            {!! icon('chevron-down', 'h-3.5 w-3.5 text-muted-foreground') !!}
                        </button>
                        <div x-show="open" x-transition.opacity.duration.150ms
                             class="absolute mt-2 left-0 sm:left-auto sm:right-0 w-56 glass border border-border/40 rounded-2xl shadow-xl p-1.5 z-50">
                            <div class="px-3 py-2.5 border-b border-border/60 mb-1">
                                <div class="font-bold text-sm">{{ auth()->user()->name }}</div>
                                <div class="text-xs text-muted-foreground" dir="ltr">{{ auth()->user()->phone }}</div>
                                <div class="text-[10px] text-primary mt-0.5 font-medium">{{ auth()->user()->roleLabel() }}</div>
                            </div>
                            @if (auth()->user()->role === 'CUSTOMER')
                                <a href="{{ route('requests.index') }}" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm hover:bg-muted/60"><span class="mr-auto">طلباتي</span>{!! icon('clipboard-list', 'h-4 w-4') !!}</a>
                            @endif
                            <a href="{{ route('profile') }}" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm hover:bg-muted/60"><span class="mr-auto">حسابي الشخصي</span>{!! icon('user-cog', 'h-4 w-4') !!}</a>
                            <div class="h-px bg-border/60 my-1"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2 rounded-xl px-3 py-2 text-sm text-destructive hover:bg-destructive/10 cursor-pointer">
                                    <span class="mr-auto">تسجيل الخروج</span>{!! icon('log-out', 'h-4 w-4') !!}
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth
                @guest
                    <a href="{{ route('login') }}" class="hidden sm:inline-flex btn btn-ghost btn-sm rounded-xl">دخول</a>
                    <a href="{{ route('register') }}" class="btn btn-primary btn-sm rounded-xl shadow-sm">حساب جديد</a>
                @endguest

                {{-- قائمة الموبايل --}}
                <button @click="mobileOpen = true" class="md:hidden flex h-9 w-9 items-center justify-center rounded-xl border border-border bg-card cursor-pointer" aria-label="القائمة">
                    {!! icon('menu', 'h-5 w-5') !!}
                </button>
            </div>
        </div>
    </div>

    {{-- قائمة الموبايل الجانبية --}}
    <div x-show="mobileOpen" x-transition.opacity.duration.200ms class="fixed inset-0 z-[60] md:hidden" style="display:none">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="mobileOpen = false"></div>
        <div dir="rtl" class="absolute top-0 right-0 bottom-0 w-72 glass border-l border-border/40 p-4 overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <img src="{{ asset('logo.jpg') }}" alt="مركز نور" class="h-9 w-9 rounded-lg object-cover">
                    <span class="font-extrabold gradient-text">مركز نور</span>
                </div>
                <button @click="mobileOpen = false" class="flex h-8 w-8 items-center justify-center rounded-lg hover:bg-muted/60 cursor-pointer">{!! icon('x', 'h-4 w-4') !!}</button>
            </div>
            <div class="flex flex-col gap-1">
                @foreach ($navItems as $item)
                    <a href="{{ $item['url'] }}" @click="mobileOpen = false"
                       class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-right transition-all {{ ($item['active'] ?? false) ? 'bg-primary/10 text-primary shadow-sm' : 'text-foreground hover:bg-muted/60' }}">
                        {!! icon($item['icon'], 'h-5 w-5') !!}
                        {{ $item['label'] }}
                        @if (($item['badge'] ?? 0) > 0)
                            <span class="mr-auto flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white pulse-soft">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
                @guest
                    <div class="h-px bg-border/60 my-2"></div>
                    <a href="{{ route('login') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium hover:bg-muted/60">{!! icon('log-in', 'h-5 w-5') !!} تسجيل الدخول</a>
                    <a href="{{ route('register') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium bg-primary text-primary-foreground shadow-sm">{!! icon('user-plus', 'h-5 w-5') !!} إنشاء حساب جديد</a>
                @endguest
            </div>
        </div>
    </div>
</header>

{{-- ===== المحتوى ===== --}}
<main class="flex-1">
    @include('partials.flash')
    @yield('content')
</main>

{{-- ===== الفوتر ===== --}}
<footer class="mt-auto border-t border-border/40 bg-gradient-to-b from-card/40 to-card/80 backdrop-blur-sm">
    <div class="container mx-auto max-w-7xl px-4 sm:px-6 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="md:col-span-2">
                <div class="flex items-center gap-2.5 mb-4">
                    <img src="{{ asset('logo.jpg') }}" alt="مركز نور" class="h-12 w-12 rounded-xl object-cover shadow-md ring-1 ring-foreground/5">
                    <div class="flex flex-col leading-tight">
                        <span class="text-lg font-extrabold gradient-text">مركز نور</span>
                        <span class="text-xs text-muted-foreground">صيانة الأجهزة المنزلية</span>
                    </div>
                </div>
                <p class="text-sm text-muted-foreground leading-relaxed max-w-md">
                    مركز نور هي خدمتك الموثوقة لصيانة جميع الأجهزة المنزلية في منطقة 6 أكتوبر وحدائق الأهرام.
                    فنيون محترفون، أسعار مناسبة، وخدمة سريعة في نفس اليوم.
                </p>
                <div class="flex items-center gap-2 mt-4">
                    <a href="{{ $siteSettings->whatsappLink() }}" target="_blank" rel="noopener noreferrer"
                       class="flex h-9 w-9 items-center justify-center rounded-lg bg-green-50 text-green-600 hover:bg-green-100 dark:bg-green-950/40 dark:text-green-400 transition-colors"
                       aria-label="واتساب">{!! icon('whatsapp', 'h-4 w-4') !!}</a>
                    @if ($siteSettings->facebook_url)
                        <a href="{{ $siteSettings->facebook_url }}" target="_blank" rel="noopener noreferrer"
                           class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-950/40 dark:text-blue-400 transition-colors"
                           aria-label="فيسبوك">{!! icon('facebook', 'h-4 w-4') !!}</a>
                    @endif
                    @if ($siteSettings->instagram_url)
                        <a href="{{ $siteSettings->instagram_url }}" target="_blank" rel="noopener noreferrer"
                           class="flex h-9 w-9 items-center justify-center rounded-lg bg-pink-50 text-pink-600 hover:bg-pink-100 dark:bg-pink-950/40 dark:text-pink-400 transition-colors"
                           aria-label="إنستجرام">{!! icon('instagram', 'h-4 w-4') !!}</a>
                    @endif
                </div>
            </div>

            <div>
                <h4 class="font-bold mb-3 text-sm">روابط سريعة</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ auth()->check() ? route('requests.create') : route('register') }}" class="text-muted-foreground hover:text-primary transition-colors">اطلب صيانة</a></li>
                    <li><a href="{{ route('track') }}" class="text-muted-foreground hover:text-primary transition-colors">تتبع طلبك</a></li>
                    @guest
                        <li><a href="{{ route('register') }}" class="text-muted-foreground hover:text-primary transition-colors">إنشاء حساب</a></li>
                        <li><a href="{{ route('login') }}" class="text-muted-foreground hover:text-primary transition-colors">تسجيل الدخول</a></li>
                    @endguest
                </ul>
            </div>

            <div>
                <h4 class="font-bold mb-3 text-sm">تواصل معنا</h4>
                <ul class="space-y-3 text-sm">
                    <li>
                        <a href="tel:{{ $siteSettings->phone }}" class="flex items-center gap-2 text-muted-foreground hover:text-primary transition-colors">
                            {!! icon('phone', 'h-4 w-4 text-primary') !!}
                            <span dir="ltr">{{ $siteSettings->phone }}</span>
                        </a>
                    </li>
                    <li class="flex items-center gap-2 text-muted-foreground">
                        {!! icon('map-pin', 'h-4 w-4 text-primary') !!}
                        {{ $siteSettings->address }}
                    </li>
                    <li class="flex items-center gap-2 text-muted-foreground">
                        {!! icon('clock', 'h-4 w-4 text-primary') !!}
                        {{ $siteSettings->working_hours }}
                    </li>
                    <li>
                        <a href="mailto:{{ $siteSettings->email }}" class="flex items-center gap-2 text-muted-foreground hover:text-primary transition-colors">
                            {!! icon('mail', 'h-4 w-4 text-primary') !!}
                            <span dir="ltr">{{ $siteSettings->email }}</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="mt-10 pt-6 border-t border-border/40 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-muted-foreground">
            <p>© {{ now()->year }} مركز نور. جميع الحقوق محفوظة.</p>
            <p class="flex items-center gap-1.5">
                <span class="inline-block h-1.5 w-1.5 rounded-full bg-primary/60"></span>
                منطقة الخدمة: 6 أكتوبر وحدائق الأهرام - الجيزة
            </p>
        </div>
    </div>
</footer>

<script>
    function toggleTheme() {
        const isDark = document.documentElement.classList.toggle('dark');
        try { localStorage.setItem('noor-theme', isDark ? 'dark' : 'light'); } catch (e) {}
    }

    /* ===== عدّاد متحرك للأرقام (KPI cards) ===== */
    function animateCountUps(root) {
        (root || document).querySelectorAll('[data-count]:not([data-counted])').forEach(function (el) {
            el.setAttribute('data-counted', '1');
            var target = parseFloat(el.getAttribute('data-count')) || 0;
            var decimals = parseInt(el.getAttribute('data-decimals') || '0', 10);
            var prefix = el.getAttribute('data-prefix') || '';
            var suffix = el.getAttribute('data-suffix') || '';
            var dur = 900;
            var start = null;
            function step(ts) {
                if (!start) start = ts;
                var p = Math.min((ts - start) / dur, 1);
                var eased = 1 - Math.pow(1 - p, 3);
                var val = target * eased;
                el.textContent = prefix + (decimals > 0
                    ? val.toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
                    : Math.round(val).toLocaleString('en-US')) + suffix;
                if (p < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        });
    }
    document.addEventListener('DOMContentLoaded', function () { animateCountUps(); });

    /* ===== ساعة حية لغرفة العمليات ===== */
    function initOpsClock() {
        var clockEl = document.getElementById('ops-clock');
        if (!clockEl) return;
        function tick() {
            var d = new Date();
            clockEl.textContent = String(d.getHours()).padStart(2, '0') + ':' +
                String(d.getMinutes()).padStart(2, '0') + ':' + String(d.getSeconds()).padStart(2, '0');
        }
        tick();
        setInterval(tick, 1000);
    }
    document.addEventListener('DOMContentLoaded', initOpsClock);

    /* ===== حارس أخطاء JS: توست ودّي بدل ما تقف الصفحة ===== */
    (function () {
        var shownAt = 0;
        function ncJSToast(msg) {
            var now = Date.now();
            if (now - shownAt < 4000) return; // مش نكرر الرسالة بسرعة
            shownAt = now;
            var t = document.createElement('div');
            t.textContent = msg;
            t.setAttribute('role', 'alert');
            t.style.cssText = 'position:fixed;bottom:1rem;inset-inline-start:1rem;z-index:9998;' +
                'background:#b91c1c;color:#fff;padding:0.7rem 1.1rem;border-radius:0.8rem;' +
                'font:700 0.8rem/1.4 system-ui,sans-serif;box-shadow:0 10px 24px -8px rgba(0,0,0,.5);' +
                'max-width:22rem;opacity:0;transform:translateY(8px);transition:all .3s ease;';
            document.body.appendChild(t);
            requestAnimationFrame(function () { t.style.opacity = '1'; t.style.transform = 'translateY(0)'; });
            setTimeout(function () {
                t.style.opacity = '0';
                setTimeout(function () { t.remove(); }, 350);
            }, 4200);
        }
        window.addEventListener('error', function (e) {
            ncJSToast('حصل خطأ بسيط في الصفحة — النظام شغال، حدّث الصفحة لو ظهرت مشكلة');
            if (window.console && console.error) console.error(e.error || e.message);
        });
        window.addEventListener('unhandledrejection', function () {
            ncJSToast('فيه طلب بيانات ما اكملش — النظام شغال وهيتجاول تلقائياً');
        });
    })();
</script>
@stack('scripts')
</body>
</html>
