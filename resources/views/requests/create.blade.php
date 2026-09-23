@extends('layouts.app')
@section('title', 'اطلب صيانة')

@section('content')
<div class="container mx-auto max-w-3xl px-4 sm:px-6 py-8" x-data="requestForm()">
    <div class="mb-8 text-center">
        <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-xl bg-primary/10 text-primary">
            {!! icon('wrench', 'h-7 w-7') !!}
        </div>
        <h1 class="text-2xl md:text-3xl font-extrabold mb-1">اطلب صيانة</h1>
        <p class="text-muted-foreground text-sm">املا البيانات وهنتواصل معاك للتأكيد — والفني يوصلك في المعاد</p>
    </div>

    <form method="POST" action="{{ route('requests.store') }}" enctype="multipart/form-data" class="card p-6 md:p-8 space-y-6">
        @csrf

        {{-- اختيار نوع الجهاز --}}
        <div>
            <label class="label">نوع الجهاز <span class="text-destructive">*</span></label>
            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
                @foreach ($deviceTypes as $dt)
                    <label class="relative cursor-pointer">
                        <input type="radio" name="device_type" value="{{ $dt->name }}" class="peer sr-only" @change="deviceType = '{{ $dt->name }}'" {{ old('device_type') === $dt->name ? 'checked' : '' }}>
                        <div class="aspect-square rounded-xl border-2 border-border bg-card flex flex-col items-center justify-center gap-1.5 transition-all peer-checked:border-primary peer-checked:bg-primary/5 hover:border-primary/40">
                            {!! device_icon($dt->name, 'h-6 w-6 text-primary') !!}
                            <span class="text-[10px] font-bold">{{ $dt->name }}</span>
                        </div>
                    </label>
                @endforeach
            </div>
            @error('device_type')<p class="text-destructive text-xs mt-1.5">{{ $message }}</p>@enderror
        </div>

        {{-- العطل + الماركة --}}
        <div class="grid sm:grid-cols-3 gap-4">
            <div class="sm:col-span-1">
                <label class="label" for="brand">الماركة <span class="text-muted-foreground font-normal">(اختياري)</span></label>
                <input id="brand" name="brand" value="{{ old('brand') }}" placeholder="مثال: شارب، LG" class="input">
            </div>
            <div class="sm:col-span-2">
                <label class="label" for="issue_description">وصف العطل <span class="text-destructive">*</span></label>
                <textarea id="issue_description" name="issue_description" rows="3" placeholder="اكتب اللي بيحصل في الجهاز... مثال: التكييف بيرجع هواء مش بارد"
                          class="input {{ $errors->has('issue_description') ? 'input-error' : '' }}">{{ old('issue_description') }}</textarea>
                @error('issue_description')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- الملحقات (لو النوع فيه ملحقات) --}}
        <template x-if="accessories.length > 0">
            <div>
                <label class="label">الملحقات الموجودة مع الجهاز <span class="text-muted-foreground font-normal">(يساعد الفني يجيب قطع الغيار الصح)</span></label>
                <div class="flex flex-wrap gap-2">
                    <template x-for="acc in accessories" :key="acc">
                        <label class="cursor-pointer">
                            <input type="checkbox" name="accessories[]" :value="acc" class="peer sr-only">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border-2 border-border bg-card text-xs font-bold transition-all peer-checked:border-primary peer-checked:bg-primary/5">
                                {!! icon('check', 'h-3.5 w-3.5') !!}
                                <span x-text="acc"></span>
                            </span>
                        </label>
                    </template>
                </div>
            </div>
        </template>

        {{-- المنطقة والعنوان (اختياري) --}}
        <div>
            <label class="label">المنطقة <span class="text-muted-foreground font-normal">(اختياري)</span></label>
            <div class="grid grid-cols-3 gap-2">
                @foreach (\App\Models\ServiceRequest::AREAS as $key => $label)
                    <label class="relative cursor-pointer">
                        <input type="radio" name="area" value="{{ $key }}" class="peer sr-only" {{ old('area') === $key ? 'checked' : '' }}>
                        <div class="rounded-xl border-2 border-border bg-card p-3 flex items-center gap-2 transition-all peer-checked:border-primary peer-checked:bg-primary/5">
                            {!! icon('map-pin', 'h-4 w-4 text-primary') !!}
                            <span class="text-xs font-bold">{{ $label }}</span>
                        </div>
                    </label>
                @endforeach
                <label class="relative cursor-pointer">
                    <input type="radio" name="area" value="" class="peer sr-only" {{ old('area') === '' ? 'checked' : '' }}>
                    <div class="rounded-xl border-2 border-border bg-card p-3 flex items-center gap-2 transition-all peer-checked:border-primary peer-checked:bg-primary/5">
                        {!! icon('x', 'h-4 w-4 text-muted-foreground') !!}
                        <span class="text-xs font-bold">بدون منطقة</span>
                    </div>
                </label>
            </div>
            @error('area')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="label" for="address">العنوان التفصيلي <span class="text-muted-foreground font-normal">(اختياري)</span></label>
                <input id="address" name="address" value="{{ old('address') }}" placeholder="اسم الشارع / العمارة / الدور / رقم الشقة أو علامة مميزة"
                       class="input {{ $errors->has('address') ? 'input-error' : '' }}">
                @error('address')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- وضع الطلب: عادي / مستعجل / طوارئ — بيحدد مهلة التسليم --}}
        <div>
            <label class="label">مستوى الاستعجال <span class="text-muted-foreground font-normal">(بيحدد ميعاد تسليم الجهاز)</span></label>
            <div class="grid grid-cols-3 gap-2">
                @foreach (['normal' => ['عادي', 'clock', 'أقصى مهلة 96 ساعة'], 'urgent' => ['مستعجل', 'zap', 'أقصى مهلة 48 ساعة'], 'emergency' => ['طوارئ', 'alert-triangle', 'أقصى مهلة 24 ساعة']] as $uKey => [$uLabel, $uIcon, $uHint])
                    <label class="relative cursor-pointer">
                        <input type="radio" name="urgency" value="{{ $uKey }}" class="peer sr-only" {{ old('urgency', 'normal') === $uKey ? 'checked' : '' }}>
                        <div class="rounded-xl border-2 border-border bg-card p-3 text-center transition-all peer-checked:border-primary peer-checked:bg-primary/5">
                            {!! icon($uIcon, 'h-4 w-4 mx-auto text-primary') !!}
                            <div class="text-xs font-extrabold mt-1">{{ $uLabel }}</div>
                            <div class="text-[9px] text-muted-foreground mt-0.5">{{ $uHint }}</div>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- الهاتف --}}
        <div>
            <label class="label" for="phone">رقم التواصل <span class="text-destructive">*</span></label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone', auth()->user()->phone) }}" dir="ltr" style="text-align: right"
                   placeholder="01xxxxxxxxx" class="input {{ $errors->has('phone') ? 'input-error' : '' }}">
            @error('phone')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- الميعاد — اختياري بالكامل --}}
        <div class="space-y-3">
            <label class="label">الميعاد المفضل <span class="text-muted-foreground font-normal">(اختياري — لو مش محدد هنتصل بيك)</span></label>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <input id="preferred_date" name="preferred_date" type="date" min="{{ now()->toDateString() }}"
                           value="{{ old('preferred_date') }}" class="input {{ $errors->has('preferred_date') ? 'input-error' : '' }}">
                    @error('preferred_date')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <select name="preferred_time" class="input">
                        <option value="">بدون فترة محددة</option>
                        @foreach (\App\Models\ServiceRequest::TIME_SLOTS as $key => $label)
                            <option value="{{ $key }}" {{ old('preferred_time') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('preferred_time')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- الصور --}}
        <div x-data="photoPicker()">
            <label class="label">صور للجهاز أو العطل <span class="text-muted-foreground font-normal">(اختياري — حتى 3 صور)</span></label>
            {{-- الحقل الحقيقي داخل الفورم --}}
            <input type="file" id="photos-input" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="sr-only" tabindex="-1">
            <div class="flex flex-wrap gap-3 items-start">
                <div class="flex flex-wrap gap-3">
                    <template x-for="(photo, i) in previews" :key="i">
                        <div class="relative w-24 h-24 rounded-xl overflow-hidden border-2 border-border">
                            <img :src="photo" class="w-full h-full object-cover" :alt="'صورة ' + (i + 1)">
                            <button type="button" @click="remove(i)" class="absolute top-1 left-1 flex h-6 w-6 items-center justify-center rounded-full bg-black/60 text-white cursor-pointer" aria-label="حذف الصورة">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="h-3 w-3"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                            </button>
                        </div>
                    </template>
                </div>
                <label x-show="previews.length < 3" class="w-24 h-24 rounded-xl border-2 border-dashed border-border bg-muted/30 flex flex-col items-center justify-center gap-1 cursor-pointer hover:border-primary/50 hover:bg-primary/5 transition-all">
                    {!! icon('upload', 'h-5 w-5 text-muted-foreground') !!}
                    <span class="text-[10px] font-bold text-muted-foreground">أضف صورة</span>
                    <input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" multiple @change="add($event)">
                </label>
            </div>
            @error('photos')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
            @error('photos.*')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- ملخص المزايا --}}
        <div class="p-4 rounded-xl bg-primary/5 border border-primary/10 space-y-2">
            <div class="flex items-center gap-2 text-xs text-muted-foreground">{!! icon('check-circle', 'h-4 w-4 text-primary shrink-0') !!} هنتواصل معاك للتأكيد قبل المعاد</div>
            <div class="flex items-center gap-2 text-xs text-muted-foreground">{!! icon('wallet', 'h-4 w-4 text-primary shrink-0') !!} الدفع كاش بعد الانتهاء — من غير أي مقدم</div>
            <div class="flex items-center gap-2 text-xs text-muted-foreground">{!! icon('shield-check', 'h-4 w-4 text-primary shrink-0') !!} ضمان على الإصلاح حسب نوع العطل</div>
        </div>

        <button type="submit" class="btn btn-primary w-full btn-lg text-base">
            {!! icon('wrench', 'h-5 w-5') !!}
            إرسال طلب الصيانة
        </button>
    </form>
</div>

<script>
function requestForm() {
    const accessoriesMap = {{ \Illuminate\Support\Js::from($deviceTypes->mapWithKeys(fn ($dt) => [$dt->name => $dt->accessories ?? []])) }};
    return {
        deviceType: document.querySelector('input[name="device_type"]:checked')?.value || '',
        get accessories() {
            return accessoriesMap[this.deviceType] || [];
        }
    };
}

function photoPicker() {
    return {
        previews: [],
        files: [],
        sync() {
            const dt = new DataTransfer();
            this.files.forEach(f => dt.items.add(f));
            const input = document.getElementById('photos-input');
            if (input) input.files = dt.files;
        },
        add(event) {
            for (const file of Array.from(event.target.files)) {
                if (this.files.length >= 3) break;
                if (! file.type.startsWith('image/')) continue;
                this.files.push(file);
                this.previews.push(URL.createObjectURL(file));
            }
            this.sync();
            event.target.value = '';
        },
        remove(i) {
            URL.revokeObjectURL(this.previews[i]);
            this.previews.splice(i, 1);
            this.files.splice(i, 1);
            this.sync();
        }
    };
}
</script>
@endsection
