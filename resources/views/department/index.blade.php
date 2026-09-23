@extends('layouts.app')
@section('title', $dept ? 'قسم '.$dept->name : 'لوحة القسم')

@section('content')
<div class="container mx-auto max-w-6xl px-4 sm:px-6 py-8" x-data="{ newOpen: false }">

    @if (! $dept)
        <div class="card p-10 text-center max-w-md mx-auto">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-950/40 text-amber-600">
                {!! icon('alert-circle', 'h-7 w-7') !!}
            </div>
            <h3 class="font-bold mb-1">لا يوجد قسم معيّن لك</h3>
            <p class="text-muted-foreground text-sm">تواصل مع الإدارة لتعيينك مديراً لقسم أولاً</p>
        </div>
    @else

    {{-- الهيدر الرئيسي (زي الأصل) --}}
    <div class="mb-6">
        <div class="card overflow-hidden border-0 bg-gradient-to-l from-primary/10 via-primary/5 to-transparent p-6">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-primary to-primary/70 shadow-lg shadow-primary/20 shrink-0">
                        {!! icon('layers', 'h-7 w-7 text-primary-foreground') !!}
                    </div>
                    <div>
                        <h1 class="text-2xl md:text-3xl font-extrabold mb-1 tracking-tight">{{ $dept->name }}</h1>
                        <p class="text-muted-foreground text-sm flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-primary/10 text-primary text-xs font-medium">
                                <span class="h-1.5 w-1.5 rounded-full bg-primary"></span>
                                {{ auth()->user()->name }}
                            </span>
                            <span>•</span>
                            <span>مدير قسم</span>
                        </p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('department.index') }}" class="btn btn-outline btn-icon h-10 w-10 rounded-xl border-2 hover:border-primary/40" title="تحديث">
                        {!! icon('refresh', 'h-4 w-4') !!}
                    </a>
                    <button @click="newOpen = true" class="btn btn-primary">
                        {!! icon('plus', 'h-4 w-4') !!}
                        طلب جديد
                    </button>
                </div>
            </div>
        </div>
    </div>

    @include('department.partials.tabs')

    {{-- نموذج تسجيل طلب جديد --}}
    <div x-show="newOpen" x-transition class="card p-5 md:p-6 mb-6 border-primary/30" style="display: none">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold flex items-center gap-2">{!! icon('plus', 'h-4 w-4 text-primary') !!} تسجيل طلب صيانة جديد — قسم {{ $dept->name }}</h2>
            <button @click="newOpen = false" class="flex h-8 w-8 items-center justify-center rounded-lg hover:bg-muted/60 cursor-pointer">{!! icon('x', 'h-4 w-4') !!}</button>
        </div>
        <form method="POST" action="{{ route('department.store') }}" class="space-y-4">
            @csrf
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">موبايل العميل <span class="text-destructive">*</span></label>
                    <input name="customer_phone" type="tel" value="{{ old('customer_phone') }}" placeholder="01xxxxxxxxx" dir="ltr" style="text-align: right" class="input {{ $errors->has('customer_phone') ? 'input-error' : '' }}" required>
                    <p class="text-[10px] text-muted-foreground mt-1">لو الرقم جديد هيتعمل حساب للعميل تلقائياً</p>
                    @error('customer_phone')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">اسم العميل <span class="text-destructive">*</span></label>
                    <input name="customer_name" value="{{ old('customer_name') }}" placeholder="الاسم كما يقوله" class="input {{ $errors->has('customer_name') ? 'input-error' : '' }}" required>
                    @error('customer_name')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid sm:grid-cols-3 gap-4">
                <div>
                    <label class="label">نوع الجهاز <span class="text-destructive">*</span></label>
                    <input name="device_type" value="{{ old('device_type') }}" list="dept-devices" placeholder="نوع الجهاز" class="input {{ $errors->has('device_type') ? 'input-error' : '' }}" required>
                    <datalist id="dept-devices">
                        @foreach ((array) $dept->device_types as $dtName)
                            <option value="{{ $dtName }}"></option>
                        @endforeach
                        @foreach (\App\Models\DeviceType::where('is_active', true)->orderBy('sort_order')->get() as $dt)
                            <option value="{{ $dt->name }}"></option>
                        @endforeach
                    </datalist>
                    @error('device_type')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">الماركة</label>
                    <input name="brand" value="{{ old('brand') }}" placeholder="اختياري" class="input">
                </div>
                <div>
                    <label class="label">رقم التواصل <span class="text-destructive">*</span></label>
                    <input name="phone" type="tel" value="{{ old('phone') }}" placeholder="01xxxxxxxxx" dir="ltr" style="text-align: right" class="input {{ $errors->has('phone') ? 'input-error' : '' }}" required>
                    @error('phone')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label class="label">وصف العطل <span class="text-destructive">*</span></label>
                <textarea name="issue_description" rows="2" placeholder="اللي العميل قاله عن العطل..." class="input {{ $errors->has('issue_description') ? 'input-error' : '' }}" required>{{ old('issue_description') }}</textarea>
                @error('issue_description')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">المنطقة <span class="text-destructive">*</span></label>
                    <select name="area" class="input" required>
                        @foreach (\App\Models\ServiceRequest::AREAS as $key => $label)
                            <option value="{{ $key }}" {{ old('area') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">العنوان <span class="text-destructive">*</span></label>
                    <input name="address" value="{{ old('address') }}" placeholder="تفاصيل العنوان" class="input {{ $errors->has('address') ? 'input-error' : '' }}" required>
                    @error('address')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">التاريخ <span class="text-destructive">*</span></label>
                    <input name="preferred_date" type="date" value="{{ old('preferred_date', now()->toDateString()) }}" class="input" required>
                </div>
                <div>
                    <label class="label">الفترة <span class="text-destructive">*</span></label>
                    <select name="preferred_time" class="input" required>
                        @foreach (\App\Models\ServiceRequest::TIME_SLOTS as $key => $label)
                            <option value="{{ $key }}" {{ old('preferred_time') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-full">{!! icon('save', 'h-4 w-4') !!} إنشاء الطلب</button>
        </form>
    </div>

    {{-- بطاقات الإحصائيات — KPI حديث --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5 mb-4 stagger">
        <div class="kpi !p-4 {{ $stats['overdue'] > 0 ? 'selected' : '' }}" style="--kpi-accent: var(--color-red-500)">
            <div class="kpi-value">{{ $stats['overdue'] }}</div>
            <div class="kpi-label">طلبات متأخرة</div>
        </div>
        <div class="kpi !p-4" style="--kpi-accent: var(--color-amber-500)">
            <div class="kpi-value">{{ $stats['pending'] }}</div>
            <div class="kpi-label">كشف / تواصل</div>
        </div>
        <div class="kpi !p-4" style="--kpi-accent: var(--color-blue-500)">
            <div class="kpi-value">{{ $stats['inProgress'] }}</div>
            <div class="kpi-label">تنفيذ / جاهز</div>
        </div>
        <div class="kpi !p-4" style="--kpi-accent: var(--color-green-500)">
            <div class="kpi-value">{{ $stats['completedToday'] }}</div>
            <div class="kpi-label">اكتملت اليوم</div>
        </div>
        <div class="kpi !p-4" style="--kpi-accent: var(--color-primary)">
            <div class="kpi-value">{{ money($stats['totalRevenue']) }}</div>
            <div class="kpi-label">إيراد الصيانات الكلي</div>
        </div>
        <div class="kpi !p-4" style="--kpi-accent: var(--color-green-500)">
            <div class="kpi-value">{{ money($stats['salesToday']) }}</div>
            <div class="kpi-label">مبيعات اليوم</div>
        </div>
        <div class="kpi !p-4" style="--kpi-accent: var(--color-purple-500)">
            <div class="kpi-value">{{ $stats['techniciansCount'] }}</div>
            <div class="kpi-label">فنيو القسم</div>
        </div>
        <div class="kpi !p-4 {{ $stats['lowStock'] > 0 ? 'selected' : '' }}" style="--kpi-accent: var(--color-amber-500)">
            <div class="kpi-value">{{ $stats['lowStock'] }}/{{ $stats['itemsCount'] }}</div>
            <div class="kpi-label">أصناف تحت الحد</div>
        </div>
    </div>

    {{-- آخر 7 أيام --}}
    <div class="card p-5 mb-4">
        <h2 class="font-bold mb-4 flex items-center gap-2">{!! icon('trending-up', 'h-4 w-4 text-primary') !!} إيرادات آخر 7 أيام (صيانات + مبيعات)</h2>
        <div class="flex items-end justify-between gap-2 h-40 px-2">
            @php $maxRev = max($last7->max('total'), 1); @endphp
            @foreach ($last7 as $day)
                <div class="flex-1 flex flex-col items-center gap-2">
                    <div class="text-[10px] font-bold text-muted-foreground">{{ $day['total'] > 0 ? number_format($day['total'], 0) : '' }}</div>
                    <div class="w-full flex-1 flex items-end">
                        <div class="w-full rounded-t-md {{ $day['date'] === today()->toDateString() ? 'bg-primary' : 'bg-primary/40' }}"
                             style="height: {{ ($day['total'] / $maxRev) * 100 }}%; min-height: {{ $day['total'] > 0 ? '8px' : '0' }};"></div>
                    </div>
                    <div class="text-xs text-muted-foreground">{{ $day['day'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- أحدث طلبات القسم --}}
    <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold flex items-center gap-2">{!! icon('clipboard-list', 'h-4 w-4 text-primary') !!} أحدث طلبات القسم</h2>
            <a href="{{ route('department.requests') }}" class="btn btn-outline btn-sm">كل الطلبات {!! icon('chevron-left', 'h-3.5 w-3.5') !!}</a>
        </div>
        <div class="space-y-2">
            @forelse ($recentRequests as $r)
                <a href="{{ route('department.show', $r) }}" class="block p-3 rounded-xl bg-muted/40 hover:bg-muted/70 transition-colors">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <div class="text-sm">
                            <b>{{ $r->customer->name }}</b> — {{ $r->device_type }}
                            <span class="text-xs text-muted-foreground block mt-0.5">
                                <span dir="ltr">{{ $r->order_number }}</span> • {{ dt($r->created_at) }}
                                @if ($r->assignedTechnician) • فني: {{ $r->assignedTechnician->name }} @endif
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($r->price)<span class="text-sm font-bold text-primary">{{ money($r->price) }}</span>@endif
                            <span class="badge {{ $r->statusColor() }}">{{ $r->statusLabel() }}</span>
                        </div>
                    </div>
                </a>
            @empty
                <p class="text-sm text-muted-foreground text-center py-4">مفيش طلبات لقسمك لسه</p>
            @endforelse
        </div>
    </div>
    @endif
</div>
@endsection
