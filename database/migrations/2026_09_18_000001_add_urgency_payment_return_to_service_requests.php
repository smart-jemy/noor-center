<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * نظام التشغيل الجديد للأوردرات:
 * - الاستعجال: normal عادي | urgent مستعجل | emergency طوارئ — يحدد مهلة التسليم (SLA)
 * - ميعاد الدخول entered_at + ميعاد الخروج المتوقع expected_exit_at (يحسب من الاستعجال)
 * - المرتجع للفني: is_returned + returned_at + return_reason + return_count
 * - طريقة الدفع: cash كاش | transfer تحويل (عند الإكمال = تم التحصيل)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->string('urgency', 16)->default('normal')->index();   // normal|urgent|emergency
            $table->string('payment_method', 16)->nullable();            // cash|transfer
            $table->timestamp('entered_at')->nullable();                 // ميعاد دخول الجهاز للمركز
            $table->timestamp('expected_exit_at')->nullable()->index();  // ميعاد الخروج المتوقع (SLA)
            $table->boolean('is_returned')->default(false)->index();     // مرتجع للفني؟
            $table->timestamp('returned_at')->nullable();                // آخر إرجاع
            $table->text('return_reason')->nullable();                   // سبب الإرجاع
            $table->integer('return_count')->default(0);                 // عدد مرات الإرجاع
        });

        // ملء البيانات القديمة: ميعاد الدخول = تاريخ الإنشاء، وميعاد الخروج = الدخول + SLA حسب الاستعجال
        // (الكل عادي في القديم → 96 ساعة)
        $slaHours = ['emergency' => 24, 'urgent' => 48, 'normal' => 96];
        DB::table('service_requests')->select('id', 'created_at', 'urgency')->chunkById(200, function ($rows) use ($slaHours) {
            foreach ($rows as $r) {
                $entered = $r->created_at;
                $hours = $slaHours[$r->urgency] ?? 96;
                DB::table('service_requests')->where('id', $r->id)->update([
                    'entered_at' => $entered,
                    'expected_exit_at' => \Carbon\Carbon::parse($entered)->addHours($hours),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn(['urgency', 'payment_method', 'entered_at', 'expected_exit_at', 'is_returned', 'returned_at', 'return_reason', 'return_count']);
        });
    }
};
