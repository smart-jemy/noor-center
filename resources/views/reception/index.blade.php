@extends('layouts.app')
@section('title', 'لوحة الاستقبال')

@section('content')
<div class="container mx-auto max-w-6xl px-4 sm:px-6 py-8"
     x-data="receptionBoard({
        status: @js(request('status', '')),
        hideCompleted: @js(request()->boolean('hide_completed')),
        total: {{ $requests->total() }}
     })">

    {{-- ===== الهيدر ===== --}}
    <div class="flex items-center justify-between flex-wrap gap-3 mb-5">
        <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-primary/15 to-primary/5 text-primary ring-1 ring-primary/20 shrink-0">
                {!! icon('phone-call', 'h-6 w-6') !!}
            </div>
            <div>
                <h1 class="text-2xl font-extrabold mb-0.5 flex items-center gap-2">
                    لوحة الاستقبال
                    <span class="live-dot" title="مباشر"></span>
                </h1>
                <p class="text-muted-foreground text-sm">تسجيل الأجهزة الواصلة وإدارة كل شغل المركز</p>
            </div>
        </div>
        <button @click="newOpen = !newOpen" :class="newOpen && 'rotate-45'"
                class="btn btn-primary transition-transform duration-300">
            {!! icon('plus', 'h-4 w-4') !!}
            <span x-text="newOpen ? 'إغلاق' : 'تسجيل جهاز واصل'"></span>
        </button>
    </div>

    @include('reception.partials.tabs')

    {{-- ===== شريط الحالة الحي (غرفة العمليات) ===== --}}
    @include('partials.ops-live-strip', ['todayEntered' => $stats['today'], 'todayCompleted' => $stats['completedToday']])

    {{-- ===== شريط المتابعة: متأخرات + طلبات اليوم ===== --}}
    @if ($overdue->isNotEmpty() || $todayRequests->isNotEmpty())
    <div class="grid md:grid-cols-2 gap-3 mb-4 stagger">
        @if ($overdue->isNotEmpty())
        <div class="req-card p-4 border-r-4 !border-r-red-500 !border-red-200 dark:!border-red-900/60 bg-red-50/50 dark:bg-red-950/20">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-100 dark:bg-red-950/60 shrink-0 pop-in">
                    {!! icon('alert-circle', 'h-5 w-5 text-red-600 dark:text-red-400') !!}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-red-900 dark:text-red-300 text-sm">
                        {{ $overdue->count() }} طلب متأخر عن مهلة التسليم!
                    </p>
                    <p class="text-[10px] text-red-700/80 dark:text-red-400/80 mt-0.5">المهلة حسب وضع الطلب: طوارئ 24 ساعة · مستعجل 48 · عادي 96</p>
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        @foreach ($overdue->take(3) as $r)
                            <a href="{{ route('reception.show', $r) }}" class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-card border border-red-200 dark:border-red-900 text-[11px] text-red-800 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-950/50 transition-colors">
                                {!! icon('wrench', 'h-3 w-3') !!}
                                {{ $r->device_type }} — {{ $r->customer->name }}
                            </a>
                        @endforeach
                        @if ($overdue->count() > 3)
                            <span class="inline-flex items-center px-2 py-1 text-[11px] text-red-700 dark:text-red-400 font-bold">+{{ $overdue->count() - 3 }} كمان</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if ($todayRequests->isNotEmpty())
        <div class="req-card p-4 border-r-4 !border-r-blue-500 !border-blue-200 dark:!border-blue-900/60 bg-blue-50/50 dark:bg-blue-950/20">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-950/60 shrink-0 pop-in">
                    {!! icon('calendar', 'h-5 w-5 text-blue-600 dark:text-blue-400') !!}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-blue-900 dark:text-blue-300 text-sm">
                        {{ $todayRequests->count() }} طلب واصل النهاردة لسه شغاله
                    </p>
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        @foreach ($todayRequests->take(4) as $r)
                            <a href="{{ route('reception.show', $r) }}" class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-card border border-blue-200 dark:border-blue-900 text-[11px] text-blue-800 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-950/50 transition-colors">
                                {!! icon('clock', 'h-3 w-3') !!}
                                {{ $r->timeSlotLabel() }} — {{ $r->device_type }}
                            </a>
                        @endforeach
                        @if ($todayRequests->count() > 4)
                            <span class="inline-flex items-center px-2 py-1 text-[11px] text-blue-700 dark:text-blue-400">+{{ $todayRequests->count() - 4 }} كمان</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- ===== KPI موحد مضغوط — دورة حياة الطلب كاملة، قابل للنقر للفلترة ===== --}}
    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-2.5 mb-4 stagger">
        <a href="{{ route('reception.index', array_filter(['status' => 'PENDING', 'hide_completed' => request()->boolean('hide_completed') ? 1 : null, 'q' => request('q')])) }}"
           class="kpi" style="--kpi-accent: var(--color-amber-500)">
            <div class="flex items-center justify-between">
                <div>
                    <div class="kpi-value" data-count="{{ $stats['pending'] }}">0</div>
                    <div class="kpi-label">كشف</div>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500 shrink-0">
                    {!! icon('clipboard-list', 'h-4.5 w-4.5') !!}
                </div>
            </div>
        </a>
        <a href="{{ route('reception.index', array_filter(['status' => 'CONTACTED', 'hide_completed' => request()->boolean('hide_completed') ? 1 : null, 'q' => request('q')])) }}"
           class="kpi" style="--kpi-accent: var(--color-cyan-500)">
            <div class="flex items-center justify-between">
                <div>
                    <div class="kpi-value" data-count="{{ $stats['contacted'] ?? 0 }}">0</div>
                    <div class="kpi-label">تواصل</div>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-500/10 text-cyan-500 shrink-0">
                    {!! icon('phone-call', 'h-4.5 w-4.5') !!}
                </div>
            </div>
        </a>
        <a href="{{ route('reception.index', array_filter(['status' => 'CONFIRMED', 'hide_completed' => request()->boolean('hide_completed') ? 1 : null, 'q' => request('q')])) }}"
           class="kpi" style="--kpi-accent: var(--color-blue-500)">
            <div class="flex items-center justify-between">
                <div>
                    <div class="kpi-value" data-count="{{ $stats['confirmed'] ?? 0 }}">0</div>
                    <div class="kpi-label">تأكيد</div>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-500/10 text-blue-500 shrink-0">
                    {!! icon('check-circle', 'h-4.5 w-4.5') !!}
                </div>
            </div>
        </a>
        <a href="{{ route('reception.index', array_filter(['status' => 'IN_PROGRESS', 'hide_completed' => request()->boolean('hide_completed') ? 1 : null, 'q' => request('q')])) }}"
           class="kpi" style="--kpi-accent: var(--color-purple-500)">
            <div class="flex items-center justify-between">
                <div>
                    <div class="kpi-value" data-count="{{ $stats['inProgress'] ?? 0 }}">0</div>
                    <div class="kpi-label">تنفيذ</div>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-500/10 text-purple-500 shrink-0">
                    {!! icon('loader', 'h-4.5 w-4.5') !!}
                </div>
            </div>
        </a>
        <a href="{{ route('reception.index', array_filter(['status' => 'READY', 'hide_completed' => request()->boolean('hide_completed') ? 1 : null, 'q' => request('q')])) }}"
           class="kpi" style="--kpi-accent: var(--color-teal-500)">
            <div class="flex items-center justify-between">
                <div>
                    <div class="kpi-value" data-count="{{ $stats['ready'] ?? 0 }}">0</div>
                    <div class="kpi-label">جاهز</div>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-500/10 text-teal-500 shrink-0">
                    {!! icon('package', 'h-4.5 w-4.5') !!}
                </div>
            </div>
        </a>
        <a href="{{ route('reception.index', array_filter(['status' => 'RETURNED', 'hide_completed' => request()->boolean('hide_completed') ? 1 : null, 'q' => request('q')])) }}"
           class="kpi" style="--kpi-accent: oklch(0.7 0.17 55)">
            <div class="flex items-center justify-between">
                <div>
                    <div class="kpi-value" data-count="{{ $stats['returned'] ?? 0 }}">0</div>
                    <div class="kpi-label">مرتجع</div>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl shrink-0" style="background: oklch(0.7 0.17 55 / 0.12); color: oklch(0.7 0.17 55)">
                    {!! icon('undo', 'h-4.5 w-4.5') !!}
                </div>
            </div>
        </a>
        <a href="{{ route('reception.index', array_filter(['hide_completed' => request()->boolean('hide_completed') ? 1 : null, 'q' => request('q')])) }}"
           class="kpi" style="--kpi-accent: var(--color-emerald-500)">
            <div class="flex items-center justify-between">
                <div>
                    <div class="kpi-value" data-count="{{ $stats['today'] }}">0</div>
                    <div class="kpi-label">وارد اليوم</div>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-500 shrink-0">
                    {!! icon('calendar', 'h-4.5 w-4.5') !!}
                </div>
            </div>
        </a>
        <a href="{{ route('reception.index', array_filter(['overdue' => 1, 'q' => request('q')])) }}"
           class="kpi {{ $stats['overdue'] > 0 ? 'selected' : '' }}" style="--kpi-accent: var(--color-red-500)">
            <div class="flex items-center justify-between">
                <div>
                    <div class="kpi-value" data-count="{{ $stats['overdue'] }}">0</div>
                    <div class="kpi-label">متأخر عن المهلة</div>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-500/10 text-red-500 shrink-0">
                    {!! icon('alert-triangle', 'h-4.5 w-4.5') !!}
                </div>
            </div>
        </a>
    </div>

    {{-- ===== نموذج تسجيل جهاز جديد ===== --}}
    <div x-show="newOpen" x-transition.opacity.duration.300ms x-cloak class="card p-5 md:p-6 mb-5 border-primary/30 shadow-lg shadow-primary/5"
         x-data="receptionForm({{ \Illuminate\Support\Js::from($deviceTypes->mapWithKeys(fn ($dt) => [$dt->name => $dt->accessories ?? []])) }})">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold flex items-center gap-2">{!! icon('plus', 'h-4 w-4 text-primary') !!} تسجيل جهاز واصل للمركز</h2>
            <button @click="newOpen = false" class="flex h-8 w-8 items-center justify-center rounded-lg hover:bg-muted/60 cursor-pointer">{!! icon('x', 'h-4 w-4') !!}</button>
        </div>
        <form method="POST" action="{{ route('reception.store') }}" enctype="multipart/form-data" class="space-y-4" @submit="submitting = true">
            @csrf
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <div class="flex items-center justify-between">
                        <label class="label">موبايل العميل <span class="text-destructive">*</span></label>
                        {{-- زر إضافة رقم إضافي اختياري جنب الرقم الأول --}}
                        <button type="button" @click="showAltPhone = !showAltPhone"
                                class="flex items-center gap-1 text-[11px] font-bold text-primary hover:underline cursor-pointer mb-1"
                                :class="showAltPhone && 'text-destructive hover:no-underline'">
                            {!! icon('plus', 'h-3 w-3') !!}
                            <span x-text="showAltPhone ? 'إلغاء الرقم الإضافي' : 'رقم إضافي'">رقم إضافي</span>
                        </button>
                    </div>
                    <input name="customer_phone" type="tel" x-model="phone" @input.debounce.400ms="lookupPhone()"
                           placeholder="01xxxxxxxxx" dir="ltr" style="text-align: right" class="input {{ $errors->has('customer_phone') ? 'input-error' : '' }}" required>
                    <div class="mt-1.5">
                        <template x-if="looking"><p class="text-xs text-muted-foreground">جاري البحث...</p></template>
                        <template x-if="checked && lookup && lookup.found">
                            <div class="flex items-center gap-1.5 text-xs text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-900 rounded-lg px-2 py-1.5 pop-in">
                                {!! icon('check-circle', 'h-3.5 w-3.5') !!}
                                <span>عميل قديم — <b x-text="lookup.customer.name"></b> — <span dir="ltr" x-text="lookup.customer.requestsCount + ' طلب سابق'"></span></span>
                            </div>
                        </template>
                        {{-- الرقم مسجل كطاقم — تحذير أحمر فوري (رقم واحد لكل حساب تحت أي بند) --}}
                        <template x-if="checked && lookup && lookup.isStaff">
                            <div class="flex items-start gap-1.5 text-xs text-red-700 dark:text-red-300 bg-red-50 dark:bg-red-950/40 border border-red-300 dark:border-red-900 rounded-lg px-2 py-1.5 pop-in">
                                {!! icon('alert-triangle', 'h-3.5 w-3.5 shrink-0 mt-0.5') !!}
                                <span>الرقم ده مسجل كحساب <b x-text="lookup.staffRole"></b> (<span x-text="lookup.staffName"></span>) — <b>مينفعش يتسجل كعميل!</b></span>
                            </div>
                        </template>
                        <template x-if="checked && lookup && !lookup.found && !lookup.isStaff">
                            <p class="text-[11px] text-muted-foreground">عميل جديد — هيتم إنشاء حساب تلقائياً (كلمة المرور: 123456)</p>
                        </template>
                    </div>
                    {{-- الرقم الإضافي — اختياري بالكامل، لو فاضي التواصل على رقم العميل نفسه --}}
                    <div x-show="showAltPhone" x-transition class="mt-2 pop-in" style="display:none">
                        <label class="label text-[11px]">رقم إضافي للتواصل (اختياري)</label>
                        <input name="phone" type="tel" x-model="altPhone" placeholder="01xxxxxxxxx — لو غير رقم العميل" dir="ltr" style="text-align: right" class="input">
                        <p class="text-[10px] text-muted-foreground mt-1">سيبه فاضي لو التواصل على رقم العميل نفسه — مش محتاج تكتب الرقم مرتين</p>
                    </div>
                    @error('customer_phone')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
                    @error('phone')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">اسم العميل <span x-show="!isExisting" class="text-destructive">*</span></label>
                    <input name="customer_name" id="cust-name-field" value="{{ old('customer_name') }}" placeholder="الاسم كما يقوله"
                           class="input {{ $errors->has('customer_name') ? 'input-error' : '' }}" :required="!isExisting">
                    <template x-if="isExisting"><p class="text-xs text-blue-600 dark:text-blue-400 mt-1">عميل قديم — الاسم اختياري</p></template>
                    @error('customer_name')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- الجهاز الأول + الملحقات --}}
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">نوع الجهاز <span class="text-destructive">*</span></label>
                    <div class="flex gap-2">
                        <input name="device_type" x-model="deviceType" list="reception-devices" placeholder="تكييف، غسالة..." class="input flex-1 {{ $errors->has('device_type') ? 'input-error' : '' }}" required @change="showAcc = false; selectedAcc = []">
                        <template x-if="deviceAccessories.length > 0">
                            <button type="button" @click="showAcc = !showAcc" title="إضافة ملحقات"
                                    class="btn btn-outline btn-sm shrink-0 h-[38px]">{!! icon('plus', 'h-4 w-4') !!}</button>
                        </template>
                    </div>
                    <datalist id="reception-devices">
                        @foreach ($deviceTypes as $dt)
                            <option value="{{ $dt->name }}"></option>
                        @endforeach
                    </datalist>
                    @error('device_type')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror

                    {{-- ملحقات الجهاز (اختياري) — زي الأصل --}}
                    <template x-if="showAcc && deviceAccessories.length > 0">
                        <div class="p-3 rounded-xl bg-muted/40 border border-border/60 space-y-2 mt-2 pop-in">
                            <p class="text-xs font-medium text-muted-foreground">ملحقات الجهاز (اختياري)</p>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="acc in deviceAccessories" :key="acc">
                                    <button type="button" @click="toggleAcc(acc)"
                                            :class="selectedAcc.includes(acc) ? 'bg-primary text-primary-foreground' : 'bg-card border hover:border-primary/40'"
                                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all">
                                        <span x-show="selectedAcc.includes(acc)">✓ </span><span x-text="acc"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
                <div>
                    <label class="label">الماركة</label>
                    <input name="brand" value="{{ old('brand') }}" placeholder="اختياري" class="input">
                </div>
            </div>

            <div>
                <label class="label">وصف العطل <span class="text-destructive">*</span></label>
                <textarea name="issue_description" rows="2" placeholder="اللي العميل قاله عن العطل..." class="input {{ $errors->has('issue_description') ? 'input-error' : '' }}" required>{{ old('issue_description') }}</textarea>
                @error('issue_description')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- أجهزة إضافية لنفس العميل — زي الأصل --}}
            <template x-for="(extra, i) in extras" :key="i">
                <div class="relative p-4 rounded-xl border-2 border-dashed border-primary/30 bg-primary/5 space-y-3 pop-in">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-bold text-primary">جهاز إضافي #<span x-text="i + 2"></span></p>
                        <button type="button" @click="extras.splice(i, 1)" class="flex h-6 w-6 items-center justify-center rounded-lg bg-red-100 dark:bg-red-950/60 text-red-600 dark:text-red-400 hover:bg-red-200 cursor-pointer">{!! icon('x', 'h-3.5 w-3.5') !!}</button>
                    </div>
                    <div class="grid sm:grid-cols-3 gap-3">
                        <div>
                            <label class="label">نوع الجهاز <span class="text-destructive">*</span></label>
                            <input :name="`extra_devices[${i}][device_type]`" x-model="extra.device_type" list="reception-devices" placeholder="نوع الجهاز" class="input" required>
                        </div>
                        <div>
                            <label class="label">الماركة</label>
                            <input :name="`extra_devices[${i}][brand]`" x-model="extra.brand" placeholder="اختياري" class="input">
                        </div>
                        <div class="sm:col-span-1">
                            <label class="label">وصف العطل <span class="text-destructive">*</span></label>
                            <input :name="`extra_devices[${i}][issue_description]`" x-model="extra.issue_description" placeholder="اللي العميل قاله" class="input" required>
                        </div>
                    </div>
                </div>
            </template>
            <button type="button" @click="extras.push({ device_type: '', brand: '', issue_description: '' })"
                    class="w-full py-2.5 rounded-xl border-2 border-dashed border-primary/40 text-primary text-sm font-medium hover:bg-primary/5 transition-colors flex items-center justify-center gap-2">
                {!! icon('plus', 'h-4 w-4') !!}
                إضافة جهاز آخر لنفس العميل
            </button>

            {{-- صور العطل (اختياري — لأول جهاز، أقصى 3 صور زي الأصل) --}}
            <div>
                <label class="label">صور العطل (اختياري)</label>
                <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" @change="previewPhotos($event)"
                       class="block w-full text-sm text-muted-foreground file:ml-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary file:text-primary-foreground hover:file:bg-primary/90 cursor-pointer">
                <template x-if="photoPreviews.length > 0">
                    <div class="flex gap-2 mt-2">
                        <template x-for="(p, pi) in photoPreviews" :key="pi">
                            <div class="relative w-16 h-16 rounded-lg overflow-hidden border pop-in">
                                <img :src="p" class="w-full h-full object-cover" alt="صورة">
                                <button type="button" @click="removePhoto(pi)" class="absolute top-0 right-0 bg-destructive text-destructive-foreground rounded-full p-0.5">{!! icon('x', 'h-3 w-3') !!}</button>
                            </div>
                        </template>
                    </div>
                </template>
                @error('photos')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
                @error('photos.*')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">المنطقة (اختياري)</label>
                    <select name="area" class="input">
                        <option value="">بدون منطقة</option>
                        @foreach (\App\Models\ServiceRequest::AREAS as $key => $label)
                            <option value="{{ $key }}" {{ old('area') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">العنوان</label>
                    <input name="address" value="{{ old('address') }}" placeholder="تفاصيل العنوان (اختياري)" class="input {{ $errors->has('address') ? 'input-error' : '' }}">
                    @error('address')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- وضع الطلب: عادي / مستعجل / طوارئ — بيحدد مهلة التسليم (SLA) --}}
            <div>
                <label class="label">وضع الطلب — بيحدد مهلة التسليم</label>
                <div class="segmented" dir="rtl">
                    <button type="button" @click="urgency = 'normal'"
                            :class="urgency === 'normal' ? 'active' : ''" class="flex-1">عادي <span class="opacity-60">(96 ساعة)</span></button>
                    <button type="button" @click="urgency = 'urgent'"
                            :class="urgency === 'urgent' ? 'active' : ''" class="flex-1">مستعجل <span class="opacity-60">(48 ساعة)</span></button>
                    <button type="button" @click="urgency = 'emergency'"
                            :class="urgency === 'emergency' ? 'active' : ''" class="flex-1">{!! icon('zap', 'h-3.5 w-3.5') !!} طوارئ <span class="opacity-60">(24 ساعة)</span></button>
                </div>
                <input type="hidden" name="urgency" :value="urgency">
                <p class="text-[10px] text-muted-foreground mt-1">الطوارئ = صيانة فورية — الجهاز لازم يخرج في 24 ساعة</p>
            </div>

            {{-- رقم التواصل الإضافي بقى جنب موبايل العميل فوق (زر «رقم إضافي») — مش حقل منفصل إجباري --}}

            {{-- الميعاد — اختياري بالكامل (مفيش وقت اجباري) --}}
            <div class="space-y-3">
                <div x-show="urgency !== 'emergency'" x-transition>
                    <label class="label">ميعاد التسليم للعميل (اختياري)</label>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <input name="preferred_date" type="date" value="{{ old('preferred_date', now()->toDateString()) }}" class="input">
                            @error('preferred_date')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <select name="preferred_time" class="input">
                                <option value="">بدون فترة محددة</option>
                                @foreach (\App\Models\ServiceRequest::TIME_SLOTS as $key => $label)
                                    <option value="{{ $key }}" {{ old('preferred_time') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <p class="text-[10px] text-muted-foreground mt-1">سيبهم فاضيين لو العميل مش محدد ميعاد — مهلة التسليم بتتحسب من وضع الطلب فوق</p>
                </div>
                <div x-show="urgency === 'emergency'" x-transition class="p-3 rounded-lg bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900 text-sm text-red-900 dark:text-red-300 flex items-center gap-2 pop-in">
                    {!! icon('zap', 'h-4 w-4 text-red-600') !!}
                    <span>طوارئ — الجهاز لازم يخرج خلال 24 ساعة، مهلة التسليم هتتكتب أوتوماتيك</span>
                </div>
                {{-- حقول الملحقات المختارة --}}
                <template x-for="acc in selectedAcc" :key="acc">
                    <input type="hidden" name="accessories[]" :value="acc">
                </template>
            </div>

            <button type="submit" class="btn btn-primary w-full" :disabled="submitting">
                {!! icon('check-circle', 'h-4 w-4') !!}
                <span x-text="submitting ? 'جاري التسجيل...' : (extras.length > 0 ? 'تسجيل ' + (extras.length + 1) + ' أجهزة' : 'تسجيل الجهاز وإنشاء الطلب')"></span>
            </button>
        </form>
    </div>

    {{-- ===== شريط الأدوات الموحد (بحث فوري + فلاتر + عداد) — HTML سليم ===== --}}
    <form method="GET" action="{{ route('reception.index') }}" class="toolbar p-3 mb-4" id="filter-form">
        <div class="flex flex-wrap items-center gap-2.5">
            {{-- بحث فوري --}}
            <div class="relative flex-1 min-w-[180px] max-w-md">
                {!! icon('search', 'h-4 w-4 absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none') !!}
                <input name="q" value="{{ request('q') }}" placeholder="بحث بالاسم أو الهاتف أو رقم الأوردر..."
                       class="input pr-10 h-10" autocomplete="off">
            </div>

            {{-- فلترة الحالة — أزرار submit سليمة (كشف - تواصل - تأكيد - تنفيذ - جاهز - تسليم - ملغى - مرتجع) --}}
            <div class="flex gap-1.5 flex-wrap">
                @php
                    $keep = array_filter(['hide_completed' => request()->boolean('hide_completed') ? 1 : null, 'q' => request('q')]);
                @endphp
                <button type="submit" name="status" value=""
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer {{ ! request('status') ? 'bg-primary text-primary-foreground shadow-md shadow-primary/25' : 'bg-muted/70 text-muted-foreground hover:bg-muted' }}">الكل</button>
                <button type="submit" name="status" value="PENDING"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer {{ request('status') === 'PENDING' ? 'bg-amber-500 text-white shadow-md shadow-amber-500/25' : 'bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 hover:opacity-80' }}">كشف</button>
                <button type="submit" name="status" value="CONTACTED"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer {{ request('status') === 'CONTACTED' ? 'bg-cyan-500 text-white shadow-md shadow-cyan-500/25' : 'bg-cyan-100 dark:bg-cyan-950/60 text-cyan-700 dark:text-cyan-300 hover:opacity-80' }}">تواصل</button>
                <button type="submit" name="status" value="CONFIRMED"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer {{ request('status') === 'CONFIRMED' ? 'bg-blue-500 text-white shadow-md shadow-blue-500/25' : 'bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 hover:opacity-80' }}">تأكيد</button>
                <button type="submit" name="status" value="IN_PROGRESS"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer {{ request('status') === 'IN_PROGRESS' ? 'bg-purple-500 text-white shadow-md shadow-purple-500/25' : 'bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 hover:opacity-80' }}">تنفيذ</button>
                <button type="submit" name="status" value="READY"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer {{ request('status') === 'READY' ? 'bg-teal-500 text-white shadow-md shadow-teal-500/25' : 'bg-teal-100 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 hover:opacity-80' }}">جاهز</button>
                <button type="submit" name="status" value="COMPLETED"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer {{ request('status') === 'COMPLETED' ? 'bg-green-600 text-white shadow-md shadow-green-600/25' : 'bg-green-100 dark:bg-green-950/60 text-green-700 dark:text-green-300 hover:opacity-80' }}">تسليم</button>
                <button type="submit" name="status" value="CANCELLED"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer {{ request('status') === 'CANCELLED' ? 'bg-red-500 text-white shadow-md shadow-red-500/25' : 'bg-red-100 dark:bg-red-950/60 text-red-700 dark:text-red-300 hover:opacity-80' }}">ملغى</button>
                <button type="submit" name="status" value="RETURNED"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer {{ request('status') === 'RETURNED' ? 'text-white shadow-md' : 'hover:opacity-80' }} {{ request('status') === 'RETURNED' ? '' : 'bg-orange-100 dark:bg-orange-950/60 text-orange-700 dark:text-orange-300' }}"
                        style="{{ request('status') === 'RETURNED' ? 'background: oklch(0.7 0.17 55)' : '' }}">مرتجع</button>
            </div>

            {{-- مفتاح إخفاء المسلَّم — switch حديث --}}
            <button type="submit" name="hide_completed" value="{{ request()->boolean('hide_completed') ? 0 : 1 }}"
                    class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer {{ request()->boolean('hide_completed') ? 'bg-primary/10 text-primary border border-primary/30' : 'bg-muted/70 text-muted-foreground border border-transparent hover:bg-muted' }}">
                <span class="switch {{ request()->boolean('hide_completed') ? 'on' : '' }}"><span class="knob"></span></span>
                <span>إخفاء المسلَّم</span>
            </button>

            <div class="flex-1"></div>

            {{-- عداد النتائج + مسح --}}
            <span class="text-xs text-muted-foreground font-medium whitespace-nowrap">{{ $requests->total() }} طلب</span>
            @if (request()->hasAny(['q', 'status', 'hide_completed']))
                <a href="{{ route('reception.index') }}" class="text-xs text-primary hover:underline font-bold flex items-center gap-1">
                    {!! icon('x', 'h-3 w-3') !!} مسح الفلاتر
                </a>
            @endif
        </div>
    </form>

    {{-- ===== جدول الطلبات الموحد — نفس شكل الأدمن ===== --}}
    {{-- الأدمن يشوف زر الحذف وهو داخل صفحة الاستقبال (تاب الطلبات) زي الأصل — الاستقبال مفيش عنده حذف --}}
    @include('partials.requests-table', ['requests' => $requests, 'showRoute' => 'reception.show', 'adminView' => auth()->user()->isAdmin()])

    {{ $requests->links() }}
</div>

<script>
    // حالة اللوحة — Alpine
    function receptionBoard(opts) {
        return {
            newOpen: false,
        };
    }

    function receptionForm(accessoriesMap) {
        return {
            phone: '', altPhone: '', showAltPhone: false, lookup: null, looking: false, checked: false,
            deviceType: '', showAcc: false, selectedAcc: [],
            extras: [], photoFiles: [], photoPreviews: [],
            urgency: 'normal', submitting: false,

            get isExisting() { return !!(this.lookup && this.lookup.found) },
            get deviceAccessories() { return accessoriesMap[this.deviceType] || [] },

            lookupPhone() {
                this.checked = false; this.lookup = null;
                if (/^01[0125][0-9]{8}$/.test(this.phone)) {
                    this.looking = true;
                    fetch('{{ route('reception.lookup') }}?phone=' + this.phone)
                        .then(r => r.json())
                        .then(d => {
                            this.lookup = d; this.checked = true; this.looking = false;
                            if (d.found) {
                                var nameField = document.getElementById('cust-name-field');
                                if (nameField && !nameField.value.trim()) nameField.value = d.customer.name;
                            }
                        })
                        .catch(() => this.looking = false);
                }
            },

            toggleAcc(acc) {
                this.selectedAcc = this.selectedAcc.includes(acc)
                    ? this.selectedAcc.filter(x => x !== acc)
                    : [...this.selectedAcc, acc];
            },

            previewPhotos(event) {
                const files = Array.from(event.target.files || []).slice(0, 3);
                this.photoFiles = files;
                this.photoPreviews = [];
                files.forEach(f => {
                    const reader = new FileReader();
                    reader.onload = () => this.photoPreviews.push(reader.result);
                    reader.readAsDataURL(f);
                });
            },

            removePhoto(i) {
                this.photoFiles.splice(i, 1);
                this.photoPreviews.splice(i, 1);
                const dt = new DataTransfer();
                this.photoFiles.forEach(f => dt.items.add(f));
                const input = this.$root.querySelector('input[name="photos[]"]');
                if (input) input.files = dt.files;
            },
        }
    }
</script>
@endsection
