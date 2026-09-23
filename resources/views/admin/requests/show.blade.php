@extends('admin.layout')
@section('title', $serviceRequest->order_number.' — الطلبات')
@section('admin_title', 'طلب '.$serviceRequest->order_number)
@section('admin_subtitle', $serviceRequest->customer->name.' — '.$serviceRequest->device_type)

@section('admin_actions')
    <a href="{{ route('admin.requests.index') }}" class="btn btn-outline btn-sm">{!! icon('arrow-right', 'h-3.5 w-3.5') !!} كل الطلبات</a>
    <a href="{{ route('reception.receipt', $serviceRequest) }}" target="_blank" class="btn btn-outline btn-sm">{!! icon('printer', 'h-3.5 w-3.5') !!} إيصال</a>
    <form method="POST" action="{{ route('admin.requests.destroy', $serviceRequest) }}" class="inline" onsubmit="return confirm('سيتم حذف كل البيانات المرتبطة به نهائياً — متأكد؟')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-sm bg-red-100 dark:bg-red-950/60 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-900 hover:bg-red-200 dark:hover:bg-red-950">{!! icon('trash', 'h-3.5 w-3.5') !!} حذف الطلب</button>
    </form>
@endsection

@section('admin_content')
<div class="grid lg:grid-cols-3 gap-4">

    {{-- العمود الرئيسي --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- تفاصيل الطلب --}}
        <div class="card p-5">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <h2 class="font-bold flex items-center gap-2">{!! icon('file-text', 'h-4 w-4 text-primary') !!} تفاصيل الطلب</h2>
                <span class="badge {{ $serviceRequest->statusColor() }} text-sm px-3 py-1">{{ $serviceRequest->statusLabel() }}</span>
            </div>
            <div class="grid sm:grid-cols-2 gap-3 text-sm">
                <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">العميل</span><b><a href="{{ route('admin.customers.show', $serviceRequest->customer) }}" class="text-primary hover:underline">{{ $serviceRequest->customer->name }}</a></b></div>
                <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">هاتف العميل</span><b dir="ltr"><a href="tel:{{ $serviceRequest->customer->phone }}" class="text-primary hover:underline">{{ $serviceRequest->customer->phone }}</a></b></div>
                <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">نوع الجهاز</span><b>{{ $serviceRequest->device_type }}{{ $serviceRequest->brand ? ' — '.$serviceRequest->brand : '' }}</b></div>
                <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">المنطقة</span><b>{{ $serviceRequest->areaLabel() }}</b></div>
                <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">هاتف التواصل</span><b dir="ltr">{{ $serviceRequest->phone }}</b></div>
                <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">الميعاد المطلوب</span><b>{{ dt($serviceRequest->preferred_date) }} — {{ $serviceRequest->timeSlotLabel() }}</b></div>
                @if ($serviceRequest->department)
                    <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">القسم المسؤول</span><b>{{ $serviceRequest->department->name }}</b></div>
                @endif
                @if ($serviceRequest->assignedTechnician)
                    <div class="p-3 rounded-xl bg-muted/40 flex justify-between"><span class="text-muted-foreground">الفني المسؤول</span><b>{{ $serviceRequest->assignedTechnician->name }}</b></div>
                @endif
                <div class="sm:col-span-2 p-3 rounded-xl bg-muted/40">
                    <div class="text-muted-foreground text-xs mb-1">العنوان</div>
                    <div>{{ $serviceRequest->address }}</div>
                </div>
                <div class="sm:col-span-2 p-3 rounded-xl bg-muted/40">
                    <div class="text-muted-foreground text-xs mb-1">وصف العطل (كتبه العميل)</div>
                    <div class="leading-relaxed">{{ $serviceRequest->issue_description }}</div>
                </div>
                @if ($serviceRequest->photos && count($serviceRequest->photos))
                    <div class="sm:col-span-2">
                        <div class="text-muted-foreground text-xs mb-2">صور العميل</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($serviceRequest->photos as $photo)
                                <a href="{{ asset(ltrim($photo, '/')) }}" target="_blank" class="block w-20 h-20 rounded-lg overflow-hidden border border-border">
                                    <img src="{{ asset(ltrim($photo, '/')) }}" class="w-full h-full object-cover" alt="صورة">
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- القطع المستخدمة --}}
        <div class="card p-5" x-data="{ addOpen: false }">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <h2 class="font-bold flex items-center gap-2">{!! icon('package', 'h-4 w-4 text-primary') !!} قطع الغيار المستخدمة ({{ $serviceRequest->usedParts->count() }})</h2>
                <button @click="addOpen = !addOpen" class="btn btn-outline btn-sm">{!! icon('plus', 'h-3.5 w-3.5') !!} إضافة قطعة</button>
            </div>

            {{-- إضافة قطعة --}}
            <form x-show="addOpen" x-transition method="POST" action="{{ route('admin.requests.parts', $serviceRequest) }}" class="p-4 rounded-xl bg-muted/30 border border-border/60 space-y-3 mb-4" style="display:none">
                @csrf
                <div class="grid sm:grid-cols-2 gap-3">
                    <div>
                        <label class="label">من المخزن</label>
                        <select name="inventory_item_id" class="input" onchange="document.getElementById('custom-name-{{ $serviceRequest->id }}').value = ''">
                            <option value="">— قطعة خارجية (اكتب الاسم) —</option>
                            @foreach ($inventory as $item)
                                <option value="{{ $item->id }}">{{ $item->name }} (متاح: {{ $item->quantity }} — {{ money($item->sell_price) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">أو اسم قطعة خارجية</label>
                        <input id="custom-name-{{ $serviceRequest->id }}" name="custom_name" placeholder="اسم القطعة" class="input" oninput="this.form.inventory_item_id.value = ''">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label">الكمية *</label>
                        <input name="quantity" type="number" step="0.01" min="0.01" placeholder="1" class="input" required dir="ltr">
                    </div>
                    <div>
                        <label class="label">سعر الوحدة (ج.م) *</label>
                        <input name="unit_price" type="number" step="0.01" min="0" placeholder="0.00" class="input" required dir="ltr">
                    </div>
                </div>
                <p class="text-[10px] text-muted-foreground">القطعة الداخلية بتخصم من المخزن تلقائياً — وسعر الوحدة بيتعبى تلقائيً لو سبته صفر</p>
                <button type="submit" class="btn btn-primary btn-sm">{!! icon('save', 'h-3.5 w-3.5') !!} إضافة</button>
            </form>

            <div class="space-y-2">
                @forelse ($serviceRequest->usedParts as $part)
                    <div class="flex items-center justify-between p-3 rounded-xl bg-muted/40 text-sm flex-wrap gap-2">
                        <div class="flex items-center gap-2">
                            <span class="badge {{ $part->inventory_item_id ? 'bg-primary/10 text-primary border-primary/20' : 'bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-900' }} text-[10px]">
                                {{ $part->inventory_item_id ? 'من المخزن' : 'خارجية' }}
                            </span>
                            <b>{{ $part->name() }}</b>
                            <span class="text-muted-foreground text-xs">× {{ $part->quantity }} × {{ number_format($part->unit_price, 2) }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <b>{{ money($part->total()) }}</b>
                            <form method="POST" action="{{ route('admin.requests.parts.destroy', $part) }}" onsubmit="return confirm('حذف القطعة وإرجاعها للمخزن؟')">
                                @csrf
                                @method('DELETE')
                                <button class="text-destructive hover:opacity-70 cursor-pointer" title="حذف وإرجاع للمخزن">{!! icon('trash', 'h-4 w-4') !!}</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-muted-foreground text-center py-3">مفيش قطع مسجلة على الطلب</p>
                @endforelse
                @if ($serviceRequest->usedParts->count())
                    <div class="flex justify-between p-3 rounded-xl bg-primary/5 border border-primary/10 text-sm">
                        <b>إجمالي القطع</b>
                        <b class="text-primary">{{ money($serviceRequest->partsTotal()) }}</b>
                    </div>
                @endif
            </div>
        </div>

        {{-- الملاحظات الداخلية --}}
        <div class="card p-5" x-data="{ noteOpen: false }">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <h2 class="font-bold flex items-center gap-2">{!! icon('message', 'h-4 w-4 text-primary') !!} ملاحظات داخلية ({{ $serviceRequest->internalNotes->count() }})</h2>
                <button @click="noteOpen = !noteOpen" class="btn btn-outline btn-sm">{!! icon('plus', 'h-3.5 w-3.5') !!} ملاحظة</button>
            </div>

            <form x-show="noteOpen" x-transition method="POST" action="{{ route('admin.requests.notes', $serviceRequest) }}" class="flex gap-2 mb-4" style="display:none">
                @csrf
                <input name="content" placeholder="اكتب ملاحظة داخلية للفريق..." class="input flex-1" required>
                <button type="submit" class="btn btn-primary btn-sm">{!! icon('save', 'h-3.5 w-3.5') !!}</button>
            </form>

            <div class="space-y-2">
                @forelse ($serviceRequest->internalNotes as $note)
                    <div class="p-3 rounded-xl bg-muted/40 text-sm">
                        <div class="flex items-center justify-between mb-1">
                            <div class="text-xs font-bold">{{ $note->author->name }} <span class="text-muted-foreground font-normal">— {{ dt($note->created_at, true) }}</span></div>
                            <form method="POST" action="{{ route('admin.requests.notes.destroy', $note) }}" onsubmit="return confirm('حذف الملاحظة؟')">
                                @csrf
                                @method('DELETE')
                                <button class="text-destructive hover:opacity-70 cursor-pointer">{!! icon('trash', 'h-3.5 w-3.5') !!}</button>
                            </form>
                        </div>
                        <p class="text-muted-foreground leading-relaxed">{{ $note->content }}</p>
                    </div>
                @empty
                    <p class="text-xs text-muted-foreground text-center py-3">مفيش ملاحظات داخلية</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- العمود الجانبي: الإدارة --}}
    <div class="space-y-4">
        {{-- تعديل الطلب --}}
        <div class="card p-5">
            <h2 class="font-bold mb-4 flex items-center gap-2">{!! icon('edit', 'h-4 w-4 text-primary') !!} تحديث الطلب</h2>
            <form method="POST" action="{{ route('admin.requests.update', $serviceRequest) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="label">الحالة</label>
                    <select name="status" class="input" required>
                        @foreach (\App\Models\ServiceRequest::STATUSES as $status)
                            <option value="{{ $status }}" {{ old('status', $serviceRequest->status) === $status ? 'selected' : '' }}>{{ \App\Models\ServiceRequest::STATUS_LABELS[$status] }}</option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-muted-foreground mt-1">اختيار «مرتجع» يسجّل إرجاع بمهلة جديدة + سبب — «تسليم» يحصّل الأجر كاملاً</p>
                </div>

                {{-- سبب الإرجاع — يظهر فقط لو الحالة RETURNED --}}
                <div>
                    <label class="label">سبب الإرجاع (للحالة مرتجع)</label>
                    <textarea name="return_reason" rows="2" class="input" placeholder="مثال: العطل رجع تاني بعد التسليم...">{{ old('return_reason', $serviceRequest->return_reason) }}</textarea>
                </div>

                {{-- وضع الطلب — بيحدد مهلة التسليم --}}
                <div>
                    <label class="label">وضع الطلب (بيحدد مهلة التسليم)</label>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach (['normal' => 'عادي', 'urgent' => 'مستعجل', 'emergency' => 'طوارئ'] as $uKey => $uLabel)
                            <label class="flex items-center justify-center rounded-xl border-2 px-2 py-2 text-xs font-extrabold cursor-pointer transition-all
                                   {{ old('urgency', $serviceRequest->urgency) === $uKey ? 'border-primary bg-primary/5 text-primary' : 'border-border text-muted-foreground hover:border-primary/40' }}">
                                <input type="radio" name="urgency" value="{{ $uKey }}" class="sr-only" {{ old('urgency', $serviceRequest->urgency) === $uKey ? 'checked' : '' }}>
                                {{ $uLabel }}
                            </label>
                        @endforeach
                    </div>
                    <p class="text-[10px] text-muted-foreground mt-1">طوارئ 24 ساعة · مستعجل 48 · عادي 7 أيام</p>
                </div>

                <div>
                    <label class="label">السعر (ج.م) — الأجر الكامل اللي بيديه العميل عند التسليم</label>
                    <input name="price" type="number" step="0.01" min="0" value="{{ old('price', $serviceRequest->price) }}" class="input" dir="ltr">
                    <p class="text-[10px] text-muted-foreground mt-1">العملاء بيدفعوا الأجر كاملاً عند التسليم — مفيش مدفوع جزئي</p>
                </div>

                <div>
                    <label class="label">الضمان (شهور)</label>
                    <select name="warranty_months" class="input">
                        @foreach ([0, 1, 3, 6, 12, 24] as $months)
                            <option value="{{ $months }}" {{ old('warranty_months', $serviceRequest->warranty_months) === $months ? 'selected' : '' }}>{{ $months === 0 ? 'بدون ضمان' : $months.' شهر' }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- طريقة الدفع — الإكمال = تم التحصيل --}}
                <div>
                    <label class="label">طريقة الدفع (كاش / تحويل)</label>
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
                    <p class="text-[10px] text-muted-foreground mt-1">التسليم مع الدفع = تحصيل كامل = من إيردات اليوم</p>
                </div>

                <div>
                    <label class="label">ملاحظة تظهر للعميل</label>
                    <textarea name="admin_notes" rows="2" class="input">{{ old('admin_notes', $serviceRequest->admin_notes) }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary w-full">{!! icon('save', 'h-4 w-4') !!} حفظ التحديث</button>
            </form>

            @if ($serviceRequest->status === 'COMPLETED' && $serviceRequest->completed_at)
                <div class="mt-3 p-3 rounded-xl bg-green-50 dark:bg-green-950/40 border border-green-200 dark:border-green-900 text-xs text-green-700 dark:text-green-300">
                    تم التسليم في {{ dt($serviceRequest->completed_at, true) }}
                    @if ($serviceRequest->hasWarranty()) — الضمان حتى {{ dt($serviceRequest->warranty_end_date) }} @endif
                </div>
            @endif
        </div>

        {{-- تعيين فني --}}
        <div class="card p-5">
            <h2 class="font-bold mb-4 flex items-center gap-2">{!! icon('hard-hat', 'h-4 w-4 text-primary') !!} تعيين فني</h2>
            <form method="POST" action="{{ route('admin.requests.assign', $serviceRequest) }}" class="space-y-3">
                @csrf
                <select name="technician_id" class="input">
                    <option value="">— بدون فني (إلغاء التعيين) —</option>
                    @foreach ($technicians as $t)
                        <option value="{{ $t->id }}" {{ $serviceRequest->assigned_technician_id === $t->id ? 'selected' : '' }}>
                            {{ $t->name }}{{ $t->specialty ? ' ('.$t->specialty.')' : '' }}
                        </option>
                    @endforeach
                </select>
                <p class="text-[10px] text-muted-foreground">التعيين بيثبت الطلب تلقائياً ويبعت إشعار للفني والعميل — اختار «بدون فني» لإلغاء التعيين</p>
                <button type="submit" class="btn btn-outline w-full">{!! icon('check', 'h-4 w-4') !!} {{ $serviceRequest->assigned_technician_id ? 'تحديث التعيين' : 'تعيين' }}</button>
            </form>
        </div>

        {{-- التقييم --}}
        @if ($serviceRequest->review)
            <div class="card p-5">
                <h2 class="font-bold mb-3 flex items-center gap-2">{!! icon('star', 'h-4 w-4 text-primary') !!} تقييم العميل</h2>
                <div class="flex items-center justify-between mb-2">
                    <div class="flex gap-0.5 text-amber-500">
                        @for ($i = 1; $i <= 5; $i++)
                            {!! icon('star', 'h-4 w-4'.($i <= $serviceRequest->review->rating ? ' fill-amber-500 text-amber-500' : '')) !!}
                        @endfor
                    </div>
                    <span class="badge {{ $serviceRequest->review->statusColor() }}">{{ $serviceRequest->review->statusLabel() }}</span>
                </div>
                <p class="text-xs text-muted-foreground leading-relaxed">{{ $serviceRequest->review->comment }}</p>
                @if ($serviceRequest->review->admin_reply)
                    <div class="mt-2 p-2.5 rounded-lg bg-primary/5 text-xs">{{ $serviceRequest->review->admin_reply }}</div>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
