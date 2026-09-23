<?php

namespace App\Support;

/**
 * مولّد رسوم SVG خفيف — بدون أي مكتبات خارجية
 * سباركلاين (منحنى مصغر) + دونات (توزيع دائري)
 * مستوحى من رسوم "غرفة العمليات" — خطوط نظيفة وألوان حية
 */
class Chart
{
    /** ألوان النظام */
    public const PALETTE = [
        '#10b981', '#f59e0b', '#3b82f6', '#8b5cf6', '#ec4899', '#64748b',
    ];

    public const SEV_COLORS = [
        'critical' => '#ef4444',
        'high' => '#f97316',
        'medium' => '#f59e0b',
        'low' => '#94a3b8',
        'none' => '#10b981',
    ];

    /**
     * سباركلاين SVG — منحنى ناعم مع تعبئة خفيفة
     * يُرجع HTML جاهز للطبع
     */
    public static function sparkline(array $values, string $color = '#10b981', int $w = 130, int $h = 38): string
    {
        $values = array_values($values);
        $n = count($values);
        if ($n < 2) {
            $values = array_merge($values, $values ?: [0]);
            $n = count($values);
        }
        if ($n < 2) {
            $values = [0, 0];
            $n = 2;
        }

        $max = max($values);
        $min = min($values);
        $range = ($max - $min) ?: 1;

        $pad = 3;
        $stepX = ($w - $pad * 2) / ($n - 1);

        $points = [];
        foreach ($values as $i => $v) {
            $x = $pad + $i * $stepX;
            $y = $pad + ($h - $pad * 2) * (1 - (($v - $min) / $range));
            $points[] = [$x, $y];
        }

        // مسار ناعم (منحنيات كواردراتيك بسيطة)
        $path = 'M'.$points[0][0].','. $points[0][1];
        for ($i = 1; $i < $n; $i++) {
            $prev = $points[$i - 1];
            $curr = $points[$i];
            $cx = ($prev[0] + $curr[0]) / 2;
            $path .= ' C'.$cx.','.$prev[1].' '.$cx.','.$curr[1].' '.$curr[0].','.$curr[1];
        }

        // مسار التعبئة (لأسفل ثم إغلاق)
        $fill = $path.' L'.$points[$n - 1][0].','.$h.' L'.$points[0][0].','.$h.' Z';

        $uid = 'sp'.uniqid();

        return '<svg class="sparkline" width="'.$w.'" height="'.$h.'" viewBox="0 0 '.$w.' '.$h.'" role="img">'
            .'<path class="spark-fill" d="'.$fill.'" fill="'.$color.'"></path>'
            .'<path class="spark-line" d="'.$path.'" stroke="'.$color.'"></path>'
            .'<circle cx="'.$points[$n - 1][0].'" cy="'.$points[$n - 1][1].'" r="2.6" fill="'.$color.'"></circle>'
            .'</svg>';
    }

    /**
     * دونات SVG — حلقات stroke-dasharray
     * $segments = [['label' => ..., 'value' => N, 'color' => '#...'], ...]
     */
    public static function donut(array $segments, int $size = 170, int $thickness = 22, ?string $centerTitle = null, ?string $centerSub = null): string
    {
        $segments = array_values(array_filter($segments, fn ($s) => $s['value'] > 0));
        $total = array_sum(array_column($segments, 'value'));

        $r = ($size - $thickness) / 2;
        $c = 2 * M_PI * $r;
        $cx = $size / 2;
        $cy = $size / 2;

        $svg = '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 '.$size.' '.$size.'" role="img" style="transform: rotate(-90deg)">';

        // حلقة الخلفية
        $svg .= '<circle cx="'.$cx.'" cy="'.$cy.'" r="'.$r.'" fill="none" stroke="rgba(127,127,127,0.13)" stroke-width="'.$thickness.'"></circle>';

        if ($total > 0) {
            $offset = 0.0;
            foreach ($segments as $i => $s) {
                $color = $s['color'] ?? self::PALETTE[$i % count(self::PALETTE)];
                $len = ($s['value'] / $total) * $c;
                // فجوة صغيرة بين الشرائح (بس لو أكتر من شريحة)
                $gap = count($segments) > 1 ? min(3.0, $len * 0.08) : 0.0;
                $svg .= '<circle cx="'.$cx.'" cy="'.$cy.'" r="'.$r.'" fill="none" stroke="'.$color.'" stroke-width="'.$thickness.'" '
                    .'stroke-dasharray="'.max(0.1, $len - $gap).' '.$c.'" stroke-dashoffset="'.(-$offset).'" '
                    .'stroke-linecap="round" style="transition: stroke-dasharray .9s cubic-bezier(.22,1,.36,1), stroke-dashoffset .9s cubic-bezier(.22,1,.36,1)"></circle>';
                $offset += $len;
            }
        }

        $svg .= '</svg>';

        // نص المنتصف (فوق الدوران)
        if ($centerTitle !== null) {
            $svg .= '<div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none" style="width:'.$size.'px;height:'.$size.'px">'
                .'<span class="text-xl font-extrabold">'.$centerTitle.'</span>'
                .($centerSub ? '<span class="text-[10px] text-muted-foreground font-bold">'.$centerSub.'</span>' : '')
                .'</div>';
        }

        return $svg;
    }

    /** لون حسب الخطورة */
    public static function sevColor(string $severity): string
    {
        return self::SEV_COLORS[$severity] ?? self::SEV_COLORS['none'];
    }
}
