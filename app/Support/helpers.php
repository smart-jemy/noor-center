<?php

use App\Support\Icons;

if (! function_exists('icon')) {
    /** رسم أيقونة SVG inline */
    function icon(string $name, string $class = 'h-4 w-4'): string
    {
        return Icons::render($name, $class);
    }
}

if (! function_exists('device_icon')) {
    /** أيقونة جهاز حسب اسمه */
    function device_icon(string $deviceName, string $class = 'h-6 w-6'): string
    {
        return Icons::render(Icons::device($deviceName), $class);
    }
}

if (! function_exists('money')) {
    /** تنسيق مبلغ بالجنيه */
    function money($amount): string
    {
        return number_format((float) $amount, ((float) $amount == (int) $amount) ? 0 : 2).' ج.م';
    }
}

if (! function_exists('dt')) {
    /** تنسيق تاريخ عربي مختصر */
    function dt($date, bool $withTime = false): string
    {
        if (! $date) return '—';
        $months = [1 => 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
        $ts = is_string($date) ? strtotime($date) : $date->getTimestamp();
        $out = date('j', $ts).' '.$months[(int) date('n', $ts)].' '.date('Y', $ts);
        if ($withTime) $out .= ' — '.date('g:i A', $ts);
        return $out;
    }
}
