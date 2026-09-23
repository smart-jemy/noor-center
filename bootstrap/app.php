<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('home'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // منع سقوط النظام بالكامل: أي خطأ غير متوقع يظهر كخطأ ودّي — والنظام يفضل شغال
        // نقاط JSON الحية ترجع {ok:false} بدل ما يتوقف الشريط الحي
        $exceptions->render(function (\Throwable $e, Request $request) {
            // استثناءات ليها معالجة أصلية (دخول/تحقق/HTTP) بتكمل مسارها الطبيعي:
            // المصادقة → تحويل لصفحة الدخول / التحقق → رجوع بالأخطاء / HTTP → صفحة الكود
            if ($e instanceof \Illuminate\Auth\AuthenticationException
                || $e instanceof \Illuminate\Validation\ValidationException
                || $e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                return null;
            }

            if ($request->is('reception/live-stats') || $request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'error' => 'حدث خطأ مؤقت في جلب البيانات — جرّب تحديث الصفحة',
                ], 500);
            }

            // null = كمّل المعالجة الافتراضية (صفحات الأخطاء المخصصة 404/403/419/500/503)
            return null;
        });
    })->create();
