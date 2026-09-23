@extends('admin.layout')
@section('title', 'تذكير الصيانة — لوحة التحكم')
@section('admin_title', 'تذكير الصيانة الدورية')
@section('admin_subtitle', 'عملاء مضى على آخر صيانة لهم أكثر من المدة المحددة')

@section('admin_actions')
    <a href="{{ route('admin.settings.index') }}" class="btn btn-outline btn-sm">{!! icon('settings', 'h-3.5 w-3.5') !!} إعدادات التذكير</a>
@endsection

@section('admin_content')
{{-- بطاقة إعدادات التذكير — زي الأصل: تفعيل + كل كام شهر + حفظ --}}
<div class="card border-primary/30 mb-4" x-data="{ enabled: {{ $enabled ? 'true' : 'false' }} }">
    <div class="p-5 space-y-4">
        <h2 class="font-bold flex items-center gap-2">{!! icon('timer', 'h-5 w-5 text-primary') !!} إعدادات التذكير الدوري</h2>
        <form method="POST" action="{{ route('admin.reminders.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="flex items-center gap-3">
                <button type="button" @click="enabled = !enabled" name="maintenance_reminder_enabled_toggle"
                        class="relative h-6 w-11 rounded-full transition-colors shrink-0" :class="enabled ? 'bg-primary' : 'bg-muted-foreground/30'">
                    <div class="absolute top-0.5 h-5 w-5 rounded-full bg-white transition-all" :class="enabled ? 'left-0.5' : 'right-0.5'"></div>
                </button>
                <input type="hidden" name="maintenance_reminder_enabled" :value="enabled ? 1 : 0">
                <div>
                    <p class="text-sm font-medium">تفعيل التذكير الدوري</p>
                    <p class="text-xs text-muted-foreground">تنبيهك بالعملاء اللي محتاجين صيانة دورية</p>
                </div>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <label class="label mb-0 whitespace-nowrap">كل</label>
                <input type="number" name="maintenance_reminder_months" min="1" max="24" value="{{ $months }}" class="input w-20" required>
                <span class="text-sm">شهور</span>
                <button type="submit" class="btn btn-primary btn-sm mr-auto">{!! icon('save', 'h-4 w-4') !!} حفظ الإعدادات</button>
            </div>
        </form>
    </div>
</div>

{{-- ملخص سريع — زي الأصل --}}
<div class="grid grid-cols-3 gap-3 mb-4">
    <div class="card p-4 border-amber-200/60 bg-amber-50/30 dark:bg-amber-950/20 text-center">
        <p class="text-xs text-muted-foreground">عملاء محتاجين تذكير</p>
        <p class="text-2xl font-extrabold mt-1 text-amber-700 dark:text-amber-400">{{ $enabled ? $customers->count() : 0 }}</p>
    </div>
    <div class="card p-4 text-center">
        <p class="text-xs text-muted-foreground">دورة التذكير</p>
        <p class="text-2xl font-extrabold mt-1 text-primary">{{ $months }} شهر</p>
    </div>
    <div class="card p-4 text-center">
        <p class="text-xs text-muted-foreground">حالة التذكير</p>
        <p class="text-2xl font-extrabold mt-1 {{ $enabled ? 'text-green-700 dark:text-green-400' : 'text-muted-foreground' }}">{{ $enabled ? 'مفعل' : 'معطل' }}</p>
    </div>
</div>

@if (! $enabled)
    <div class="card p-8 text-center">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-xl bg-muted text-muted-foreground">
            {!! icon('timer', 'h-7 w-7') !!}
        </div>
        <h3 class="font-bold mb-1">التذكير الدوري معطل حالياً</h3>
        <p class="text-muted-foreground text-sm">فعّله من بطاقة الإعدادات فوق وحدد المدة (كل كام شهر)</p>
    </div>
@else
    <div class="card p-4 mb-4 bg-primary/5 border-primary/20 flex items-center justify-between flex-wrap gap-2">
        <div class="flex items-center gap-2 text-sm">
            {!! icon('timer', 'h-4 w-4 text-primary') !!}
            <b>{{ $customers->count() }}</b> عميل مضى على آخر صيانة له أكتر من <b>{{ $months }} شهر</b>
        </div>
        <div class="text-xs text-muted-foreground">كل جنيه صيانة = نقطة ولاء — دي فرصة لخدمة متكررة</div>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-noor min-w-[680px]">
                <thead>
                    <tr>
                        <th>العميل</th>
                        <th>آخر صيانة</th>
                        <th>مضى</th>
                        <th>عدد الصيانات</th>
                        <th>إجمالي إنفاقه</th>
                        <th>اتصال</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $c)
                        @php $lastRequest = $c->requests->first(); $monthsAgo = (int) floor($lastRequest->completed_at->diffInMonths(now())); @endphp
                        <tr>
                            <td>
                                <a href="{{ route('admin.customers.show', $c) }}" class="flex items-center gap-2 hover:text-primary">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-primary to-primary/70 text-primary-foreground text-xs font-bold shrink-0">{{ mb_substr($c->name, 0, 1) }}</div>
                                    <div>
                                        <div class="font-bold">{{ $c->name }}</div>
                                        <div class="text-[10px] text-muted-foreground" dir="ltr">{{ $c->phone }}</div>
                                    </div>
                                </a>
                            </td>
                            <td class="text-xs">{{ dt($lastRequest->completed_at) }}<div class="text-muted-foreground">{{ $lastRequest->device_type }}</div></td>
                            <td><span class="badge {{ $monthsAgo > $months * 2 ? 'bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-300 border-red-200 dark:border-red-900' : 'bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-900' }}">{{ $monthsAgo }} شهر</span></td>
                            <td>{{ $c->requests_count }}</td>
                            <td class="text-primary font-bold">{{ money($c->total_spent) }}</td>
                            <td>
                                <a href="tel:{{ $c->phone }}" class="btn btn-outline btn-sm">{!! icon('phone', 'h-3.5 w-3.5') !!} اتصل</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted-foreground py-8">مفيش عملاء محتاجين تذكير — كلهم صيانوا قريب 👌</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
