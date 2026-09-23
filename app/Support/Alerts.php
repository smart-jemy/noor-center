<?php

namespace App\Support;

use App\Models\InventoryItem;
use App\Models\ServiceRequest;

/**
 * محرك الإنذارات على الطلبات — قلب غرفة العمليات الحية
 *
 * الإنذارات تُحسب لحظياً من قاعدة البيانات:
 * - طوارئ تجاوزت المهلة (24 ساعة)      → إنذار أحمر حرج
 * - مستعجل تجاوز المهلة (48 ساعة)      → إنذار برتقالي عالي
 * - عادي تجاوز المهلة (7 أيام)         → إنذار أصفر متوسط
 * - مرتجع للفني ينتظر إعادة الصيانة     → إنذار برتقالي عالي
 * - طلبات كشف/تواصل بدون فني          → إنذار أزرق متوسط
 * - أصناف مخزون وصلت حد التنبيه         → إنذار رمادي منخفض
 *
 * مستوحى من شريط "الإنذارات النشطة" في غرف عمليات SCADA
 */
class Alerts
{
    /** كل الإنذارات النشطة (مرتبة بالخطورة) */
    public static function active(): array
    {
        $alerts = [];

        // 1) طوارئ متأخرة — أحرج إنذار
        $emergencyOverdue = ServiceRequest::where('urgency', 'emergency')
            ->whereNotIn('status', ['COMPLETED', 'CANCELLED'])
            ->where('expected_exit_at', '<', now())
            ->count();
        if ($emergencyOverdue > 0) {
            $alerts[] = self::item('emergency_overdue', 'critical', 'طوارئ تجاوزت المهلة', $emergencyOverdue,
                'طلبات طوارئ عدّى عليها ميعاد الخروج (24 ساعة) ولسه مش مكتملة', 'فلتر الطوارئ المتأخرة');
        }

        // 2) مستعجل متأخرة
        $urgentOverdue = ServiceRequest::where('urgency', 'urgent')
            ->whereNotIn('status', ['COMPLETED', 'CANCELLED'])
            ->where('expected_exit_at', '<', now())
            ->count();
        if ($urgentOverdue > 0) {
            $alerts[] = self::item('urgent_overdue', 'high', 'مستعجل تجاوز المهلة', $urgentOverdue,
                'طلبات مستعجلة عدّى عليها ميعاد الخروج (48 ساعة)', 'فلتر المستعجل المتأخر');
        }

        // 3) مرتجع للفني — ينتظر إعادة صيانة
        $returned = ServiceRequest::where('status', 'RETURNED')->count();
        if ($returned > 0) {
            $alerts[] = self::item('returned', 'high', 'مرتجعات تنتظر الإصلاح', $returned,
                'أجهزة رجعت من العميل والشركة بتصلحها تاني — محتاجة متابعة فورية', 'طلبات مرتجعة للفني');
        }

        // 4) عادي متأخرة
        $normalOverdue = ServiceRequest::where('urgency', 'normal')
            ->whereNotIn('status', ['COMPLETED', 'CANCELLED'])
            ->where('expected_exit_at', '<', now())
            ->count();
        if ($normalOverdue > 0) {
            $alerts[] = self::item('normal_overdue', 'medium', 'طلبات عادية متأخرة', $normalOverdue,
                'طلبات عادية تجاوزت مهلة التسليم (7 أيام)', 'فلتر العادي المتأخر');
        }

        // 5) طلبات بدون فني (كشف أو تواصل)
        $unassigned = ServiceRequest::whereIn('status', ['PENDING', 'CONTACTED'])->whereNull('assigned_technician_id')->count();
        if ($unassigned > 0) {
            $alerts[] = self::item('unassigned', 'medium', 'طلبات بدون فني', $unassigned,
                'طلبات جديدة (كشف/تواصل) محتاجة تعيين فني', 'طلبات بدون فني');
        }

        // 6) مخزون ناقص
        $lowStock = InventoryItem::whereColumn('quantity', '<=', 'min_quantity')->count();
        if ($lowStock > 0) {
            $alerts[] = self::item('low_stock', 'low', 'مخزون وصل حد التنبيه', $lowStock,
                'أصناف خلصت أو قربت تخلص في المخزن', 'المخزن');
        }

        return $alerts;
    }

    /** عدد الطلبات المتأثرة بكل الإنذارات */
    public static function count(): int
    {
        return array_sum(array_column(self::active(), 'count'));
    }

    /** أقصى خطورة حالية: critical | high | medium | low | none */
    public static function severity(): string
    {
        $order = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];
        $max = 'none';
        foreach (self::active() as $a) {
            if (($order[$a['severity']] ?? 0) > ($order[$max] ?? 0)) {
                $max = $a['severity'];
            }
        }

        return $max;
    }

    /** عناصر إنذار جاهزة للـ JSON الحي (شريط الحالة) */
    public static function liveSummary(): array
    {
        $active = self::active();

        return [
            'alertsCount' => array_sum(array_column($active, 'count')),
            'severity' => self::severity(),
            'items' => array_map(fn ($a) => [
                'key' => $a['key'],
                'severity' => $a['severity'],
                'label' => $a['label'],
                'count' => $a['count'],
                'hint' => $a['hint'],
            ], $active),
            'time' => now()->format('H:i:s'),
        ];
    }

    private static function item(string $key, string $severity, string $label, int $count, string $hint, string $filterLabel): array
    {
        return [
            'key' => $key,
            'severity' => $severity,
            'label' => $label,
            'count' => $count,
            'hint' => $hint,
            'filterLabel' => $filterLabel,
            'url' => self::url($key),
        ];
    }

    private static function url(string $key): string
    {
        return match ($key) {
            'emergency_overdue' => url('/reception?urgency=emergency&overdue=1'),
            'urgent_overdue' => url('/reception?urgency=urgent&overdue=1'),
            'normal_overdue' => url('/reception?overdue=1'),
            'returned' => url('/reception?status=RETURNED'),
            'unassigned' => url('/reception?status=PENDING'),
            'low_stock' => url('/reception/inventory'),
            default => url('/reception'),
        };
    }
}
