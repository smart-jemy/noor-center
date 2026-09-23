<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\LegacyImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * استعادة البيانات من مشروع Next.js القديم — للأدمن فقط
 * الرفع → الفحص والمعاينة → التنفيذ (دمج ذكي أو استبدال كامل)
 */
class RestoreController extends Controller
{
    /** مسار الملف المؤقت المرفوع داخل storage/app */
    private const DISK = 'local';

    private const NAME = 'legacy-import/custom.db';

    public function index()
    {
        // معاينة محفوظة من رفع سابق (نفس الجلسة) + ملخص آخر استيراد
        $preview = session('legacy_preview');
        $result = session('legacy_result');

        return view('admin.restore', compact('preview', 'result'));
    }

    /** رفع الملف + فحصه وعرض المعاينة قبل التنفيذ */
    public function upload(Request $request)
    {
        $request->validate([
            'db_file' => 'required|file|max:30720', // حتى 30MB
        ], [
            'db_file.required' => 'اختار ملف قاعدة البيانات (custom.db)',
            'db_file.max' => 'حجم الملف أكبر من 30MB',
        ]);

        $file = $request->file('db_file');

        Storage::disk(self::DISK)->makeDirectory('legacy-import');
        $file->storeAs('legacy-import', 'custom.db', self::DISK);

        $path = Storage::disk(self::DISK)->path(self::NAME);
        $preview = LegacyImport::inspect($path);

        if (! $preview['valid']) {
            Storage::disk(self::DISK)->delete(self::NAME);

            return back()->with('error', $preview['error']);
        }

        // الملخص الحالي في النظام للمقارنة
        $preview['current'] = [
            'users' => \App\Models\User::count(),
            'requests' => \App\Models\ServiceRequest::count(),
            'inventory' => \App\Models\InventoryItem::count(),
        ];

        return back()->with('legacy_preview', $preview);
    }

    /** تنفيذ الاستعادة (دمج أو استبدال) */
    public function run(Request $request)
    {
        $data = $request->validate([
            'mode' => 'required|in:merge,replace',
            'with_settings' => 'nullable',
            'confirm' => 'nullable|string',
        ], [
            'mode.required' => 'اختار طريقة الاستعادة',
        ]);

        $path = Storage::disk(self::DISK)->path(self::NAME);

        if (! is_file($path)) {
            return redirect()->route('admin.restore')->with('error', 'مفيش ملف مرفوع — ارفع custom.db الأول');
        }

        // تأكيد الاستبدال الكامل بكتابة كلمة «استعادة» — حماية من المسح الغير مقصود
        if ($data['mode'] === 'replace' && trim((string) $data['confirm']) !== 'استعادة') {
            return back()->with('error', 'للاستبدال الكامل اكتب كلمة «استعادة» في خانة التأكيد بالظبط');
        }

        try {
            $summary = LegacyImport::run(
                $path,
                $data['mode'],
                $request->boolean('with_settings'),
                $request->user()->id,
            );
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('admin.restore')
                ->with('error', 'فشلت الاستعادة: '.$e->getMessage().' — البيانات الحالية سليمة (كل العملية داخل ترانزاكشن)');
        }

        ActivityLog::record(
            $request->user()->id,
            'DATA_RESTORED',
            'استعادة بيانات من مشروع Next.js القديم ('.($data['mode'] === 'replace' ? 'استبدال كامل' : 'دمج ذكي').')'
        );

        Storage::disk(self::DISK)->delete(self::NAME);

        return redirect()->route('admin.restore')
            ->with('legacy_result', ['mode' => $data['mode'], 'summary' => $summary])
            ->with('success', 'تمت الاستعادة بنجاح — راجع الملخص تحت');
    }

    /** إلغاء الملف المرفوع */
    public function cancel()
    {
        Storage::disk(self::DISK)->delete(self::NAME);

        return redirect()->route('admin.restore')->with('success', 'تم إلغاء الملف المرفوع');
    }
}
