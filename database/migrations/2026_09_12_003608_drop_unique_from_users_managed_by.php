<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تحسين على الأصل: الأصل كان يقيد كل مدير قسم بفني واحد فقط
     * (managedById @unique في Prisma). المفروض منطقياً أن يدير عدة فنيين —
     * فنشيل القيد الفريد ونسيب الفهرس العادي لسرعة البحث.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_managed_by_unique');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('managed_by');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_managed_by_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('managed_by');
        });
    }
};
