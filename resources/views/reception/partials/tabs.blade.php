{{-- شريط تبويبات لوحة الاستقبال — 7 تبويبات (تصميم حديث pill) --}}
{{-- للأدمن: زر رجوع واضح للوحة تحكم الأدمن (السلوك المثبّت المفيد) --}}
@php
    $receptionTabs = [
        ['route' => 'reception.index', 'label' => 'الطلبات', 'icon' => 'clipboard-list', 'active' => request()->routeIs('reception.index') || request()->routeIs('reception.show')],
        ['route' => 'reception.inventory', 'label' => 'المخزن', 'icon' => 'package', 'active' => request()->routeIs('reception.inventory')],
        ['route' => 'reception.customers', 'label' => 'العملاء', 'icon' => 'users', 'active' => request()->routeIs('reception.customers') || request()->routeIs('reception.customers.show')],
        ['route' => 'reception.stats', 'label' => 'الإحصائيات', 'icon' => 'trending-up', 'active' => request()->routeIs('reception.stats')],
        ['route' => 'reception.daily', 'label' => 'التقرير اليومي', 'icon' => 'calendar-check', 'active' => request()->routeIs('reception.daily')],
        ['route' => 'reception.expenses', 'label' => 'المصروفات', 'icon' => 'wallet', 'active' => request()->routeIs('reception.expenses')],
        ['route' => 'reception.partners', 'label' => 'شركاء الصيانة', 'icon' => 'handshake', 'active' => request()->routeIs('reception.partners')],
    ];
    $isAdmin = auth()->check() && auth()->user()->role === 'ADMIN';
@endphp
<div class="mb-5 flex items-center gap-3 flex-wrap">
    <div class="overflow-x-auto pb-1 -mx-1 px-1 grow">
        <div class="pill-tabs">
            @foreach ($receptionTabs as $tab)
                <a href="{{ route($tab['route']) }}" class="{{ $tab['active'] ? 'active' : '' }}">
                    {!! icon($tab['icon'], 'h-4 w-4') !!}
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </div>
    </div>

    @if ($isAdmin)
        {{-- الرجوع للوحة تحكم الأدمن — السلوك المثبّت --}}
        <a href="{{ route('admin.dashboard') }}" class="btn-back-admin shrink-0">
            {!! icon('dashboard', 'h-3.5 w-3.5') !!}
            لوحة تحكم الأدمن
        </a>
    @endif
</div>
