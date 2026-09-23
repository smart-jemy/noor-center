@extends('admin.layout')
@section('title', 'الإعدادات — لوحة التحكم')
@section('admin_title', 'إعدادات الموقع')
@section('admin_subtitle', 'بيانات التواصل، الإيصال، التذكير، والإشعارات')

@section('admin_content')
<form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-4">
    @csrf
    @method('PUT')

    {{-- بيانات التواصل --}}
    <div class="card p-5 md:p-6">
        <h2 class="font-bold mb-4 flex items-center gap-2">{!! icon('phone', 'h-4 w-4 text-primary') !!} بيانات التواصل (تظهر في الصفحة الرئيسية والفوتر)</h2>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="label">رقم الهاتف *</label>
                <input name="phone" value="{{ old('phone', $settings->phone) }}" class="input {{ $errors->has('phone') ? 'input-error' : '' }}" dir="ltr" required>
                @error('phone')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">رقم واتساب <span class="text-muted-foreground font-normal">(اختياري)</span></label>
                <input name="whatsapp_num" value="{{ old('whatsapp_num', $settings->whatsapp_num) }}" class="input" dir="ltr" placeholder="لو فاضي هيستخدم رقم الهاتف">
            </div>
            <div>
                <label class="label">البريد الإلكتروني *</label>
                <input name="email" type="email" value="{{ old('email', $settings->email) }}" class="input {{ $errors->has('email') ? 'input-error' : '' }}" dir="ltr" required>
                @error('email')<p class="text-destructive text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">ساعات العمل *</label>
                <input name="working_hours" value="{{ old('working_hours', $settings->working_hours) }}" class="input {{ $errors->has('working_hours') ? 'input-error' : '' }}" required>
            </div>
            <div class="sm:col-span-2">
                <label class="label">العنوان *</label>
                <input name="address" value="{{ old('address', $settings->address) }}" class="input {{ $errors->has('address') ? 'input-error' : '' }}" required>
            </div>
            <div>
                <label class="label">رابط فيسبوك</label>
                <input name="facebook_url" value="{{ old('facebook_url', $settings->facebook_url) }}" class="input" dir="ltr" placeholder="https://facebook.com/...">
            </div>
            <div>
                <label class="label">رابط إنستجرام</label>
                <input name="instagram_url" value="{{ old('instagram_url', $settings->instagram_url) }}" class="input" dir="ltr" placeholder="https://instagram.com/...">
            </div>
        </div>
    </div>

    {{-- إعدادات الإيصال --}}
    <div class="card p-5 md:p-6">
        <h2 class="font-bold mb-4 flex items-center gap-2">{!! icon('printer', 'h-4 w-4 text-primary') !!} إعدادات إيصال الاستلام</h2>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="label">عنوان الإيصال *</label>
                <input name="receipt_title" value="{{ old('receipt_title', $settings->receipt_title) }}" class="input" required>
            </div>
            <div>
                <label class="label">اسم المركز بالإيصال *</label>
                <input name="receipt_subtitle" value="{{ old('receipt_subtitle', $settings->receipt_subtitle) }}" class="input" required>
            </div>
            <div class="sm:col-span-2">
                <label class="label">ملاحظات الإيصال *</label>
                <textarea name="receipt_notes" rows="2" class="input" required>{{ old('receipt_notes', $settings->receipt_notes) }}</textarea>
                <p class="text-[10px] text-muted-foreground mt-1">تظهر فوق التوقيعات — مثلاً: مسؤولية المركز عن الجهاز بعد 14 يوم</p>
            </div>
            <div class="sm:col-span-2">
                <label class="label">تذييل الإيصال *</label>
                <input name="receipt_footer" value="{{ old('receipt_footer', $settings->receipt_footer) }}" class="input" required>
            </div>
        </div>
    </div>

    {{-- التذكير الدوري --}}
    <div class="card p-5 md:p-6">
        <h2 class="font-bold mb-4 flex items-center gap-2">{!! icon('timer', 'h-4 w-4 text-primary') !!} تذكير الصيانة الدورية</h2>
        <div class="space-y-4">
            <label class="flex items-center gap-3 cursor-pointer select-none p-3.5 rounded-xl border-2 transition-all {{ old('maintenance_reminder_enabled', $settings->maintenance_reminder_enabled) ? 'border-primary bg-primary/5' : 'border-border' }}">
                <input type="checkbox" name="maintenance_reminder_enabled" value="1" {{ old('maintenance_reminder_enabled', $settings->maintenance_reminder_enabled) ? 'checked' : '' }} class="accent-primary w-5 h-5">
                <div>
                    <div class="text-sm font-bold">تفعيل قائمة التذكير</div>
                    <div class="text-xs text-muted-foreground">تظهر قائمة بالعملاء اللي مضى على آخر صيانة لهم أكثر من المدة المحددة</div>
                </div>
            </label>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">التذكير كل (شهر)</label>
                    <select name="maintenance_reminder_months" class="input">
                        @foreach ([1, 2, 3, 6, 12, 18, 24] as $m)
                            <option value="{{ $m }}" {{ old('maintenance_reminder_months', $settings->maintenance_reminder_months) == $m ? 'selected' : '' }}>{{ $m }} شهر</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <a href="{{ route('admin.reminders') }}" class="btn btn-outline btn-sm w-full">{!! icon('timer', 'h-3.5 w-3.5') !!} عرض قائمة التذكير الحالية</a>
                </div>
            </div>
        </div>
    </div>

    {{-- إشعار الصوت --}}
    <div class="card p-5 md:p-6">
        <h2 class="font-bold mb-4 flex items-center gap-2">{!! icon('bell', 'h-4 w-4 text-primary') !!} إشعار صوتي للطلبات الجديدة</h2>
        <div class="space-y-4">
            <label class="flex items-center gap-3 cursor-pointer select-none p-3.5 rounded-xl border-2 transition-all {{ old('sound_notification_enabled', $settings->sound_notification_enabled) ? 'border-primary bg-primary/5' : 'border-border' }}">
                <input type="checkbox" name="sound_notification_enabled" value="1" {{ old('sound_notification_enabled', $settings->sound_notification_enabled) ? 'checked' : '' }} class="accent-primary w-5 h-5">
                <div>
                    <div class="text-sm font-bold">تفعيل الصوت</div>
                    <div class="text-xs text-muted-foreground">يشتغل عند دخول الأدمن لو فيه إشعارات غير مقروءة</div>
                </div>
            </label>
            <div>
                <label class="label">رابط ملف الصوت</label>
                <input name="sound_notification_url" value="{{ old('sound_notification_url', $settings->sound_notification_url) }}" class="input" dir="ltr" placeholder="/sounds/notification.wav">
                <p class="text-[10px] text-muted-foreground mt-1">فيه ملف جاهز: <span dir="ltr">/sounds/notification.wav</span></p>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-lg w-full">
        {!! icon('save', 'h-5 w-5') !!}
        حفظ كل الإعدادات
    </button>
</form>
@endsection
