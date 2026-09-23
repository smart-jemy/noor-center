<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'حدث خطأ') — مركز نور</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, 'Noto Sans Arabic', system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            color: #1e293b;
            background:
                radial-gradient(60% 50% at 50% 25%, rgba(59, 130, 246, 0.08), transparent 70%),
                linear-gradient(180deg, #f8fafc, #eef2f8);
        }
        @media (prefers-color-scheme: dark) {
            body { color: #e2e8f0; background:
                radial-gradient(60% 50% at 50% 25%, rgba(59, 130, 246, 0.12), transparent 70%),
                linear-gradient(180deg, #0f172a, #111827); }
            .card { background: #1e293b !important; border-color: #33415540 !important; }
            .muted { color: #94a3b8 !important; }
        }
        .card {
            max-width: 460px;
            width: 100%;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1.25rem;
            padding: 2.5rem 2rem;
            text-align: center;
            box-shadow: 0 20px 45px -20px rgba(15, 23, 42, 0.25);
        }
        .logo {
            width: 64px;
            height: 64px;
            margin: 0 auto 1.1rem;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            font-weight: 900;
            color: #fff;
            background: linear-gradient(150deg, #60a5fa, #3b82f6 55%, #2563eb);
            box-shadow: 0 10px 22px -8px rgba(37, 99, 235, 0.55), inset 0 1px 0 rgba(255, 255, 255, 0.4);
        }
        .code {
            font-size: 2.6rem;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 0.5rem;
            background: linear-gradient(180deg, #3b82f6, #1d4ed8);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        h1 { font-size: 1.15rem; font-weight: 800; margin-bottom: 0.5rem; }
        p { font-size: 0.85rem; line-height: 1.7; margin-bottom: 1.4rem; }
        .muted { color: #64748b; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.62rem 1.35rem;
            border-radius: 0.8rem;
            font-size: 0.85rem;
            font-weight: 700;
            text-decoration: none;
            color: #fff;
            background: linear-gradient(180deg, #3b82f6, #2563eb);
            box-shadow: 0 6px 16px -6px rgba(37, 99, 235, 0.6);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 10px 20px -8px rgba(37, 99, 235, 0.7); }
        .btn.ghost { color: #475569; background: #f1f5f9; box-shadow: none; border: 1px solid #e2e8f0; }
        .btn.ghost:hover { background: #e2e8f0; }
        .row { display: flex; gap: 0.6rem; justify-content: center; flex-wrap: wrap; }
        .foot { margin-top: 1.5rem; font-size: 0.68rem; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">NC</div>
        <div class="code">@yield('code', '500')</div>
        <h1>@yield('title', 'حدث خطأ غير متوقع')</h1>
        <p class="muted">@yield('message', 'حصلت مشكلة مؤقتة في النظام — الفريق التقني بيتابعها. جرّب تاني من فضلك.')</p>
        <div class="row">
            <a href="{{ url('/') }}" class="btn">الرجوع للرئيسية</a>
            @yield('actions')
        </div>
        <p class="foot">مركز نور لخدمات الصيانة — نظام إدارة الصيانة</p>
    </div>
</body>
</html>
