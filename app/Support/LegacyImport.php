<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\CustomerNote;
use App\Models\Department;
use App\Models\DeviceType;
use App\Models\Expense;
use App\Models\InventoryItem;
use App\Models\NoorNotification;
use App\Models\PartnerRepair;
use App\Models\PartnerSettlement;
use App\Models\PartnerTechnician;
use App\Models\RequestNote;
use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\Setting;
use App\Models\UsedPart;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * استعادة البيانات من قاعدة بيانات مشروع Next.js القديم (Prisma + SQLite)
 * ----------------------------------------------------------------------------
 * - يقرأ ملف custom.db مباشرة (PDO SQLite) بدون أي اعتماد على Prisma
 * - يحوّل معرّفات Prisma النصية (cuid) إلى معرّفات رقمية عبر خرائط مرجعية
 * - يحوّل طوابع Prisma (ملّي ثانية منذ الحقبة) إلى تواريخ SQL
 * - وضعان:
 *   merge   → دمج ذكي: اللي موجود يتحدّث والجديد يتضاف (بياناتك الحالية محفوظة)
 *   replace → استعادة كاملة: كل بيانات الأعمال تتمسح ويتم استيراد القديمة كما هي
 *     (حساب الأدمن الحالي محفوظ دايماً — لو نفس الرقم القديم يتبنى عليه)
 * - كلمات مرور القديم (scrypt) تُحفظ في legacy_password فيشتغل تسجيل الدخول
 *   بنفس كلمات المرور القديمة (الترقية التلقائية لـ bcrypt موجودة في User)
 */
class LegacyImport
{
    /** الجداول المتوقعة في ملف النظام القديم */
    public const EXPECTED = [
        'User', 'ServiceRequest', 'Department', 'DeviceType', 'InventoryItem',
        'Review', 'Notification', 'ActivityLog', 'CustomerNote', 'RequestNote',
        'UsedPart', 'Expense', 'PartnerTechnician', 'PartnerRepair', 'PartnerSettlement', 'Settings',
    ];

    // ============================================================
    // فحص الملف — عدّادات كل جدول + معلومات الملف
    // ============================================================

    public static function inspect(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            return ['valid' => false, 'error' => 'الملف غير موجود أو غير قابل للقراءة'];
        }

        try {
            $pdo = self::pdo($path);
        } catch (\Throwable $e) {
            return ['valid' => false, 'error' => 'الملف مش قاعدة بيانات SQLite صالحة (اتأكد إنه ملف custom.db بتاع مشروع Next.js)'];
        }

        $tables = [];
        foreach ($pdo->query("SELECT name FROM sqlite_master WHERE type='table'") as $t) {
            $tables[] = $t['name'];
        }

        if (! in_array('User', $tables, true) || ! in_array('ServiceRequest', $tables, true)) {
            return ['valid' => false, 'error' => 'الملف ناقص جداول النظام القديم (User / ServiceRequest) — متأكد إنه db/custom.db؟'];
        }

        $counts = [];
        foreach (self::EXPECTED as $t) {
            $counts[$t] = in_array($t, $tables, true)
                ? (int) $pdo->query("SELECT COUNT(*) c FROM \"{$t}\"")->fetch(\PDO::FETCH_ASSOC)['c']
                : 0;
        }

        // عينة من بيانات القديم للتأكيد
        $sample = $pdo->query('SELECT "name", "phone", role FROM "User" LIMIT 3')->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'valid' => true,
            'error' => null,
            'counts' => $counts,
            'sample' => $sample,
            'file' => [
                'size' => filesize($path),
                'modified' => Carbon::createFromTimestamp(filemtime($path))->format('Y-m-d H:i'),
            ],
        ];
    }

    // ============================================================
    // التنفيذ
    // ============================================================

    /**
     * @param  string  $mode  merge|replace
     * @param  bool  $withSettings  استيراد إعدادات الموقع القديمة كذلك
     * @param  int|null  $keepUserId  حساب الأدمن المحفوظ في وضع الاستبدال
     * @return array ملخص النتائج لكل جدول (added / updated / skipped)
     */
    public static function run(string $path, string $mode = 'merge', bool $withSettings = false, ?int $keepUserId = null): array
    {
        $pdo = self::pdo($path);
        date_default_timezone_set(config('app.timezone'));

        $summary = collect([
            'users', 'departments', 'deviceTypes', 'requests', 'inventory',
            'partners', 'repairs', 'settlements', 'expenses', 'reviews',
            'notifications', 'activityLogs', 'customerNotes', 'requestNotes', 'usedParts',
        ])->mapWithKeys(fn ($k) => [$k => ['added' => 0, 'updated' => 0, 'skipped' => 0]])->all();

        $fallbackUser = (int) ($keepUserId ?? (auth()->id() ?? User::where('role', User::ROLE_ADMIN)->value('id') ?? 1));

        // فتح باب الإسناد الجماعي (الأعمدة الزمنية مش في fillable)
        // ملاحظة: PRAGMA foreign_keys لازم يتتنفذ خارج الترانزاكشن — عشان كده خارج closure الترانزاكشن
        Model::unguard();
        try {
            if ($mode === 'replace') {
                DB::statement('PRAGMA foreign_keys = OFF');
            }

            DB::transaction(function () use ($pdo, $mode, $withSettings, $keepUserId, $fallbackUser, &$summary) {
                self::import($pdo, $mode, $withSettings, $keepUserId, $fallbackUser, $summary);
            });
        } finally {
            if ($mode === 'replace') {
                DB::statement('PRAGMA foreign_keys = ON');
            }
            Model::reguard();
        }

        return $summary;
    }

    /** التنفيذ الفعلي داخل الترانزاكشن */
    private static function import(\PDO $pdo, string $mode, bool $withSettings, ?int $keepUserId, int $fallbackUser, array &$summary): void
    {
        // ===== 0) وضع الاستبدال: نمسح بيانات الأعمال أولاً (الأبناء قبل الآباء) =====
        $keptAdmin = null;
        if ($mode === 'replace') {
            $keptAdmin = $keepUserId ? User::find($keepUserId) : User::where('role', User::ROLE_ADMIN)->first();

            foreach ([
                'reviews', 'notifications_custom', 'activity_logs', 'used_parts', 'customer_notes',
                'request_notes', 'expenses', 'partner_settlements', 'partner_repairs',
                'partner_technicians', 'service_requests', 'inventory_items', 'device_types', 'departments',
            ] as $t) {
                DB::table($t)->delete();
            }
            // نمسح كل المستخدمين ما عدا الأدمن المحفوظ
            User::whereKeyNot($keptAdmin?->id ?? 0)->delete();
            self::resetSequences();
        }

        // ===== 1) الأقسام وأنواع الأجهزة (قبل أي حاجة تانية) =====
        $deptMap = [];
        foreach (self::rows($pdo, 'Department') as $r) {
            $found = Department::where('name', $r['name'])->first();
            if ($found) {
                $found->update([
                    'icon' => $r['icon'] ?? $found->icon,
                    'device_types' => self::jsonArray($r['deviceTypes'] ?? '[]'),
                    'description' => $r['description'] ?? null,
                    'is_active' => (bool) ($r['isActive'] ?? true),
                ]);
                $summary['departments']['updated']++;
            } else {
                $found = Department::create([
                    'name' => $r['name'],
                    'icon' => $r['icon'] ?? null,
                    'device_types' => self::jsonArray($r['deviceTypes'] ?? '[]'),
                    'description' => $r['description'] ?? null,
                    'is_active' => (bool) ($r['isActive'] ?? true),
                ]);
                $summary['departments']['added']++;
            }
            $deptMap[$r['id']] = $found->id;
        }

        $deviceMap = [];
        foreach (self::rows($pdo, 'DeviceType') as $r) {
            $found = DeviceType::where('name', $r['name'])->first();
            $payload = [
                'icon' => $r['icon'] ?? null,
                'is_active' => (bool) ($r['isActive'] ?? true),
                'sort_order' => (int) ($r['sortOrder'] ?? 0),
                'accessories' => self::jsonArray($r['accessories'] ?? '[]'),
            ];
            if ($found) {
                $found->update($payload);
                $summary['deviceTypes']['updated']++;
            } else {
                $found = DeviceType::create($payload + ['name' => $r['name']]);
                $summary['deviceTypes']['added']++;
            }
            $deviceMap[$r['id']] = $found->id;
        }

        // ===== 2) المستخدمون — مطابقة بالهاتف =====
        $userMap = [];
        $keptPhone = $keptAdmin?->phone;
        foreach (self::rows($pdo, 'User') as $r) {
            // الأدمن المحفوظ بنفس الرقم → الخريطة تشاور على حسابه الحالي (بدون تكرار)
            if ($keptPhone !== null && ($r['phone'] ?? '') === $keptPhone) {
                $userMap[$r['id']] = $keptAdmin->id;
                $summary['users']['skipped']++;
                continue;
            }

            $found = User::where('phone', $r['phone'])->first();
            if ($found) {
                // موجود: نحدّث بياناته التشغيلية بدون مس كلمة المرور أو الدور الحالي
                $found->update([
                    'name' => $r['name'] ?? $found->name,
                    'specialty' => $r['specialty'] ?? $found->specialty,
                    'department_id' => $deptMap[$r['departmentId'] ?? ''] ?? ($mode === 'replace' ? null : $found->department_id),
                    'is_active' => (bool) ($r['isActive'] ?? true),
                    'loyalty_points' => (int) ($r['loyaltyPoints'] ?? 0),
                    'total_spent' => (float) ($r['totalSpent'] ?? 0),
                    'managed_by' => null, // يتربط تاني تحت بعد اكتمال الاستيراد
                ]);
                $userMap[$r['id']] = $found->id;
                $summary['users']['updated']++;
            } else {
                $hasLegacy = ! empty($r['passwordHash']);
                $created = User::create([
                    'name' => $r['name'],
                    'phone' => $r['phone'],
                    // حسابه بكلمة مروره القديمة — البديل العشوائي مش قابل للتطابق فالتحقق بيقع على legacy
                    'password' => $hasLegacy ? Str::random(40) : '123456',
                    'legacy_password' => $hasLegacy ? $r['passwordHash'] : null,
                    'role' => in_array($r['role'] ?? '', array_keys(User::ROLES), true) ? $r['role'] : User::ROLE_CUSTOMER,
                    'specialty' => $r['specialty'] ?? null,
                    'department_id' => $deptMap[$r['departmentId'] ?? ''] ?? null,
                    'is_active' => (bool) ($r['isActive'] ?? true),
                    'loyalty_points' => (int) ($r['loyaltyPoints'] ?? 0),
                    'total_spent' => (float) ($r['totalSpent'] ?? 0),
                    'created_at' => self::ms($r['createdAt'] ?? null) ?? now(),
                    'updated_at' => self::ms($r['updatedAt'] ?? null) ?? now(),
                ]);
                $userMap[$r['id']] = $created->id;
                $summary['users']['added']++;
            }
        }

        // ربط الفنيين بمديريهم (managedById) بعد اكتمال خريطة المستخدمين
        foreach (self::rows($pdo, 'User') as $r) {
            if (! empty($r['managedById']) && isset($userMap[$r['managedById']], $userMap[$r['id']])) {
                User::whereKey($userMap[$r['id']])->update(['managed_by' => $userMap[$r['managedById']]]);
            }
        }

        // ===== 3) طلبات الصيانة =====
        $reqMap = [];
        foreach (self::rows($pdo, 'ServiceRequest') as $r) {
            $customerId = $userMap[$r['customerId']] ?? null;
            if (! $customerId) {
                $summary['requests']['skipped']++;
                continue;
            }

            // المطابقة: برقم الأوردر لو موجود، وإلا ببصمة تركيبية (عميل + جهاز + وصف + ميعاد)
            $found = null;
            if (! empty($r['orderNumber'])) {
                $found = ServiceRequest::where('order_number', $r['orderNumber'])->first();
            } else {
                $found = ServiceRequest::where('customer_id', $customerId)
                    ->where('device_type', $r['deviceType'])
                    ->where('issue_description', $r['issueDescription'])
                    ->where('preferred_date', $r['preferredDate'])
                    ->first();
            }

            $enteredAt = self::ms($r['createdAt'] ?? null) ?? now();
            $payload = [
                'device_type' => $r['deviceType'],
                'brand' => $r['brand'] ?? null,
                'issue_description' => $r['issueDescription'],
                'area' => $r['area'] ?: '',
                'address' => $r['address'] ?: '',
                'phone' => $r['phone'] ?: '',
                'preferred_date' => $r['preferredDate'] ?: now()->toDateString(),
                'preferred_time' => $r['preferredTime'] ?: '',
                'photos' => self::jsonArray($r['photos'] ?? '[]'),
                'status' => in_array($r['status'] ?? '', ServiceRequest::STATUSES, true) ? $r['status'] : 'PENDING',
                'admin_notes' => $r['adminNotes'] ?? null,
                'price' => ($r['price'] ?? '') !== '' && $r['price'] !== null ? (float) $r['price'] : null,
                'paid_amount' => (float) ($r['paidAmount'] ?? 0),
                'assigned_technician_id' => $userMap[$r['assignedTechnicianId'] ?? ''] ?? null,
                'assigned_at' => self::ms($r['assignedAt'] ?? null),
                'completed_at' => self::ms($r['completedAt'] ?? null),
                'warranty_months' => (int) ($r['warrantyMonths'] ?? 0),
                'warranty_end_date' => $r['warrantyEndDate'] ?: null,
                'department_id' => $deptMap[$r['departmentId'] ?? ''] ?? null,
                'entered_at' => $enteredAt,
                'expected_exit_at' => Carbon::parse($enteredAt)->addHours(ServiceRequest::URGENCY_SLA_HOURS['normal']),
            ];

            if ($found) {
                $found->update($payload);
                $reqMap[$r['id']] = $found->id;
                $summary['requests']['updated']++;
            } else {
                $payload['order_number'] = ! empty($r['orderNumber']) ? $r['orderNumber'] : Noor::orderNumber();
                $created = ServiceRequest::create($payload);
                $reqMap[$r['id']] = $created->id;
                $summary['requests']['added']++;
            }
        }

        // ===== 4) المخزون =====
        foreach (self::rows($pdo, 'InventoryItem') as $r) {
            $found = InventoryItem::where('name', $r['name'])
                ->where('category', $r['category'])
                ->where('brand', $r['brand'] ?? '')
                ->where('part_number', $r['partNumber'] ?? '')
                ->first();
            $payload = [
                'quantity' => (float) ($r['quantity'] ?? 0),
                'unit' => $r['unit'] ?? 'قطعة',
                'cost_price' => (float) ($r['costPrice'] ?? 0),
                'sell_price' => (float) ($r['sellPrice'] ?? 0),
                'min_quantity' => (float) ($r['minQuantity'] ?? 0),
                'location' => $r['location'] ?? null,
                'notes' => $r['notes'] ?? null,
                'department_id' => $deptMap[$r['departmentId'] ?? ''] ?? null,
            ];
            if ($found) {
                $found->update($payload);
                $summary['inventory']['updated']++;
            } else {
                InventoryItem::create($payload + [
                    'name' => $r['name'],
                    'category' => $r['category'],
                    'brand' => $r['brand'] ?? null,
                    'part_number' => $r['partNumber'] ?? null,
                ]);
                $summary['inventory']['added']++;
            }
        }

        // ===== 5) شركاء الصيانة + صياناتهم + تسوياتهم =====
        $partnerMap = [];
        foreach (self::rows($pdo, 'PartnerTechnician') as $r) {
            $found = PartnerTechnician::where('name', $r['name'])->where('phone', $r['phone'] ?? '')->first();
            $payload = [
                'phone' => $r['phone'] ?? null,
                'notes' => $r['notes'] ?? null,
                'credit_limit' => (float) ($r['creditLimit'] ?? 0),
                'is_active' => (bool) ($r['isActive'] ?? true),
                'created_by_id' => $userMap[$r['createdById'] ?? ''] ?? $fallbackUser,
            ];
            if ($found) {
                $found->update($payload);
                $partnerMap[$r['id']] = $found->id;
                $summary['partners']['updated']++;
            } else {
                $created = PartnerTechnician::create($payload + ['name' => $r['name']]);
                $partnerMap[$r['id']] = $created->id;
                $summary['partners']['added']++;
            }
        }

        foreach (self::rows($pdo, 'PartnerRepair') as $r) {
            $partnerId = $partnerMap[$r['partnerId']] ?? null;
            if (! $partnerId) {
                $summary['repairs']['skipped']++;
                continue;
            }
            $total = ($r['totalAmount'] ?? null) !== null && (float) $r['totalAmount'] > 0 ? (float) $r['totalAmount'] : null;
            $found = PartnerRepair::where('partner_id', $partnerId)
                ->where('date', $r['date'])->where('device_type', $r['deviceType'])
                ->where('total_amount', $total)->first();
            $payload = [
                'device_type' => $r['deviceType'],
                'brand' => $r['brand'] ?? null,
                'customer_name' => $r['customerName'] ?? null,
                'issue_description' => $r['issueDescription'] ?? null,
                'total_amount' => $total,
                'paid_amount' => (float) ($r['paidAmount'] ?? 0),
                'date' => $r['date'],
                'notes' => $r['notes'] ?? null,
                'created_by_id' => $userMap[$r['createdById'] ?? ''] ?? $fallbackUser,
            ];
            if ($found) {
                $found->update($payload);
                $summary['repairs']['updated']++;
            } else {
                PartnerRepair::create($payload + ['partner_id' => $partnerId]);
                $summary['repairs']['added']++;
            }
        }

        foreach (self::rows($pdo, 'PartnerSettlement') as $r) {
            $partnerId = $partnerMap[$r['partnerId']] ?? null;
            if (! $partnerId) {
                $summary['settlements']['skipped']++;
                continue;
            }
            $found = PartnerSettlement::where('partner_id', $partnerId)
                ->where('date', $r['date'])->where('amount', (float) $r['amount'])->first();
            if ($found) {
                $summary['settlements']['skipped']++;
                continue;
            }
            PartnerSettlement::create([
                'partner_id' => $partnerId,
                'amount' => (float) $r['amount'],
                'date' => $r['date'],
                'notes' => $r['notes'] ?? null,
                'created_by_id' => $userMap[$r['createdById'] ?? ''] ?? $fallbackUser,
            ]);
            $summary['settlements']['added']++;
        }

        // ===== 6) المصروفات =====
        foreach (self::rows($pdo, 'Expense') as $r) {
            $createdBy = $userMap[$r['createdById'] ?? ''] ?? $fallbackUser;
            $found = Expense::where('date', $r['date'])->where('amount', (float) $r['amount'])
                ->where('description', $r['description'])->first();
            if ($found) {
                $summary['expenses']['skipped']++;
                continue;
            }
            Expense::create([
                'amount' => (float) $r['amount'],
                'description' => $r['description'],
                'category' => $r['category'] ?? 'other',
                'date' => $r['date'],
                'created_by_id' => $createdBy,
            ]);
            $summary['expenses']['added']++;
        }

        // ===== 7) السجلات المرتبطة بالطلبات (ملاحظات/تقييمات/قطع/إشعارات/سجلات) =====
        foreach (self::rows($pdo, 'Review') as $r) {
            $reqId = $reqMap[$r['requestId']] ?? null;
            $custId = $userMap[$r['customerId']] ?? null;
            if (! $reqId || ! $custId || Review::where('request_id', $reqId)->exists()) {
                $summary['reviews']['skipped']++;
                continue;
            }
            Review::create([
                'request_id' => $reqId, 'customer_id' => $custId,
                'rating' => (int) $r['rating'], 'comment' => $r['comment'],
                'status' => $r['status'] ?? 'PENDING', 'admin_reply' => $r['adminReply'] ?? null,
            ]);
            $summary['reviews']['added']++;
        }

        foreach (self::rows($pdo, 'Notification') as $r) {
            $userId = $userMap[$r['userId']] ?? null;
            if (! $userId) {
                $summary['notifications']['skipped']++;
                continue;
            }
            NoorNotification::create([
                'user_id' => $userId, 'type' => $r['type'] ?? 'INFO',
                'title' => $r['title'] ?? '', 'message' => $r['message'] ?? '',
                'request_id' => $reqMap[$r['requestId'] ?? ''] ?? null,
                'is_read' => (bool) ($r['read'] ?? false),
                'created_at' => self::ms($r['createdAt'] ?? null) ?? now(),
            ]);
            $summary['notifications']['added']++;
        }

        foreach (self::rows($pdo, 'ActivityLog') as $r) {
            $userId = $userMap[$r['userId']] ?? null;
            if (! $userId) {
                $summary['activityLogs']['skipped']++;
                continue;
            }
            ActivityLog::create([
                'user_id' => $userId, 'action' => $r['action'] ?? 'LEGACY',
                'details' => $r['details'] ?? '', 'ip_address' => $r['ipAddress'] ?? null,
                'created_at' => self::ms($r['createdAt'] ?? null) ?? now(),
            ]);
            $summary['activityLogs']['added']++;
        }

        foreach (self::rows($pdo, 'CustomerNote') as $r) {
            $custId = $userMap[$r['customerId']] ?? null;
            $authorId = $userMap[$r['authorId']] ?? null;
            if (! $custId || ! $authorId || CustomerNote::where('customer_id', $custId)->where('content', $r['content'])->exists()) {
                $summary['customerNotes']['skipped']++;
                continue;
            }
            CustomerNote::create([
                'customer_id' => $custId, 'author_id' => $authorId, 'content' => $r['content'],
                'created_at' => self::ms($r['createdAt'] ?? null) ?? now(),
                'updated_at' => self::ms($r['updatedAt'] ?? null) ?? now(),
            ]);
            $summary['customerNotes']['added']++;
        }

        foreach (self::rows($pdo, 'RequestNote') as $r) {
            $reqId = $reqMap[$r['requestId']] ?? null;
            $authorId = $userMap[$r['authorId']] ?? null;
            if (! $reqId || ! $authorId || RequestNote::where('request_id', $reqId)->where('content', $r['content'])->exists()) {
                $summary['requestNotes']['skipped']++;
                continue;
            }
            RequestNote::create([
                'request_id' => $reqId, 'author_id' => $authorId, 'content' => $r['content'],
                'created_at' => self::ms($r['createdAt'] ?? null) ?? now(),
                'updated_at' => self::ms($r['updatedAt'] ?? null) ?? now(),
            ]);
            $summary['requestNotes']['added']++;
        }

        foreach (self::rows($pdo, 'UsedPart') as $r) {
            $reqId = $reqMap[$r['requestId']] ?? null;
            if (! $reqId || UsedPart::where('request_id', $reqId)->exists()) {
                $summary['usedParts']['skipped']++;
                continue;
            }
            UsedPart::create([
                'request_id' => $reqId,
                'inventory_item_id' => null, // القطع القديمة تُسجل بالاسم الخارجي لأمان المطابقة
                'custom_name' => $r['customName'] ?: 'قطعة من النظام القديم',
                'quantity' => (float) ($r['quantity'] ?? 1),
                'unit_price' => (float) ($r['unitPrice'] ?? 0),
            ]);
            $summary['usedParts']['added']++;
        }

        // ===== 8) إعدادات الموقع (اختياري) =====
        if ($withSettings) {
            $s = self::rows($pdo, 'Settings')[0] ?? null;
            if ($s) {
                Setting::current()->update([
                    'phone' => $s['phone'] ?? null,
                    'email' => $s['email'] ?? null,
                    'address' => $s['address'] ?? null,
                    'working_hours' => $s['workingHours'] ?? null,
                    'facebook_url' => $s['facebookUrl'] ?? null,
                    'instagram_url' => $s['instagramUrl'] ?? null,
                    'whatsapp_num' => $s['whatsappNum'] ?? null,
                    'receipt_title' => $s['receiptTitle'] ?? null,
                    'receipt_subtitle' => $s['receiptSubtitle'] ?? null,
                    'receipt_notes' => $s['receiptNotes'] ?? null,
                    'receipt_footer' => $s['receiptFooter'] ?? null,
                    'maintenance_reminder_enabled' => (bool) ($s['maintenanceReminderEnabled'] ?? false),
                    'maintenance_reminder_months' => (int) ($s['maintenanceReminderMonths'] ?? 6),
                    'sound_notification_enabled' => (bool) ($s['soundNotificationEnabled'] ?? false),
                    'sound_notification_url' => $s['soundNotificationUrl'] ?? null,
                ]);
            }
        }
    }

    // ============================================================
    // أدوات مساعدة
    // ============================================================

    private static function pdo(string $path): \PDO
    {
        $pdo = new \PDO('sqlite:'.$path, null, null, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = OFF');

        return $pdo;
    }

    /** كل صفوف جدول قديم (لو الجدول مش موجود يرجع فاضي) */
    private static function rows(\PDO $pdo, string $table): array
    {
        try {
            return $pdo->query("SELECT * FROM \"{$table}\"")->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    /** تحويل طابع Prisma (ملّي ثانية) إلى صيغة قاعدة البيانات */
    private static function ms(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_int($value) || (is_string($value) && preg_match('/^\d{10,}$/', (string) $value))) {
            return Carbon::createFromTimestamp((int) ((int) $value / 1000))->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }

    /** JSON من القديم → مصفوفة (مهما كانت الصيغة) */
    private static function jsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode((string) ($value ?? '[]'), true);

        return is_array($decoded) ? $decoded : [];
    }

    /** تصفير العدادات التلقائية بعد المسح (وضع الاستبدال) */
    private static function resetSequences(): void
    {
        try {
            DB::statement("DELETE FROM sqlite_sequence WHERE name IN ('users','service_requests','departments','device_types','inventory_items','used_parts','customer_notes','request_notes','reviews','notifications_custom','activity_logs','expenses','partner_technicians','partner_repairs','partner_settlements')");
        } catch (\Throwable) {
            // sqlite_sequence مش موجود — عادي
        }
    }
}
