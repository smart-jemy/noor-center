<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * سعر صيانة الشركاء اختياري — الاستقبال بيستلم الأجهزة الأول ويحدد السعر بعدين
 * total_amount بقى NULL = السعر لسه محددش
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_repairs', function (Blueprint $table) {
            $table->double('total_amount')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('partner_repairs', function (Blueprint $table) {
            $table->double('total_amount')->default(0)->change();
        });
    }
};
