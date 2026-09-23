<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * رقم واحد لكل حساب — فهرس فريد على users.phone
 * مفيش رقم يتكرر مرتين تحت أي بند (عميل/استقبال/فني/أدمن/مدير قسم)
 * على مستوى قاعدة البيانات نفسها مش الفاليديشن بس
 */
return new class extends Migration
{
    public function up(): void
    {
        // أمان: لو فيه تكرارات قديمة ننضفها الأول (نسيب الأقدم)
        // الوضع الحالي مفيش تكرارات — ده ضمان إضافي للترقية على نسخ قديمة
        $dupes = DB::select('SELECT phone, COUNT(*) c FROM users GROUP BY phone HAVING c > 1');
        foreach ($dupes as $dupe) {
            $ids = DB::select('SELECT id FROM users WHERE phone = ? ORDER BY id ASC', [$dupe->phone]);
            $keep = array_shift($ids); // الأول (الأقدم)
            foreach ($ids as $row) {
                DB::delete('DELETE FROM users WHERE id = ?', [$row->id]);
            }
        }

        // الفهرس الفريد ممكن يكون موجود أصلاً (أُنشئ منفصلاً) — نتأكد الأول
        $already = collect(DB::select("PRAGMA index_list('users')"))->pluck('name')->contains('users_phone_unique');
        if (! $already) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('phone');
            });
        }
    }

    public function down(): void
    {
        $already = collect(DB::select("PRAGMA index_list('users')"))->pluck('name')->contains('users_phone_unique');
        if ($already) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['phone']);
            });
        }
    }
};
