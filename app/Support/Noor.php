<?php

namespace App\Support;

use App\Models\Department;
use App\Models\NoorNotification;
use App\Models\ServiceRequest;
use App\Models\Setting;
use App\Models\User;

class Noor
{
    /** رقم أوردر تسلسلي: NC 0001 2026 (يتجدد كل سنة) */
    public static function orderNumber(): string
    {
        $year = now()->format('Y');

        $last = ServiceRequest::where('order_number', 'like', "% {$year}")
            ->orderByDesc('order_number')
            ->value('order_number');

        $next = 1;
        if ($last) {
            $seq = (int) (explode(' ', $last)[1] ?? 0);
            if ($seq > 0) $next = $seq + 1;
        }

        return 'NC '.str_pad((string) $next, 4, '0', STR_PAD_LEFT)." {$year}";
    }

    /** توجيه الطلب تلقائياً للقسم المختص حسب نوع الجهاز */
    public static function routeDepartment(ServiceRequest $request): void
    {
        $type = mb_strtolower(trim($request->device_type));

        if ($type === '') return;

        foreach (Department::where('is_active', true)->get() as $dept) {
            foreach ((array) $dept->device_types as $dt) {
                $n = mb_strtolower(trim((string) $dt));
                if ($n !== '' && ($n === $type || str_contains($n, $type) || str_contains($type, $n))) {
                    $request->update(['department_id' => $dept->id]);
                    return;
                }
            }
        }
    }

    /** إشعار كل الأدمنز */
    public static function notifyAdmins(string $type, string $title, string $message, ?int $requestId = null): void
    {
        foreach (User::where('role', 'ADMIN')->pluck('id') as $adminId) {
            NoorNotification::create([
                'user_id' => $adminId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'request_id' => $requestId,
            ]);
        }
    }

    /** إشعار مستخدم معين */
    public static function notifyUser(int $userId, string $type, string $title, string $message, ?int $requestId = null): void
    {
        NoorNotification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'request_id' => $requestId,
        ]);
    }

    public static function settings(): Setting
    {
        return Setting::current();
    }

    /** تحديث حالة الطلب مع الأعمال المرتبطة (نقاط الولاء، الضمان، الإشعارات، طريقة الدفع) */
    public static function completeRequest(ServiceRequest $request, int $warrantyMonths = 0, ?string $paymentMethod = null): void
    {
        $update = [
            'status' => 'COMPLETED',
            'completed_at' => now(),
            'warranty_months' => $warrantyMonths,
            'warranty_end_date' => $warrantyMonths > 0 ? now()->addMonths($warrantyMonths)->toDateString() : null,
            // التحصيل من العملاء بكامل السعر دائماً — التسليم = تم تحصيل الأجر كاملاً
            'paid_amount' => (float) ($request->price ?? 0),
        ];

        // طريقة الدفع (كاش/تحويل) — التسليم = تم التحصيل = من إيردات اليوم
        if ($paymentMethod !== null && in_array($paymentMethod, ['cash', 'transfer'], true)) {
            $update['payment_method'] = $paymentMethod;
        }

        $request->update($update);

        // نقاط الولاء على المحصّل الفعلي — التحصيل كامل السعر
        $price = (float) ($request->price ?? 0);
        if ($price > 0) {
            $request->customer?->increment('loyalty_points', (int) floor($price));
            $request->customer?->increment('total_spent', $price);
        }

        self::notifyUser(
            $request->customer_id,
            'REQUEST_UPDATED',
            'تم التسليم',
            "طلبك {$request->order_number} جاهز للتسليم وتم تحصيل الأجر".($warrantyMonths > 0 ? " — ضمان {$warrantyMonths} شهر" : ''),
            $request->id
        );
    }

    /**
     * مرتجع للفني — العميل رجّع الجهاز بعد الإصلاح والشركة هتصلحه تاني
     * الطلب يرجع لقائمة الشغل النشط عند الفني بحالة RETURNED
     */
    public static function returnRequest(ServiceRequest $request, string $reason, int $byUserId): void
    {
        $request->update([
            'status' => 'RETURNED',
            'is_returned' => true,
            'returned_at' => now(),
            'return_reason' => $reason,
            'return_count' => $request->return_count + 1,
            // مهلة جديدة للإصلاح — نفس استعجال الطلب
            'expected_exit_at' => now()->addHours($request->slaHours()),
        ]);

        // إشعار الفني المخصص
        if ($request->assigned_technician_id) {
            self::notifyUser(
                (int) $request->assigned_technician_id,
                'REQUEST_RETURNED',
                'مرتجع للفني',
                "الطلب {$request->order_number} رجع من العميل: {$reason}",
                $request->id
            );
        }

        // إشعار الأدمنز
        self::notifyAdmins(
            'REQUEST_RETURNED',
            'مرتجع للفني',
            "الطلب {$request->order_number} ({$request->device_type}) رجع من العميل — سبب: {$reason}",
            $request->id
        );
    }

    /**
     * الرجوع من حالة "مكتمل" — إلغاء الضمان وسحب نقاط الولاء اللي اتمنحت (زي الأصل)
     */
    public static function revertCompletion(ServiceRequest $request): void
    {
        $request->update([
            'completed_at' => null,
            'warranty_end_date' => null,
        ]);

        $price = (float) ($request->price ?? 0);
        if ($price > 0) {
            $customer = $request->customer;
            if ($customer) {
                $customer->decrement('loyalty_points', (int) floor($price));
                $customer->decrement('total_spent', $price);
                // ضمان عدم النزول تحت الصفر
                $customer->update([
                    'loyalty_points' => max(0, $customer->fresh()->loyalty_points),
                    'total_spent' => max(0, $customer->fresh()->total_spent),
                ]);
            }
        }
    }
}
