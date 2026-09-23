@extends('layouts.app')
@section('title', 'طلب '.$serviceRequest->order_number.' — استقبال')

@section('content')
<div class="container mx-auto max-w-4xl px-4 sm:px-6 py-8">
    <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
        <div>
            <a href="{{ route('reception.index') }}" class="text-xs text-muted-foreground hover:text-primary mb-1 inline-flex items-center gap-1 transition-colors">
                {!! icon('arrow-right', 'h-3 w-3') !!} رجوع للطلبات
            </a>
            <h1 class="text-2xl font-extrabold flex items-center gap-2 flex-wrap">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary shrink-0 pop-in">{!! device_icon($serviceRequest->device_type, 'h-5 w-5') !!}</div>
                {{ $serviceRequest->customer->name }}
                <span class="text-muted-foreground text-lg font-normal">— {{ $serviceRequest->device_type }}{{ $serviceRequest->brand ? ' '.$serviceRequest->brand : '' }}</span>
                @if ($serviceRequest->isImmediate())
                    <span class="inline-flex items-center gap-0.5 text-[10px] font-bold text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-950/50 px-1.5 py-0.5 rounded-md">{!! icon('zap', 'h-2.5 w-2.5') !!} فوري</span>
                @endif
                @if ($serviceRequest->return_count > 0)
                    <span class="inline-flex items-center gap-0.5 text-[10px] font-bold text-orange-700 dark:text-orange-300 bg-orange-100 dark:bg-orange-950/50 px-1.5 py-0.5 rounded-md">{!! icon('undo', 'h-2.5 w-2.5') !!} مرتجع {{ $serviceRequest->return_count }}×</span>
                @endif
                @if ($serviceRequest->isOverdue())
                    <span class="badge bg-red-100 text-red-800 border-red-200 dark:bg-red-950/60 dark:text-red-300 dark:border-red-900">تجاوز مهلة التسليم!</span>
                @endif
            </h1>
            <p class="text-muted-foreground text-sm mt-1 flex items-center gap-2 flex-wrap">
                <span class="font-mono" dir="ltr">{{ $serviceRequest->order_number }}</span>
                <span>•</span>
                <span>{{ $serviceRequest->timeAgo() }}</span>
                <span>•</span>
                <span>{{ dt($serviceRequest->created_at, true) }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="badge {{ $serviceRequest->statusColor() }} text-sm px-3 py-1"><span class="h-1.5 w-1.5 rounded-full {{ $serviceRequest->statusDot() }}"></span>{{ $serviceRequest->statusLabel() }}</span>
            <a href="tel:{{ $serviceRequest->customer->phone }}" class="btn btn-outline btn-sm" title="اتصال بالعميل">{!! icon('phone', 'h-3.5 w-3.5') !!}</a>
            <a href="{{ route('reception.receipt', $serviceRequest) }}" target="_blank" class="btn btn-outline btn-sm">
                {!! icon('printer', 'h-3.5 w-3.5') !!} إيصال
            </a>
            {{-- حذف الطلب — للأدمن بس (زي الأصل) --}}
            @if (auth()->user()->isAdmin())
                <form method="POST" action="{{ route('admin.requests.destroy', $serviceRequest) }}"
                      onsubmit="return confirm('سيتم حذف الطلب {{ $serviceRequest->order_number }} بكل بياناته المرتبطة نهائياً — متأكد؟')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm bg-red-100 dark:bg-red-950/60 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-900 hover:bg-red-200 dark:hover:bg-red-950" title="حذف الطلب نهائياً">
                        {!! icon('trash', 'h-3.5 w-3.5') !!} حذف
                    </button>
                </form>
            @endif
        </div>
    </div>

    @include('reception.partials.tabs')

    {{-- ===== بطاقة التشغيل: ميعاد الدخول/الخروج + وضع الطلب + الدفع ===== --}}
    <div class="card p-4 mb-4 grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 shrink-0">{!! icon('log-in', 'h-4 w-4') !!}</div>
            <div class="min-w-0">
                <p class="text-[10px] font-bold text-muted-foreground">ميعاد الدخول</p>
                <p class="text-sm font-extrabold" dir="ltr">{{ $serviceRequest->entered_at?->format('d/m/Y H:i') ?? '—' }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg {{ $serviceRequest->isOverdue() ? 'bg-red-100 dark:bg-red-950/50 text-red-600 dark:text-red-400' : 'bg-green-100 dark:bg-green-950/50 text-green-600 dark:text-green-400' }} shrink-0">{!! icon('log-out', 'h-4 w-4') !!}</div>
            <div class="min-w-0">
                <p class="text-[10px] font-bold text-muted-foreground">ميعاد الخروج (حسب وضع الطلب)</p>
                <p class="text-sm font-extrabold {{ $serviceRequest->isOverdue() ? 'text-red-600 dark:text-red-400' : '' }}" dir="ltr">{{ $serviceRequest->slaDeadline()->format('d/m H:i') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg {{ $serviceRequest->urgency === 'emergency' ? 'bg-red-100 dark:bg-red-950/50 text-red-600' : ($serviceRequest->urgency === 'urgent' ? 'bg-amber-100 dark:bg-amber-950/50 text-amber-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300') }} shrink-0">{!! icon($serviceRequest->urgency === 'emergency' ? 'alert-triangle' : ($serviceRequest->urgency === 'urgent' ? 'zap' : 'clock'), 'h-4 w-4') !!}</div>
            <div class="min-w-0">
                <p class="text-[10px] font-bold text-muted-foreground">وضع الطلب ({{ $serviceRequest->slaHours() >= 72 ? round($serviceRequest->slaHours() / 24).' أيام' : $serviceRequest->slaHours().' ساعة' }} مهلة)</p>
                <span class="urgency-flag {{ $serviceRequest->urgencyColor() }}">{{ $serviceRequest->urgencyLabel() }}</span>
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-green-100 dark:bg-green-950/50 text-green-600 dark:text-green-400 shrink-0">{!! icon('banknote', 'h-4 w-4') !!}</div>
            <div class="min-w-0">
                <p class="text-[10px] font-bold text-muted-foreground">التحصيل{{ $serviceRequest->status === 'COMPLETED' ? ' (اتحصل)' : '' }}</p>
                <p class="text-sm font-extrabold">{{ $serviceRequest->payment_method ? $serviceRequest->paymentLabel() : '—' }} @if($serviceRequest->status === 'COMPLETED')<span class="text-[10px] text-green-600">من إيردات {{ $serviceRequest->completed_at?->format('d/m') }}</span>@endif</p>
            </div>
        </div>
        {{-- شريط استهلاك المهلة --}}
        @if (! in_array($serviceRequest->status, ['COMPLETED', 'CANCELLED']))
            <div class="sm:col-span-2 lg:col-span-4">
                @php $prog = min(100, $serviceRequest->slaProgress() * 100); @endphp
                <div class="flex items-center gap-2">
                    <div class="flex-1 h-2 rounded-full bg-muted overflow-hidden" dir="ltr">
                        <div class="h-full rounded-full transition-all duration-700 {{ $prog >= 100 ? 'bg-red-500' : ($prog > 70 ? 'bg-amber-500' : 'bg-green-500') }}" style="width: {{ $prog }}%"></div>
                    </div>
                    <span class="text-[10px] font-extrabold shrink-0 {{ $serviceRequest->isOverdue() ? 'text-red-600 dark:text-red-400' : 'text-muted-foreground' }}">{{ $serviceRequest->slaRemainingLabel() }}</span>
                </div>
            </div>
        @endif
    </div>

    {{-- مرتجع سابق؟ اعرض السبب --}}
    @if ($serviceRequest->return_reason)
        <div class="card p-4 mb-4 border-orange-300 dark:border-orange-800 bg-orange-50/50 dark:bg-orange-950/20">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-orange-100 dark:bg-orange-950/60 text-orange-600 dark:text-orange-400 shrink-0">{!! icon('undo', 'h-4 w-4') !!}</div>
                <div class="flex-1">
                    <p class="font-bold text-sm text-orange-900 dark:text-orange-300">مرتجع — سبب الإرجاع</p>
                    <p class="text-xs text-orange-800/80 dark:text-orange-400/80 mt-1 leading-relaxed">{{ $serviceRequest->return_reason }}</p>
                    @if ($serviceRequest->returned_at)
                        <p class="text-[10px] text-muted-foreground mt-1">آخر إرجاع: {{ dt($serviceRequest->returned_at, true) }}</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="grid lg:grid-cols-3 gap-4">

        {{-- معلومات الطلب --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="card p-5">
                <h2 class="font-bold mb-4 flex items-center gap-2">{!! icon('file-text', 'h-4 w-4 text-primary') !!} تفاصيل الطلب</h2>
                <div class="grid sm:grid-cols-2 gap-3 text-sm">
                    <div class="p-3 rounded-xl bg-muted/40 border border-border/40 flex justify-between transition-colors hover:border-primary/30">
                        <span class="text-muted-foreground">العميل</span>
                        <b><a href="{{ route('reception.customers.show', $serviceRequest->customer) }}" class="hover:text-primary">{{ $serviceRequest->customer->name }}</a></b>
                    </div>
                    <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">هاتف العميل</span><b dir="ltr"><a href="tel:{{ $serviceRequest->customer->phone }}" class="text-primary hover:underline">{{ $serviceRequest->customer->phone }}</a></b></div>
                    <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">المنطقة</span><b>{{ $serviceRequest->areaLabel() }}</b></div>
                    <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">هاتف التواصل</span><b dir="ltr">{{ $serviceRequest->phone }}</b></div>
                    <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">الميعاد المطلوب</span><b>{{ dt($serviceRequest->preferred_date) }} — {{ $serviceRequest->timeSlotLabel() }}</b></div>
                    @if ($serviceRequest->department)
                        <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">القسم</span><b>{{ $serviceRequest->department->name }}</b></div>
                    @endif
                    @if ($serviceRequest->assignedTechnician)
                        <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">الفني</span><b>{{ $serviceRequest->assignedTechnician->name }}</b></div>
                    @endif
                    @if ($serviceRequest->warranty_months > 0)
                        <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">الضمان</span><b>{{ $serviceRequest->warranty_months }} شهر</b></div>
                        <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">ينتهي الضمان</span><b>{{ dt($serviceRequest->warranty_end_date) }}</b></div>
                    @endif
                    <div class="sm:col-span-2 p-3 rounded-xl bg-muted/40">
                        <div class="text-muted-foreground text-xs mb-1">العنوان</div>
                        <div>{{ $serviceRequest->address }}</div>
                    </div>
                    <div class="sm:col-span-2 p-3 rounded-xl bg-muted/40">
                        <div class="text-muted-foreground text-xs mb-1">وصف العطل</div>
                        <div class="leading-relaxed">{{ $serviceRequest->issue_description }}</div>
                    </div>
                </div>

                @if ($serviceRequest->photos && count($serviceRequest->photos))
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($serviceRequest->photos as $photo)
                            <a href="{{ asset(ltrim($photo, '/')) }}" target="_blank" class="block w-20 h-20 rounded-lg overflow-hidden border border-border">
                                <img src="{{ asset(ltrim($photo, '/')) }}" class="w-full h-full object-cover" alt="صورة">
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- ملاحظات داخلية للطاقم (استقبال + أدمن) --}}
            <div class="card p-5" x-data="{ adding: false }">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-bold flex items-center gap-2">{!! icon('message', 'h-4 w-4 text-primary') !!} ملاحظات الطاقم (داخلية)</h2>
                    <button @click="adding = !adding" class="btn btn-outline btn-sm">{!! icon('plus', 'h-3.5 w-3.5') !!} إضافة</button>
                </div>

                <form x-show="adding" x-transition method="POST" action="{{ route('reception.notes.store', $serviceRequest) }}" class="mb-4 p-3 rounded-xl bg-muted/30 border border-border/60 space-y-2" style="display:none">
                    @csrf
                    <textarea name="content" rows="2" placeholder="ملاحظة داخلية عن الطلب أو العميل (العميل مش بيشوفها)..." class="input" required></textarea>
                    <button type="submit" class="btn btn-primary btn-sm w-full">{!! icon('save', 'h-3.5 w-3.5') !!} حفظ الملاحظة</button>
                </form>

                @forelse ($serviceRequest->internalNotes as $note)
                    <div class="p-3 rounded-xl bg-muted/40 mb-2">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <div class="flex items-center gap-2 text-xs">
                                <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-primary/10 text-primary text-[10px] font-bold">{{ mb_substr($note->author?->name ?? '?', 0, 1) }}</div>
                                <b>{{ $note->author?->name }}</b>
                                <span class="text-muted-foreground">{{ $note->author?->roleLabel() }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] text-muted-foreground">{{ dt($note->created_at, true) }}</span>
                                <form method="POST" action="{{ route('reception.notes.destroy', $note) }}" onsubmit="return confirm('حذف الملاحظة؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-muted-foreground hover:text-destructive cursor-pointer">{!! icon('trash', 'h-3.5 w-3.5') !!}</button>
                                </form>
                            </div>
                        </div>
                        <p class="text-sm leading-relaxed">{{ $note->content }}</p>
                    </div>
                @empty
                    <p class="text-sm text-muted-foreground text-center py-4">مفيش ملاحظات داخلية على الطلب ده</p>
                @endforelse
            </div>

            {{-- القطع المستخدمة + إضافة قطعة --}}
            <div class="card p-5" x-data="{ mode: null }">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-bold flex items-center gap-2">{!! icon('package', 'h-4 w-4 text-primary') !!} قطع الغيار المستخدمة</h2>
                    <button @click="mode = mode ? null : 'select'" class="btn btn-outline btn-sm">{!! icon('plus', 'h-3.5 w-3.5') !!} إضافة قطعة</button>
                </div>

                {{-- اختيار طريقة الإضافة --}}
                <div x-show="mode === 'select'" x-transition class="mb-4 p-3 rounded-xl bg-muted/30 border border-border/60 grid grid-cols-2 gap-2" style="display:none">
                    <button @click="mode = 'inventory'" class="btn btn-outline btn-sm">{!! icon('package', 'h-3.5 w-3.5') !!} من المخزن</button>
                    <button @click="mode = 'custom'" class="btn btn-outline btn-sm">{!! icon('edit', 'h-3.5 w-3.5') !!} قطعة خارجية</button>
                </div>

                {{-- إضافة من المخزن --}}
                <form x-show="mode === 'inventory'" x-transition method="POST" action="{{ route('reception.parts.store', $serviceRequest) }}" class="mb-4 p-3 rounded-xl bg-muted/30 border border-border/60 space-y-2" style="display:none">
                    @csrf
                    <label class="label">اختر الصنف</label>
                    <select name="inventory_item_id" class="input" required>
                        <option value="">— اختر من المخزن —</option>
                        @foreach ($inventoryItems as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}{{ $item->brand ? ' ('.$item->brand.')' : '' }} — متاح {{ $item->quantity }} {{ $item->unit }}</option>
                        @endforeach
                    </select>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="label">الكمية</label>
                            <input name="quantity" type="number" step="0.01" min="0.01" value="1" class="input" dir="ltr" required>
                        </div>
                        <div>
                            <label class="label">سعر الوحدة (0 = سعر المخزن)</label>
                            <input name="unit_price" type="number" step="0.01" min="0" value="0" class="input" dir="ltr">
                        </div>
                    </div>
                    <p class="text-[10px] text-muted-foreground">بيتخصم تلقائياً من المخزن</p>
                    <button type="submit" class="btn btn-primary btn-sm w-full">{!! icon('save', 'h-3.5 w-3.5') !!} إضافة</button>
                </form>

                {{-- قطعة خارجية --}}
                <form x-show="mode === 'custom'" x-transition method="POST" action="{{ route('reception.parts.store', $serviceRequest) }}" class="mb-4 p-3 rounded-xl bg-muted/30 border border-border/60 space-y-2" style="display:none">
                    @csrf
                    <label class="label">اسم القطعة</label>
                    <input name="custom_name" placeholder="مثال: كوندنساتور تكييف" class="input" required>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="label">الكمية</label>
                            <input name="quantity" type="number" step="0.01" min="0.01" value="1" class="input" dir="ltr" required>
                        </div>
                        <div>
                            <label class="label">سعر الوحدة</label>
                            <input name="unit_price" type="number" step="0.01" min="0" class="input" dir="ltr" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-full">{!! icon('save', 'h-3.5 w-3.5') !!} إضافة</button>
                </form>

                @if ($serviceRequest->usedParts->isNotEmpty())
                    <div class="space-y-2">
                        @foreach ($serviceRequest->usedParts as $part)
                            <div class="flex items-center justify-between p-3 rounded-xl bg-muted/40 text-sm gap-2">
                                <b>{{ $part->name() }} <span class="text-muted-foreground">× {{ $part->quantity }}</span></b>
                                <div class="flex items-center gap-2">
                                    <b>{{ money($part->total()) }}</b>
                                    <form method="POST" action="{{ route('reception.parts.destroy', $part) }}" onsubmit="return confirm('حذف القطعة؟ هترجع للمخزن لو كانت منه')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-muted-foreground hover:text-destructive cursor-pointer">{!! icon('trash', 'h-3.5 w-3.5') !!}</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                        <div class="flex justify-between p-3 rounded-xl border border-primary/30 bg-primary/5 text-sm">
                            <b>إجمالي القطع</b>
                            <b class="text-primary">{{ money($serviceRequest->usedParts->sum(fn ($p) => $p->total())) }}</b>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-muted-foreground text-center py-4">مفيش قطع مسجلة على الطلب</p>
                @endif
            </div>
        </div>

        {{-- إدارة الطلب --}}
        <div class="space-y-4">
            {{-- زر مرتجع للفني — العميل رجّع الجهاز والشركة هتصلحه تاني --}}
            @if (! in_array($serviceRequest->status, ['CANCELLED', 'RETURNED']) && \App\Support\Permissions::canEditRequest(auth()->user()->role, 'IN_PROGRESS'))
                <div class="card p-5 border-orange-300/70 dark:border-orange-800/60" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="btn-return w-full">
                        {!! icon('undo', 'h-4 w-4') !!}
                        تسجيل مرتجع — العميل رجّع الجهاز بعد التسليم
                    </button>
                    <form x-show="open" x-transition method="POST" action="{{ route('reception.return', $serviceRequest) }}" class="mt-3 space-y-2" style="display:none">
                        @csrf
                        <label class="label">سبب الإرجاع <span class="text-destructive">*</span></label>
                        <textarea name="reason" rows="2" class="input" placeholder="مثال: العطل رجع تاني بعد التسليم / مشكلة جديدة في نفس الجهاز..." required></textarea>
                        <p class="text-[10px] text-muted-foreground">الطلب هيرجع للفني بمهلة تسليم جديدة (حسب وضعه) وهيتإنذر الفني والأدمن</p>
                        <button type="submit" class="btn btn-primary w-full">{!! icon('undo', 'h-4 w-4') !!} تأكيد المرتجع</button>
                    </form>
                </div>
            @endif

            @if (\App\Support\Permissions::canEditRequest(auth()->user()->role, $serviceRequest->status))
                <div class="card p-5">
                    <h2 class="font-bold mb-4 flex items-center gap-2">{!! icon('edit', 'h-4 w-4 text-primary') !!} إدارة الطلب</h2>
                    <form method="POST" action="{{ route('reception.update', $serviceRequest) }}" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="label">الحالة</label>
                            <select name="status" class="input" required>
                                @foreach (\App\Models\ServiceRequest::STATUSES as $status)
                                    <option value="{{ $status }}" {{ old('status', $serviceRequest->status) === $status ? 'selected' : '' }}>{{ \App\Models\ServiceRequest::STATUS_LABELS[$status] }}</option>
                                @endforeach
                            </select>
                            @if (auth()->user()->role !== 'ADMIN')
                                <p class="text-[10px] text-muted-foreground mt-1">الطلب الحالي نشط — بعد التسليم أو الإلغاء التعديل للأدمن فقط</p>
                            @endif
                        </div>

                        {{-- وضع الطلب — تغييره يفتح مهلة تسليم جديدة --}}
                        <div>
                            <label class="label">وضع الطلب (بيحدد مهلة التسليم)</label>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach (['normal' => 'عادي', 'urgent' => 'مستعجل', 'emergency' => 'طوارئ'] as $uKey => $uLabel)
                                    <label class="flex items-center justify-center gap-1.5 rounded-xl border-2 px-2 py-2 text-xs font-extrabold cursor-pointer transition-all
                                           {{ old('urgency', $serviceRequest->urgency) === $uKey ? 'border-primary bg-primary/5 text-primary' : 'border-border text-muted-foreground hover:border-primary/40' }}">
                                        <input type="radio" name="urgency" value="{{ $uKey }}" class="sr-only" {{ old('urgency', $serviceRequest->urgency) === $uKey ? 'checked' : '' }}>
                                        {{ $uLabel }}
                                    </label>
                                @endforeach
                            </div>
                            <p class="text-[10px] text-muted-foreground mt-1">طوارئ 24 ساعة · مستعجل 48 · عادي 7 أيام — تغيير الوضع يحدّث مهلة الخروج</p>
                        </div>

                        {{-- طريقة الدفع — التسليم = تم التحصيل بالكامل --}}
                        <div>
                            <label class="label">طريقة الدفع (عند التسليم = تم تحصيل الأجر كاملاً)</label>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="flex items-center justify-center gap-1.5 rounded-xl border-2 px-2 py-2 text-xs font-extrabold cursor-pointer transition-all
                                       {{ old('payment_method', $serviceRequest->payment_method) === 'cash' ? 'border-green-500 bg-green-50 dark:bg-green-950/30 text-green-700 dark:text-green-300' : 'border-border text-muted-foreground hover:border-green-400/50' }}">
                                    <input type="radio" name="payment_method" value="cash" class="sr-only" {{ old('payment_method', $serviceRequest->payment_method) === 'cash' ? 'checked' : '' }}>
                                    {!! icon('banknote', 'h-3.5 w-3.5') !!} كاش
                                </label>
                                <label class="flex items-center justify-center gap-1.5 rounded-xl border-2 px-2 py-2 text-xs font-extrabold cursor-pointer transition-all
                                       {{ old('payment_method', $serviceRequest->payment_method) === 'transfer' ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/30 text-blue-700 dark:text-blue-300' : 'border-border text-muted-foreground hover:border-blue-400/50' }}">
                                    <input type="radio" name="payment_method" value="transfer" class="sr-only" {{ old('payment_method', $serviceRequest->payment_method) === 'transfer' ? 'checked' : '' }}>
                                    {!! icon('credit-card', 'h-3.5 w-3.5') !!} تحويل
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="label">تعيين فني</label>
                            <select name="assigned_technician_id" class="input">
                                <option value="">بدون فني</option>
                                @foreach ($technicians as $t)
                                    <option value="{{ $t->id }}" {{ (string) old('assigned_technician_id', $serviceRequest->assigned_technician_id) === (string) $t->id ? 'selected' : '' }}>
                                        {{ $t->name }}{{ $t->specialty ? ' ('.$t->specialty.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[10px] text-muted-foreground mt-1">تعيين فني بيأكد الطلب تلقائياً ويّصل إشعار للفني</p>
                        </div>

                        <div>
                            <label class="label">السعر (ج.م) — الأجر الكامل اللي بيديه العميل عند التسليم</label>
                            <input name="price" type="number" step="0.01" min="0" value="{{ old('price', $serviceRequest->price) }}" placeholder="0.00" class="input" dir="ltr">
                        </div>

                        <div>
                            <label class="label">الضمان (شهر) — عند الإكمال</label>
                            <input name="warranty_months" type="number" min="0" max="36" value="{{ old('warranty_months', $serviceRequest->warranty_months ?? 0) }}" class="input" dir="ltr">
                        </div>

                        <div class="p-3 rounded-xl bg-green-50/60 dark:bg-green-950/20 border border-green-200/70 dark:border-green-900/60">
                            <p class="text-[11px] font-bold text-green-800 dark:text-green-300 flex items-center gap-1.5">{!! icon('check-circle', 'h-3.5 w-3.5') !!} تسليم الطلب = تحصيل الأجر كاملاً = من إيردات اليوم</p>
                            <p class="text-[10px] text-muted-foreground mt-1">اختار طريقة الدفع فوق (كاش/تحويل) قبل ما تسلّم الطلب — هتتحسب في إحصائيات النهاردة</p>
                        </div>

                        <div>
                            <label class="label">ملاحظة للعميل</label>
                            <textarea name="admin_notes" rows="2" class="input">{{ old('admin_notes', $serviceRequest->admin_notes) }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-full">
                            {!! icon('save', 'h-4 w-4') !!}
                            حفظ التعديلات
                        </button>
                    </form>
                </div>
            @else
                <div class="card p-5 text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl {{ $serviceRequest->status === 'COMPLETED' ? 'bg-green-100 dark:bg-green-950/40 text-green-600' : 'bg-red-100 dark:bg-red-950/40 text-red-600' }}">
                        {!! icon($serviceRequest->status === 'COMPLETED' ? 'check-circle' : 'x', 'h-6 w-6') !!}
                    </div>
                    <p class="text-sm font-bold mb-1">{{ $serviceRequest->statusLabel() }}</p>
                    <p class="text-xs text-muted-foreground">
                        @if ($serviceRequest->status === 'COMPLETED')
                            اكتملت في {{ dt($serviceRequest->completed_at, true) }}
                            @if ($serviceRequest->hasWarranty()) — ضمان {{ $serviceRequest->warranty_months }} شهر @endif
                        @else
                            الطلب ملغى — التعديل صلاحية الأدمن فقط
                        @endif
                    </p>
                </div>
            @endif

            {{-- معلومات مالية --}}
            @if ($serviceRequest->price !== null)
                <div class="card p-5">
                    <h2 class="font-bold mb-3 flex items-center gap-2">{!! icon('wallet', 'h-4 w-4 text-primary') !!} المالية</h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-muted-foreground">أجر الصيانة (يتحصل كاملاً عند التسليم)</span><b>{{ money($serviceRequest->price) }}</b></div>
                        @if ($serviceRequest->status === 'COMPLETED')
                            <div class="flex justify-between border-t border-border pt-2">
                                <span class="text-muted-foreground">المحصّل فعلياً</span>
                                <b class="text-green-600">{{ money($serviceRequest->price) }} {{ $serviceRequest->paymentLabel() !== '—' ? '('.$serviceRequest->paymentLabel().')' : '' }}</b>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
