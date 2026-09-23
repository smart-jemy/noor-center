<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

/**
 * مهلة الطلبات العادية بقت 7 أيام (168 ساعة) بدل 96 ساعة
 * — إعادة حساب ميعاد الخروج المتوقع لكل الطلبات العادية النشطة
 * (المكتمل والملغي محفوظين بتواريخهم التاريخية زي ما هما)
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('service_requests')
            ->where('urgency', 'normal')
            ->whereNotIn('status', ['COMPLETED', 'CANCELLED'])
            ->update([
                'expected_exit_at' => DB::raw("datetime(COALESCE(entered_at, created_at), '+168 hours')"),
            ]);
    }

    public function down(): void
    {
        DB::table('service_requests')
            ->where('urgency', 'normal')
            ->whereNotIn('status', ['COMPLETED', 'CANCELLED'])
            ->update([
                'expected_exit_at' => DB::raw("datetime(COALESCE(entered_at, created_at), '+96 hours')"),
            ]);
    }
};
