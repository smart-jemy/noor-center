<?php

namespace App\Support;

use App\Models\ServiceRequest;
use App\Models\User;

/**
 * نظام الصلاحيات المركزي — مطابق تماماً لمنطق المشروع الأصلي (src/lib/auth.ts)
 *
 * الأصل:
 * - requireStaff            → ADMIN + RECEPTION
 * - requireRequestEditor    → ADMIN + RECEPTION + DEPARTMENT_MANAGER
 * - requireAdmin            → ADMIN فقط
 * - requireDepartmentManager→ DEPARTMENT_MANAGER فقط
 * - requireAdminOrDeptMgr   → ADMIN + DEPARTMENT_MANAGER
 * - requireTechnician       → TECHNICIAN فقط
 * - canEditRequest(role, currentStatus):
 *     ADMIN  → أي حالة
 *     RECEPTION → فقط إذا الطلب حالياً PENDING / CONFIRMED / IN_PROGRESS
 */
class Permissions
{
    /** موظف الاستقبال: أدمن + استقبال (عرض الطلبات، العملاء، الملاحظات، الإشعارات) */
    public static function staff(User $user): bool
    {
        return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_RECEPTION], true);
    }

    /** محرر الطلبات: أدمن + استقبال + مدير قسم (المخزن، القطع، المصروفات، الشركاء) */
    public static function requestEditor(User $user): bool
    {
        return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_RECEPTION, User::ROLE_DEPARTMENT_MANAGER], true);
    }

    public static function admin(User $user): bool
    {
        return $user->role === User::ROLE_ADMIN;
    }

    public static function departmentManager(User $user): bool
    {
        return $user->role === User::ROLE_DEPARTMENT_MANAGER;
    }

    public static function adminOrDeptManager(User $user): bool
    {
        return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_DEPARTMENT_MANAGER], true);
    }

    public static function technician(User $user): bool
    {
        return $user->role === User::ROLE_TECHNICIAN;
    }

    /**
     * هل يمكن تعديل الطلب حسب حالته الحالية؟
     * الأدمن يعدل أي حالة — الاستقبال يعدل الطلبات النشطة فقط.
     * المرتجع (RETURNED) حالة نشطة: الشركة بتصلحه تاني.
     * (مدير القسم في الأصل لا يعدل حالة الطلبات — لكنه يضيف قطعاً ومخزناً)
     */
    public static function canEditRequest(string $role, string $currentStatus): bool
    {
        if ($role === User::ROLE_ADMIN) {
            return true;
        }

        if ($role === User::ROLE_RECEPTION) {
            return in_array($currentStatus, ServiceRequest::ACTIVE_STATUSES, true);
        }

        return false;
    }

    /** الاستقبال/مدير القسم يضيفون شركاء وصيانات وتسويات — التعديل والحذف للأدمن فقط */
    public static function canManagePartners(User $user): bool
    {
        return self::requestEditor($user);
    }

    public static function canDeletePartners(User $user): bool
    {
        return self::admin($user);
    }

    /** جدول توضيحي للصلاحيات (يُستخدم في صفحة عرض الأدوار) */
    public static function matrix(): array
    {
        return [
            ['الميزة', 'أدمن', 'استقبال', 'مدير قسم', 'فني', 'عميل'],
            ['عرض كل الطلبات', '✓', '✓', 'طلبات قسمه', 'المسندة له', 'طلباته'],
            ['تعديل حالة الطلب', 'أي حالة', 'النشطة فقط', '—', '—', '—'],
            ['حذف طلب', '✓', '—', '—', '—', '—'],
            ['تسجيل جهاز واصل', '✓', '✓', '✓', '—', '—'],
            ['بحث عميل بالهاتف', '✓', '✓', '✓', '—', '—'],
            ['ملاحظات الطلب', '✓', '✓', '—', '—', '—'],
            ['قطع الغيار في الطلب', '✓', '✓', '✓', '—', '—'],
            ['المخزن (عرض/إضافة)', '✓', '✓', '✓', '—', '—'],
            ['تعديل كميات المخزن', '✓', '—', '✓', '—', '—'],
            ['العملاء + ملاحظاتهم', '✓', '✓', 'عملاء قسمه', '—', '—'],
            ['المصروفات', '✓', '✓', '—', '—', '—'],
            ['الشركاء (إضافة)', '✓', '✓', '—', '—', '—'],
            ['الشركاء (تعديل/حذف)', '✓', '—', '—', '—', '—'],
            ['إدارة الفنيين', '✓', '—', 'فنيو قسمه', '—', '—'],
            ['التقارير المالية', '✓', 'إحصائيات', 'تقرير القسم', '—', '—'],
            ['إشعارات الطاقم', '✓', '✓', '✓', '—', '—'],
        ];
    }
}
