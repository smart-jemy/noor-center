<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // طلبات الصيانة
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 20)->nullable()->unique(); // NC 0001 2026
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->string('device_type');
            $table->string('brand')->nullable();
            $table->text('issue_description');
            $table->string('area', 32);              // 6_october | pyramids_gardens
            $table->text('address');
            $table->string('phone', 32);
            $table->date('preferred_date');
            $table->string('preferred_time', 16);
            $table->json('photos')->default('[]');
            $table->string('status', 16)->default('PENDING'); // PENDING|CONFIRMED|IN_PROGRESS|COMPLETED|CANCELLED
            $table->text('admin_notes')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->double('paid_amount')->default(0);         // المدفوع (آجل = الفرق)
            $table->foreignId('assigned_technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('warranty_months')->default(0);
            $table->date('warranty_end_date')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('status');
            $table->index(['status', 'assigned_technician_id']);
            $table->index('assigned_technician_id');
            $table->index('department_id');
            $table->index('phone');
            $table->index('completed_at');
        });

        // أنواع الأجهزة (يديرها الأدمن)
        Schema::create('device_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('accessories')->default('[]'); // ملحقات (حد أقصى 5)
            $table->timestamps();

            $table->index('name');
        });

        // إعدادات الموقع (صف واحد)
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 32)->default('01000000000');
            $table->string('email')->default('info@noor-maintenance.com');
            $table->string('address')->default('حدائق الأهرام');
            $table->string('working_hours')->default('يومياً 9 ص - 9 م');
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('whatsapp_num', 32)->nullable();
            $table->string('receipt_title')->default('إيصال استلام جهاز');
            $table->string('receipt_subtitle')->default('نور للصيانة');
            $table->text('receipt_notes')->default('المركز غير مسؤول عن الجهاز بعد 14 يوما من تاريخ الاستلام.');
            $table->string('receipt_footer')->default('هذا الإيصال يعتبر سند استلام للجهاز المذكور أعلاه');
            $table->boolean('maintenance_reminder_enabled')->default(false);
            $table->integer('maintenance_reminder_months')->default(6);
            $table->boolean('sound_notification_enabled')->default(false);
            $table->string('sound_notification_url')->nullable();
            $table->timestamps();
        });

        // التقييمات
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->unique()->constrained('service_requests')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->integer('rating'); // 1-5
            $table->text('comment');
            $table->string('status', 16)->default('PENDING'); // PENDING|APPROVED|REJECTED
            $table->text('admin_reply')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('customer_id');
        });

        // الإشعارات
        Schema::create('notifications_custom', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('title');
            $table->text('message');
            $table->foreignId('request_id')->nullable()->constrained('service_requests')->nullOnDelete();
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_read']);
        });

        // سجل النشاط
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('action', 48);
            $table->text('details');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('created_at');
        });

        // المخزون
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category', 32); // parts|tools|consumables|other
            $table->string('brand')->nullable();
            $table->string('part_number')->nullable();
            $table->double('quantity')->default(0);
            $table->string('unit')->default('قطعة');
            $table->double('cost_price')->default(0);
            $table->double('sell_price')->default(0);
            $table->double('min_quantity')->default(0);
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->timestamps();

            $table->index('category');
            $table->index('name');
            $table->index('department_id');
        });

        // القطع المستخدمة في الطلبات
        Schema::create('used_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('service_requests')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->string('custom_name')->nullable(); // قطعة خارجية
            $table->double('quantity');
            $table->double('unit_price');
            $table->timestamps();

            $table->index('request_id');
            $table->index('inventory_item_id');
        });

        // ملاحظات العملاء
        Schema::create('customer_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->text('content');
            $table->timestamps();

            $table->index('customer_id');
        });

        // ملاحظات داخلية على الطلبات
        Schema::create('request_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('service_requests')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->text('content');
            $table->timestamps();

            $table->index('request_id');
        });

        // المصروفات
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->double('amount');
            $table->text('description');
            $table->string('category', 32)->default('other'); // rent|utilities|salaries|supplies|transport|other
            $table->date('date');
            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('date');
            $table->index('category');
        });

        // شركاء الصيانة
        Schema::create('partner_technicians', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 32)->nullable();
            $table->text('notes')->nullable();
            $table->double('credit_limit')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('name');
            $table->index('phone');
        });

        // صيانات الشركاء
        Schema::create('partner_repairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partner_technicians')->cascadeOnDelete();
            $table->string('device_type');
            $table->string('brand')->nullable();
            $table->string('customer_name')->nullable();
            $table->text('issue_description')->nullable();
            $table->double('total_amount');
            $table->double('paid_amount')->default(0);
            $table->date('date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('partner_id');
            $table->index('date');
        });

        // دفعات تسوية الشركاء
        Schema::create('partner_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partner_technicians')->cascadeOnDelete();
            $table->double('amount');
            $table->date('date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('partner_id');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_settlements');
        Schema::dropIfExists('partner_repairs');
        Schema::dropIfExists('partner_technicians');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('request_notes');
        Schema::dropIfExists('customer_notes');
        Schema::dropIfExists('used_parts');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('notifications_custom');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('device_types');
        Schema::dropIfExists('service_requests');
    }
};
