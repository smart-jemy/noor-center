<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'طباعة') — مركز نور</title>
    <style>
        @font-face { font-family: 'Cairo'; font-style: normal; font-weight: 400 900; font-display: swap;
            src: url('{{ asset('fonts/cairo-arabic.woff2') }}') format('woff2'); }
        body { font-family: 'Cairo', Tahoma, sans-serif; margin: 0; padding: 1rem; background: #f5f5f4; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        @media print { body { background: white; padding: 0; } }
    </style>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body class="bg-stone-100">
    <div class="no-print max-w-2xl mx-auto mb-4 flex gap-2 justify-center">
        <button onclick="window.print()" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>
            طباعة الإيصال
        </button>
        <button onclick="window.close()" class="btn btn-outline">إغلاق</button>
    </div>
    @yield('content')
</body>
</html>
