@extends('layouts.app')

@section('content')
<div class="container mx-auto max-w-7xl px-4 sm:px-6 py-6" x-data="{ sidebarOpen: false }">

    {{-- رأس اللوحة --}}
    <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
        <div class="flex items-center gap-3">
            <button @click="sidebarOpen = true" class="lg:hidden flex h-10 w-10 items-center justify-center rounded-xl border border-border bg-card cursor-pointer">
                {!! icon('menu', 'h-5 w-5') !!}
            </button>
            <div>
                <h1 class="text-xl md:text-2xl font-extrabold">@yield('admin_title', 'لوحة التحكم')</h1>
                <p class="text-muted-foreground text-xs">@yield('admin_subtitle')</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @yield('admin_actions')
        </div>
    </div>

    <div class="grid lg:grid-cols-[240px_1fr] gap-6">

        {{-- القائمة الجانبية — تابات 3D --}}
        <aside class="hidden lg:block">
            <div class="sticky top-20 card p-2.5 max-h-[calc(100vh-6rem)] overflow-y-auto nav3d-scroll">
                @include('admin.partials.nav')
            </div>
        </aside>

        {{-- قائمة الموبايل --}}
        <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-[60] lg:hidden" style="display:none">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="sidebarOpen = false"></div>
            <div class="absolute top-0 right-0 bottom-0 w-72 glass border-l border-border/40 p-2 overflow-y-auto">
                <div class="flex items-center justify-between p-2 mb-1">
                    <span class="font-extrabold gradient-text">أقسام اللوحة</span>
                    <button @click="sidebarOpen = false" class="flex h-8 w-8 items-center justify-center rounded-lg hover:bg-muted/60 cursor-pointer">{!! icon('x', 'h-4 w-4') !!}</button>
                </div>
                @include('admin.partials.nav', ['mobile' => true])
            </div>
        </div>

        {{-- المحتوى --}}
        <main class="min-w-0">
            @yield('admin_content')
        </main>
    </div>
</div>
@endsection

@once
@push('scripts')
{{-- صوت الإشعارات لو مفعّل --}}
@if ($siteSettings->sound_notification_enabled && $siteSettings->sound_notification_url && $unreadBadge > 0)
<script>
    (function () {
        try {
            var audio = new Audio('{{ $siteSettings->sound_notification_url }}');
            audio.volume = 0.7;
            audio.play().catch(function () {});
        } catch (e) {}
    })();
</script>
@endif
@endpush
@endonce
