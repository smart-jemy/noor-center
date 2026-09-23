<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'action', 'details', 'ip_address'];

    public static function record(int $userId, string $action, string $details, ?string $ip = null): self
    {
        return static::create([
            'user_id' => $userId,
            'action' => $action,
            'details' => $details,
            'ip_address' => $ip ?? request()?->ip(),
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'LOGIN' => 'تسجيل دخول',
            'LOGOUT' => 'تسجيل خروج',
            'REQUEST_UPDATED' => 'تحديث طلب',
            'REQUEST_ASSIGNED' => 'تعيين فني',
            'SETTINGS_UPDATED' => 'تحديث إعدادات',
            'USER_CREATED' => 'إنشاء مستخدم',
            'USER_UPDATED' => 'تحديث مستخدم',
            'REVIEW_UPDATED' => 'تحديث تقييم',
            'ACCOUNT_UPDATED' => 'تحديث حساب',
            'INVENTORY_ADDED' => 'إضافة صنف',
            'INVENTORY_UPDATED' => 'تحديث صنف',
            'INVENTORY_DELETED' => 'حذف صنف',
            'EXPENSE_ADDED' => 'إضافة مصروف',
            'EXPENSE_UPDATED' => 'تحديث مصروف',
            'EXPENSE_DELETED' => 'حذف مصروف',
            'PARTNER_ADDED' => 'إضافة شريك',
            'PARTNER_UPDATED' => 'تحديث شريك',
            'PARTNER_REPAIR_ADDED' => 'صيانة شريك',
            'PARTNER_SETTLEMENT_ADDED' => 'تسوية شريك',
            'RESET' => 'تصفير النظام',
            default => $this->action,
        };
    }

    public function actionColor(): string
    {
        return match ($this->action) {
            'LOGIN' => 'bg-green-100 text-green-700',
            'LOGOUT' => 'bg-gray-100 text-gray-700',
            'REQUEST_UPDATED', 'REQUEST_ASSIGNED' => 'bg-blue-100 text-blue-700',
            'RESET', 'EXPENSE_DELETED', 'INVENTORY_DELETED' => 'bg-red-100 text-red-700',
            'INVENTORY_ADDED', 'USER_CREATED', 'PARTNER_ADDED' => 'bg-emerald-100 text-emerald-700',
            default => 'bg-amber-100 text-amber-700',
        };
    }
}
