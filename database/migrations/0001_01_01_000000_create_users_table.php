<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // المستخدمون: عميل / أدمن / استقبال / فني / مدير قسم
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique();          // الهاتف هو وسيلة الدخول
            $table->string('password');
            $table->string('legacy_password')->nullable(); // كلمات المرور القديمة (scrypt من النظام السابق) — تُرحّل وتُحدّث تلقائياً عند أول دخول
            $table->string('role', 32)->default('CUSTOMER'); // CUSTOMER|ADMIN|RECEPTION|TECHNICIAN|DEPARTMENT_MANAGER
            $table->string('specialty')->nullable();     // تخصص الفني
            $table->foreignId('department_id')->nullable()->unique()->constrained('departments')->nullOnDelete(); // القسم الذي يديره
            $table->foreignId('managed_by')->nullable()->unique()->constrained('users')->nullOnDelete(); // مدير القسم المسؤول عن الفني
            $table->boolean('is_active')->default(true);
            $table->integer('loyalty_points')->default(0); // نقاط الولاء (1ج = 1نقطة)
            $table->double('total_spent')->default(0);     // إجمالي الإنفاق
            $table->rememberToken();
            $table->timestamps();

            $table->index(['role', 'is_active']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('phone')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // جلسات قاعدة البيانات (سريعة وآمنة)
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
