{{-- شريط تبويبات لوحة مدير القسم — 7 تبويبات مثل الأصل --}}
@php
    $deptTabs = [
        ['route' => 'department.index', 'label' => 'نظرة عامة', 'icon' => 'trending-up', 'active' => request()->routeIs('department.index')],
        ['route' => 'department.requests', 'label' => 'الطلبات', 'icon' => 'clipboard-list', 'active' => request()->routeIs('department.requests') || request()->routeIs('department.show')],
        ['route' => 'department.sales', 'label' => 'المبيعات', 'icon' => 'shopping-cart', 'active' => request()->routeIs('department.sales')],
        ['route' => 'department.technicians', 'label' => 'الفنيين', 'icon' => 'hard-hat', 'active' => request()->routeIs('department.technicians')],
        ['route' => 'department.customers', 'label' => 'العملاء', 'icon' => 'users', 'active' => request()->routeIs('department.customers')],
        ['route' => 'department.inventory', 'label' => 'المخزن', 'icon' => 'package', 'active' => request()->routeIs('department.inventory')],
        ['route' => 'department.financial', 'label' => 'التقرير المالي', 'icon' => 'bar-chart', 'active' => request()->routeIs('department.financial')],
    ];
@endphp
<div class="mb-5 overflow-x-auto pb-1 -mx-1 px-1">
    <div class="pill-tabs">
        @foreach ($deptTabs as $tab)
            <a href="{{ route($tab['route']) }}" class="{{ $tab['active'] ? 'active' : '' }}">
                {!! icon($tab['icon'], 'h-4 w-4') !!}
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>
</div>
