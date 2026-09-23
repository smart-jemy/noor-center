@extends('admin.layout')
@section('title', 'الطلبات — لوحة التحكم')
@section('admin_title', 'إدارة الطلبات')
@section('admin_subtitle', 'كل طلبات الصيانة — بحث وفلترة كاملة')

@section('admin_actions')
    <a href="{{ route('reception.index') }}" class="btn btn-outline btn-sm">{!! icon('plus', 'h-3.5 w-3.5') !!} تسجيل جهاز (استقبال)</a>
@endsection

@section('admin_content')
{{-- شريط المتأخرات — زي الأصل --}}
@if ($overdueCount > 0)
    <div class="card p-4 mb-4 border-red-300 dark:border-red-900/60 bg-red-50/70 dark:bg-red-950/30 flex items-center gap-3">
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-100 dark:bg-red-950/60 shrink-0">
            {!! icon('alert-circle', 'h-5 w-5 text-red-600 dark:text-red-400') !!}
        </div>
        <div class="flex-1">
            <p class="font-bold text-red-900 dark:text-red-300">{{ $overdueCount }} طلب متأخر — يرجى المتابعة</p>
            <p class="text-xs text-red-700 dark:text-red-400 mt-0.5">طلبات تجاوزت مهلة التسليم ولسه مش مسلَّمة</p>
        </div>
        <a href="{{ route('admin.requests.index', ['hide_completed' => 1]) }}" class="btn btn-outline btn-sm shrink-0">عرض النشط</a>
    </div>
@endif

{{-- البحث والفلاتر + إخفاء المكتمل — شريط أدوات حديث --}}
<form method="GET" class="toolbar p-3 mb-4">
    <div class="flex flex-wrap gap-2.5 items-center">
        <div class="relative flex-1 min-w-[200px] max-w-md">
            {!! icon('search', 'h-4 w-4 absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none') !!}
            <input name="q" value="{{ request('q') }}" placeholder="بحث: رقم الطلب / الاسم / الهاتف / الجهاز / العنوان..." class="input pr-10 h-10" autocomplete="off">
        </div>
        <select name="status" class="input w-auto h-10">
            <option value="">كل الحالات</option>
            @foreach (\App\Models\ServiceRequest::STATUSES as $status)
                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ \App\Models\ServiceRequest::STATUS_LABELS[$status] }}</option>
            @endforeach
        </select>
        <select name="area" class="input w-auto h-10">
            <option value="">كل المناطق</option>
            @foreach (\App\Models\ServiceRequest::AREAS as $key => $label)
                <option value="{{ $key }}" {{ request('area') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" name="hide_completed" value="{{ request()->boolean('hide_completed') ? 0 : 1 }}"
                class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer {{ request()->boolean('hide_completed') ? 'bg-primary/10 text-primary border border-primary/30' : 'bg-muted/70 text-muted-foreground border border-transparent hover:bg-muted' }}">
            <span class="switch {{ request()->boolean('hide_completed') ? 'on' : '' }}"><span class="knob"></span></span>
            <span>إخفاء المسلَّم/الملغى</span>
        </button>
        <button type="submit" class="btn btn-primary btn-sm h-10">{!! icon('filter', 'h-3.5 w-3.5') !!} فلترة</button>
        @if (request()->hasAny(['q', 'status', 'area', 'hide_completed']))
            <a href="{{ route('admin.requests.index') }}" class="btn btn-ghost btn-sm h-10">مسح</a>
        @endif
    </div>
</form>

{{-- الجدول الموحد — نفس شكل الاستقبال بالظبط (مع صلاحية الحذف للأدمن) --}}
@include('partials.requests-table', ['requests' => $requests, 'showRoute' => 'admin.requests.show', 'adminView' => true])

{{ $requests->links() }}
@endsection
