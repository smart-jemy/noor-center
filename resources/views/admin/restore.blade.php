@extends('admin.layout')
@section('title', 'استعادة البيانات — لوحة التحكم')
@section('admin_title', 'استعادة البيانات')
@section('admin_subtitle', '')

@section('admin_actions')
    <a href="{{ route('admin.backup') }}" class="btn btn-outline btn-sm">{!! icon('download', 'h-3.5 w-3.5') !!} نسخة احتياطية</a>
@endsection

@section('admin_content')
<div class="max-w-4xl mx-auto space-y-4">

    {{-- رسائل النظام --}}
    @if (session('error'))
        <div class="card p-4 border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-950/40">
            <p class="text-sm font-bold text-red-700 dark:text-red-300 flex items-center gap-2">{!! icon('alert-triangle', 'h-4 w-4') !!} {{ session('error') }}</p>
        </div>
    @endif
    @if (session('success'))
        <div class="card p-4 border-green-200 dark:border-green-900 bg-green-50 dark:bg-green-950/40">
            <p class="text-sm font-bold text-green-700 dark:text-green-300 flex items-center gap-2">{!! icon('check-circle', 'h-4 w-4') !!} {{ session('success') }}</p>
        </div>
    @endif

    {{-- ===== الخطوة 1: رفع الملف ===== --}}
    <div class="card p-5 md:p-6">
        <h2 class="font-bold mb-1.5 flex items-center gap-2">
            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary/10 text-primary text-sm font-extrabold">1</span>
            ملف 
        </h2>
        <p class="text-xs text-muted-foreground mb-4 leading-relaxed">
            <code class="px-1.5 py-0.5 rounded bg-muted text-[11px]" dir="ltr"></code>
            
        </p>
        <form method="POST" action="{{ route('admin.restore.upload') }}" enctype="multipart/form-data"
              class="flex flex-wrap items-center gap-3" onsubmit="return validateDbFile(this)">
            @csrf
            <input type="file" name="db_file" id="db-file" accept=".db,.sqlite,.sqlite3"
                   class="block flex-1 min-w-[220px] text-sm text-muted-foreground file:ml-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-primary file:text-primary-foreground hover:file:bg-primary/90 cursor-pointer">
            <button type="submit" class="btn btn-primary">{!! icon('upload', 'h-4 w-4') !!} فحص الملف</button>
        </form>
    </div>

    {{-- ===== الخطوة 2: معاينة الملف المرفوع ===== --}}
    @if (! empty($preview) && ($preview['valid'] ?? false))
        <div class="card p-5 md:p-6 border-primary/30">
            <div class="flex items-center justify-between flex-wrap gap-2 mb-4">
                <h2 class="font-bold flex items-center gap-2">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary/10 text-primary text-sm font-extrabold">2</span>
                    Working
                </h2>
                <form method="POST" action="{{ route('admin.restore.cancel') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-sm">{!! icon('x', 'h-3.5 w-3.5') !!} إلغاء الملف</button>
                </form>
            </div>

            <div class="flex flex-wrap gap-2 text-[11px] text-muted-foreground mb-4">
                <span class="badge bg-muted">{{ $preview['file']['size'] ? round($preview['file']['size'] / 1024).' KB' : '—' }}</span>
                <span class="badge bg-muted">آخر تعديل: {{ $preview['file']['modified'] }}</span>
                @foreach ($preview['sample'] as $s)
                    <span class="badge bg-blue-100 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-900">{{ $s['name'] }} — {{ $s['role'] }}</span>
                @endforeach
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 mb-5">
                @php
                    $cards = [
                        ['المستخدمون', $preview['counts']['User'], 'users'],
                        ['طلبات الصيانة', $preview['counts']['ServiceRequest'], 'requests'],
                        ['المخزون', $preview['counts']['InventoryItem'], 'inventory'],
                        ['الأقسام', $preview['counts']['Department'], 'departments'],
                        ['أنواع الأجهزة', $preview['counts']['DeviceType'], 'device'],
                        ['شركاء الصيانة', $preview['counts']['PartnerTechnician'], 'partners'],
                        ['صيانات الشركاء', $preview['counts']['PartnerRepair'], 'repairs'],
                        ['السجلات', $preview['counts']['ActivityLog'], 'logs'],
                    ];
                @endphp
                @foreach ($cards as $c)
                    <div class="p-3 rounded-xl bg-muted/40 text-center">
                        <div class="text-xl font-extrabold text-primary">{{ $c[1] }}</div>
                        <div class="text-[10px] text-muted-foreground mt-0.5">{{ $c[0] }}</div>
                    </div>
                @endforeach
            </div>

            <div class="p-3 rounded-xl bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900 text-xs text-blue-800 dark:text-blue-300 mb-5 leading-relaxed">
                النظام حالياً فيه: <b>{{ $preview['current']['users'] }}</b> مستخدم و<b>{{ $preview['current']['requests'] }}</b> طلب و<b>{{ $preview['current']['inventory'] }}</b> صنف مخزن.
                اختار طريقة الاستعادة المناسبة من تحت.
            </div>

            {{-- ===== الخطوة 3: اختيار طريقة الاستعادة ===== --}}
            <h2 class="font-bold mb-3 flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary/10 text-primary text-sm font-extrabold">3</span>
                اختار طريقة الاستعادة
            </h2>

            <form method="POST" action="{{ route('admin.restore.run') }}" x-data="{ mode: 'merge', confirmText: '' }" class="space-y-4">
                @csrf

                {{-- دمج ذكي --}}
                <label class="flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all"
                       :class="mode === 'merge' ? 'border-primary bg-primary/5' : 'border-border hover:border-primary/40'">
                    <input type="radio" name="mode" value="merge" x-model="mode" class="mt-1">
                    <div class="flex-1">
                        <div class="font-bold text-sm flex items-center gap-2">
                            {!! icon('git-merge', 'h-4 w-4 text-primary') !!} دمج ذكي <span class="badge bg-green-100 dark:bg-green-950/50 text-green-700 dark:text-green-300 border-green-200 dark:border-green-900 text-[9px]">آمن — مقترح</span>
                        </div>
                        <p class="text-xs text-muted-foreground mt-1 leading-relaxed">Not Working</p>
                    </div>
                </label>

                {{-- استبدال كامل --}}
                <label class="flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all"
                       :class="mode === 'replace' ? 'border-red-500 bg-red-50 dark:bg-red-950/30' : 'border-border hover:border-red-400/50'">
                    <input type="radio" name="mode" value="replace" x-model="mode" class="mt-1">
                    <div class="flex-1">
                        <div class="font-bold text-sm flex items-center gap-2 text-red-700 dark:text-red-300">
                            {!! icon('refresh-cw', 'h-4 w-4') !!} استبدال كامل (نسخة طبق الأصل من القديم)
                        </div>
                        <p class="text-xs text-muted-foreground mt-1 leading-relaxed">NOT AVILBLE</p>

                        <div x-show="mode === 'replace'" x-transition class="mt-3">
                            <label class="label text-red-700 dark:text-red-300">اكتب كلمة «استعادة» للتأكيد</label>
                            <input type="text" name="confirm" x-model="confirmText" placeholder="استعادة"
                                   class="input max-w-[200px] border-red-200 dark:border-red-900" dir="rtl" autocomplete="off">
                        </div>
                    </div>
                </label>

                {{-- الإعدادات اختيارية --}}
                <label class="flex items-center gap-2.5 text-sm cursor-pointer">
                    <input type="checkbox" name="with_settings" value="1" class="h-4 w-4">
                    <span>استيراد إعدادات الموقع القديمة كذلك (بيانات التواصل + نصوص الإيصال)</span>
                </label>

                <button type="submit" class="btn btn-primary w-full" :class="mode === 'replace' && confirmText !== 'استعادة' ? 'opacity-60' : ''">
                    {!! icon('database', 'h-4 w-4') !!}
                    <span x-text="mode === 'merge' ? 'بدء الدمج الذكي' : 'بدء الاستبدال الكامل'">بدء الدمج الذكي</span>
                </button>
            </form>
        </div>
    @endif

    {{-- ===== ملخص آخر استعادة ===== --}}
    @if (! empty($result))
        <div class="card p-5 md:p-6 border-green-300 dark:border-green-900">
            <h2 class="font-bold mb-4 flex items-center gap-2">
                {!! icon('check-circle', 'h-5 w-5 text-green-600') !!}
                نتيجة الاستعادة ({{ $result['mode'] === 'replace' ? 'استبدال كامل' : 'دمج ذكي' }})
            </h2>
            <div class="overflow-x-auto">
                <table class="table-noor min-w-[420px]">
                    <thead>
                        <tr><th>الجدول</th><th>أُضيف</th><th>اتحدّث</th><th>اتتخطى</th></tr>
                    </thead>
                    <tbody>
                        @php
                            $labels = [
                                'users' => 'المستخدمون', 'departments' => 'الأقسام', 'deviceTypes' => 'أنواع الأجهزة',
                                'requests' => 'طلبات الصيانة', 'inventory' => 'المخزون', 'partners' => 'شركاء الصيانة',
                                'repairs' => 'صيانات الشركاء', 'settlements' => 'تسويات الشركاء', 'expenses' => 'المصروفات',
                                'reviews' => 'التقييمات', 'notifications' => 'الإشعارات', 'activityLogs' => 'سجل النشاط',
                                'customerNotes' => 'ملاحظات العملاء', 'requestNotes' => 'ملاحظات الطلبات', 'usedParts' => 'القطع المستخدمة',
                            ];
                        @endphp
                        @foreach ($result['summary'] as $key => $s)
                            <tr>
                                <td class="font-bold">{{ $labels[$key] ?? $key }}</td>
                                <td><span class="badge bg-green-100 dark:bg-green-950/50 text-green-700 dark:text-green-300 border-green-200 dark:border-green-900">{{ $s['added'] }}</span></td>
                                <td><span class="badge bg-blue-100 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-900">{{ $s['updated'] }}</span></td>
                                <td><span class="badge bg-muted text-muted-foreground">{{ $s['skipped'] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-muted-foreground mt-3 leading-relaxed">  <b dir="ltr">123456</b>.
            </p>
        </div>
    @endif

    {{-- ملاحظة أمان --}}
    <div class="card p-4 bg-amber-50 dark:bg-amber-950/30 border-amber-200 dark:border-amber-900">
        <p class="text-xs text-amber-800 dark:text-amber-300 leading-relaxed flex items-start gap-2">
            {!! icon('shield', 'h-4 w-4 shrink-0 mt-0.5') !!}
            <span>sorry not avilable.</span>
        </p>
    </div>
</div>

<script>
    function validateDbFile(form) {
        const input = document.getElementById('db-file');
        if (!input.files.length) { alert('اختار ملف الأول'); return false; }
        return false;
    }
</script>
@endsection
