<?php

namespace App\Console\Commands;

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
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * ترحيل البيانات من قاعدة بيانات النظام القديم (Next.js + Prisma + SQLite)
 * الاستخدام: php artisan noor:migrate-old {مسار القاعدة القديمة}
 */
class MigrateOldData extends Command
{
    protected $signature = 'noor:migrate-old {db : مسار ملف قاعدة البيانات القديمة (custom.db)} {--fresh : تصفير القاعدة الحالية أولاً}';

    protected $description = 'ترحيل كل البيانات من قاعدة النظام القديم (Next.js) إلى Laravel';

    private array $userMap = [];      // oldId => newId
    private array $deptMap = [];      // oldId => newId
    private array $requestMap = [];   // oldId => newId
    private array $itemMap = [];      // oldId => newId
    private int $warnings = 0;

    public function handle(): int
    {
        $dbPath = $this->argument('db');

        if (! file_exists($dbPath)) {
            $this->error("الملف غير موجود: {$dbPath}");
            return 1;
        }

        $pdo = new \PDO('sqlite:'.$dbPath);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        if ($this->option('fresh')) {
            $this->info('تصفير القاعدة الحالية...');
            Artisan::call('migrate:fresh', ['--force' => true]);
        }

        $this->info('⬆ بدء ترحيل بيانات مركز نور من النظام القديم...');

        $this->migrateSettings($pdo);
        $this->migrateDepartments($pdo);
        $this->migrateUsers($pdo);
        $this->migrateDeviceTypes($pdo);
        $this->migrateRequests($pdo);
        $this->migrateInventory($pdo);
        $this->migrateUsedParts($pdo);
        $this->migrateReviews($pdo);
        $this->migrateExpenses($pdo);
        $this->migratePartners($pdo);
        $this->migrateNotifications($pdo);
        $this->migrateNotes($pdo);
        $this->migrateActivity($pdo);

        $this->newLine();
        $this->info('✅ تم الترحيل بنجاح!');
        $this->info("   • المستخدمون: ".count($this->userMap));
        $this->info("   • الأقسام: ".count($this->deptMap));
        $this->info("   • الطلبات: ".count($this->requestMap));
        $this->info("   • أصناف المخزن: ".count($this->itemMap));
        if ($this->warnings > 0) {
            $this->warn("   ⚠ سجلات تم تخطيها: {$this->warnings}");
        }

        return 0;
    }

    private function rows(\PDO $pdo, string $table): array
    {
        return $pdo->query("SELECT * FROM \"{$table}\"")->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** تحويل تاريخ Prisma (epoch millis أو ISO) */
    private function ts($value): ?\Carbon\Carbon
    {
        if (! $value) return null;
        if (is_numeric($value)) {
            return \Carbon\Carbon::createFromTimestampMs((int) $value);
        }
        try {
            return \Carbon\Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function migrateSettings(\PDO $pdo): void
    {
        $this->info('⟳ ترحيل الإعدادات...');
        $s = $pdo->query('SELECT * FROM Settings LIMIT 1')->fetch(\PDO::FETCH_ASSOC);
        if (! $s) return;

        $settings = Setting::firstOrCreate([], []);
        $settings->update([
            'phone' => $s['phone'] ?: $settings->phone,
            'email' => $s['email'] ?: $settings->email,
            'address' => $s['address'] ?: $settings->address,
            'working_hours' => $s['workingHours'] ?: $settings->working_hours,
            'facebook_url' => $s['facebookUrl'] ?: null,
            'instagram_url' => $s['instagramUrl'] ?: null,
            'whatsapp_num' => $s['whatsappNum'] ?: null,
            'receipt_title' => $s['receiptTitle'] ?: $settings->receipt_title,
            'receipt_subtitle' => $s['receiptSubtitle'] ?: $settings->receipt_subtitle,
            'receipt_notes' => $s['receiptNotes'] ?: $settings->receipt_notes,
            'receipt_footer' => $s['receiptFooter'] ?: $settings->receipt_footer,
            'maintenance_reminder_enabled' => (bool) ($s['maintenanceReminderEnabled'] ?? false),
            'maintenance_reminder_months' => (int) ($s['maintenanceReminderMonths'] ?? 6),
            'sound_notification_enabled' => (bool) ($s['soundNotificationEnabled'] ?? false),
            'sound_notification_url' => $s['soundNotificationUrl'] ?: null,
        ]);
    }

    private function migrateDepartments(\PDO $pdo): void
    {
        $this->info('⟳ ترحيل الأقسام...');
        foreach ($this->rows($pdo, 'Department') as $d) {
            $dept = Department::firstOrCreate(
                ['name' => $d['name']],
                [
                    'icon' => $d['icon'] ?: 'layers',
                    'device_types' => json_decode($d['deviceTypes'] ?: '[]', true) ?: [],
                    'description' => $d['description'] ?: null,
                    'is_active' => (bool) $d['isActive'],
                ]
            );
            $this->deptMap[$d['id']] = $dept->id;
        }
    }

    private function migrateUsers(\PDO $pdo): void
    {
        $this->info('⟳ ترحيل المستخدمين (كلمات المرور القديمة هتشتغل وتتحدث تلقائياً)...');
        foreach ($this->rows($pdo, 'User') as $u) {
            $existing = User::where('phone', $u['phone'])->first();
            if ($existing) {
                $this->userMap[$u['id']] = $existing->id;
                $existing->update([
                    'name' => $u['name'] ?: $existing->name,
                    'role' => $u['role'] ?: $existing->role,
                    'specialty' => $u['specialty'] ?: null,
                    'is_active' => (bool) ($u['isActive'] ?? 1),
                    'loyalty_points' => (int) ($u['loyaltyPoints'] ?? 0),
                    'total_spent' => (float) ($u['totalSpent'] ?? 0),
                    'legacy_password' => $u['passwordHash'] ?: null,
                ]);
                continue;
            }

            $user = User::create([
                'name' => $u['name'] ?: 'بدون اسم',
                'phone' => $u['phone'],
                'password' => 'noor-migration-placeholder',
                'legacy_password' => $u['passwordHash'] ?: null,
                'role' => in_array($u['role'], array_keys(User::ROLES)) ? $u['role'] : 'CUSTOMER',
                'specialty' => $u['specialty'] ?: null,
                'is_active' => (bool) ($u['isActive'] ?? 1),
                'loyalty_points' => (int) ($u['loyaltyPoints'] ?? 0),
                'total_spent' => (float) ($u['totalSpent'] ?? 0),
                'created_at' => $this->ts($u['createdAt']) ?? now(),
                'updated_at' => $this->ts($u['updatedAt']) ?? now(),
            ]);

            if (! $u['passwordHash']) {
                $user->update(['password' => '123456']);
            }

            $this->userMap[$u['id']] = $user->id;
        }

        // علاقات departmentId + managedBy بعد إنشاء الكل
        foreach ($this->rows($pdo, 'User') as $u) {
            if (! isset($this->userMap[$u['id']])) continue;

            $deptId = isset($this->deptMap[$u['departmentId']]) ? $this->deptMap[$u['departmentId']] : null;
            $managedBy = isset($this->userMap[$u['managedById']]) ? $this->userMap[$u['managedById']] : null;

            User::find($this->userMap[$u['id']])->update([
                'department_id' => $deptId,
                'managed_by' => $managedBy,
            ]);
        }
    }

    private function migrateDeviceTypes(\PDO $pdo): void
    {
        $this->info('⟳ ترحيل أنواع الأجهزة...');
        $rows = $this->rows($pdo, 'DeviceType');

        if (empty($rows)) {
            $devices = $pdo->query('SELECT DISTINCT deviceType FROM ServiceRequest')->fetchAll(\PDO::FETCH_COLUMN);
            $rows = array_map(fn ($name) => ['name' => $name, 'icon' => null, 'accessories' => '[]'], $devices);
        }

        $defaults = ['تكييف' => 'wind', 'غسالة' => 'washer', 'ثلاجة' => 'fridge', 'بوتاجاز' => 'flame', 'شاشة' => 'tv', 'دش' => 'dish', 'كاميرا' => 'camera'];
        $order = array_merge(array_keys($defaults), ['أخرى']);
        $sort = 1;

        foreach ($order as $name) {
            $found = array_values(array_filter($rows, fn ($r) => trim($r['name']) === $name));
            if ($found) {
                DeviceType::firstOrCreate(['name' => $name], [
                    'icon' => $defaults[$name] ?? 'wrench',
                    'sort_order' => $sort++,
                    'is_active' => true,
                    'accessories' => json_decode($found[0]['accessories'] ?? '[]', true) ?: [],
                ]);
            }
        }

        foreach ($rows as $r) {
            $name = trim($r['name']);
            if (in_array($name, $order)) continue;
            DeviceType::firstOrCreate(['name' => $name], [
                'icon' => 'wrench',
                'sort_order' => $sort++,
                'is_active' => true,
                'accessories' => json_decode($r['accessories'] ?? '[]', true) ?: [],
            ]);
        }
    }

    private function migrateRequests(\PDO $pdo): void
    {
        $this->info('⟳ ترحيل الطلبات...');
        foreach ($this->rows($pdo, 'ServiceRequest') as $r) {
            if (! isset($this->userMap[$r['customerId']])) {
                $this->warnings++;
                continue;
            }

            $request = ServiceRequest::create([
                'order_number' => $r['orderNumber'] ?: null,
                'customer_id' => $this->userMap[$r['customerId']],
                'device_type' => $r['deviceType'] ?: 'أخرى',
                'brand' => $r['brand'] ?: null,
                'issue_description' => $r['issueDescription'] ?: '—',
                'area' => array_key_exists($r['area'], ServiceRequest::AREAS) ? $r['area'] : '6_october',
                'address' => $r['address'] ?: '—',
                'phone' => $r['phone'] ?: '—',
                'preferred_date' => $r['preferredDate'] ?: now()->toDateString(),
                'preferred_time' => $r['preferredTime'] ?: '09:00-12:00',
                'photos' => json_decode($r['photos'] ?: '[]', true) ?: [],
                'status' => in_array($r['status'], ServiceRequest::STATUSES) ? $r['status'] : 'PENDING',
                'admin_notes' => $r['adminNotes'] ?: null,
                'price' => ($r['price'] !== '' && $r['price'] !== null) ? (float) $r['price'] : null,
                'paid_amount' => (float) ($r['paidAmount'] ?? 0),
                'assigned_technician_id' => isset($this->userMap[$r['assignedTechnicianId']]) ? $this->userMap[$r['assignedTechnicianId']] : null,
                'assigned_at' => $this->ts($r['assignedAt']),
                'completed_at' => $this->ts($r['completedAt']),
                'warranty_months' => (int) ($r['warrantyMonths'] ?? 0),
                'warranty_end_date' => $r['warrantyEndDate'] ?: null,
                'department_id' => isset($this->deptMap[$r['departmentId']]) ? $this->deptMap[$r['departmentId']] : null,
                'created_at' => $this->ts($r['createdAt']) ?? now(),
                'updated_at' => $this->ts($r['updatedAt']) ?? now(),
            ]);

            $this->requestMap[$r['id']] = $request->id;
        }

        // أرقام أوردر للطلبات القديمة اللي مالهاش
        $missing = ServiceRequest::whereNull('order_number')->orderBy('created_at')->get();
        foreach ($missing as $req) {
            $year = $req->created_at->format('Y');
            $seq = ServiceRequest::where('order_number', 'like', "% {$year}")->count() + 1;
            $req->update(['order_number' => 'NC '.str_pad((string) $seq, 4, '0', STR_PAD_LEFT)." {$year}"]);
        }
    }

    private function migrateInventory(\PDO $pdo): void
    {
        $this->info('⟳ ترحيل المخزون...');
        foreach ($this->rows($pdo, 'InventoryItem') as $i) {
            $item = InventoryItem::create([
                'name' => $i['name'] ?: 'صنف',
                'category' => array_key_exists($i['category'], InventoryItem::CATEGORY_LABELS) ? $i['category'] : 'other',
                'brand' => $i['brand'] ?: null,
                'part_number' => $i['partNumber'] ?: null,
                'quantity' => (float) ($i['quantity'] ?? 0),
                'unit' => $i['unit'] ?: 'قطعة',
                'cost_price' => (float) ($i['costPrice'] ?? 0),
                'sell_price' => (float) ($i['sellPrice'] ?? 0),
                'min_quantity' => (float) ($i['minQuantity'] ?? 0),
                'location' => $i['location'] ?: null,
                'notes' => $i['notes'] ?: null,
                'department_id' => isset($this->deptMap[$i['departmentId']]) ? $this->deptMap[$i['departmentId']] : null,
                'created_at' => $this->ts($i['createdAt']) ?? now(),
            ]);
            $this->itemMap[$i['id']] = $item->id;
        }
    }

    private function migrateUsedParts(\PDO $pdo): void
    {
        $this->info('⟳ ترحيل القطع المستخدمة...');
        foreach ($this->rows($pdo, 'UsedPart') as $p) {
            if (! isset($this->requestMap[$p['requestId']])) {
                $this->warnings++;
                continue;
            }

            UsedPart::create([
                'request_id' => $this->requestMap[$p['requestId']],
                'inventory_item_id' => isset($this->itemMap[$p['inventoryItemId']]) ? $this->itemMap[$p['inventoryItemId']] : null,
                'custom_name' => $p['customName'] ?: null,
                'quantity' => (float) ($p['quantity'] ?? 1),
                'unit_price' => (float) ($p['unitPrice'] ?? 0),
                'created_at' => $this->ts($p['createdAt']) ?? now(),
            ]);
        }
    }

    private function migrateReviews(\PDO $pdo): void
    {
        $this->info('⟳ ترحيل التقييمات...');
        foreach ($this->rows($pdo, 'Review') as $r) {
            if (! isset($this->requestMap[$r['requestId']]) || ! isset($this->userMap[$r['customerId']])) {
                $this->warnings++;
                continue;
            }

            Review::firstOrCreate([
                'request_id' => $this->requestMap[$r['requestId']],
            ], [
                'customer_id' => $this->userMap[$r['customerId']],
                'rating' => max(1, min(5, (int) $r['rating'])),
                'comment' => $r['comment'] ?: '—',
                'status' => in_array($r['status'], ['PENDING', 'APPROVED', 'REJECTED']) ? $r['status'] : 'PENDING',
                'admin_reply' => $r['adminReply'] ?: null,
                'created_at' => $this->ts($r['createdAt']) ?? now(),
            ]);
        }
    }

    private function migrateExpenses(\PDO $pdo): void
    {
        $this->info('⟳ ترحيل المصروفات...');
        foreach ($this->rows($pdo, 'Expense') as $e) {
            if (! isset($this->userMap[$e['createdById']])) {
                $adminId = User::where('role', 'ADMIN')->value('id');
                if (! $adminId) { $this->warnings++; continue; }
                $this->userMap[$e['createdById']] = $adminId;
            }

            Expense::create([
                'amount' => (float) $e['amount'],
                'description' => $e['description'] ?: 'مصروف',
                'category' => array_key_exists($e['category'], Expense::CATEGORY_LABELS) ? $e['category'] : 'other',
                'date' => $e['date'] ?: now()->toDateString(),
                'created_by_id' => $this->userMap[$e['createdById']],
                'created_at' => $this->ts($e['createdAt']) ?? now(),
            ]);
        }
    }

    private function migratePartners(\PDO $pdo): void
    {
        $this->info('⟳ ترحيل الشركاء...');
        $adminId = User::where('role', 'ADMIN')->value('id');

        foreach ($this->rows($pdo, 'PartnerTechnician') as $p) {
            $createdBy = isset($this->userMap[$p['createdById']]) ? $this->userMap[$p['createdById']] : $adminId;
            $partner = PartnerTechnician::create([
                'name' => $p['name'] ?: 'شريك',
                'phone' => $p['phone'] ?: null,
                'notes' => $p['notes'] ?: null,
                'credit_limit' => (float) ($p['creditLimit'] ?? 0),
                'is_active' => (bool) ($p['isActive'] ?? 1),
                'created_by_id' => $createdBy,
                'created_at' => $this->ts($p['createdAt']) ?? now(),
            ]);

            $repairs = $pdo->prepare('SELECT * FROM PartnerRepair WHERE partnerId = ?');
            $repairs->execute([$p['id']]);
            foreach ($repairs->fetchAll(\PDO::FETCH_ASSOC) as $pr) {
                PartnerRepair::create([
                    'partner_id' => $partner->id,
                    'device_type' => $pr['deviceType'] ?: 'أخرى',
                    'brand' => $pr['brand'] ?: null,
                    'customer_name' => $pr['customerName'] ?: null,
                    'issue_description' => $pr['issueDescription'] ?: null,
                    'total_amount' => (float) ($pr['totalAmount'] ?? 0),
                    'paid_amount' => (float) ($pr['paidAmount'] ?? 0),
                    'date' => $pr['date'] ?: now()->toDateString(),
                    'notes' => $pr['notes'] ?: null,
                    'created_by_id' => isset($this->userMap[$pr['createdById']]) ? $this->userMap[$pr['createdById']] : $createdBy,
                    'created_at' => $this->ts($pr['createdAt']) ?? now(),
                ]);
            }

            $setts = $pdo->prepare('SELECT * FROM PartnerSettlement WHERE partnerId = ?');
            $setts->execute([$p['id']]);
            foreach ($setts->fetchAll(\PDO::FETCH_ASSOC) as $ps) {
                PartnerSettlement::create([
                    'partner_id' => $partner->id,
                    'amount' => (float) ($ps['amount'] ?? 0),
                    'date' => $ps['date'] ?: now()->toDateString(),
                    'notes' => $ps['notes'] ?: null,
                    'created_by_id' => isset($this->userMap[$ps['createdById']]) ? $this->userMap[$ps['createdById']] : $createdBy,
                    'created_at' => $this->ts($ps['createdAt']) ?? now(),
                ]);
            }
        }
    }

    private function migrateNotifications(\PDO $pdo): void
    {
        $this->info('⟳ ترحيل الإشعارات...');
        foreach ($this->rows($pdo, 'Notification') as $n) {
            if (! isset($this->userMap[$n['userId']])) {
                $this->warnings++;
                continue;
            }

            NoorNotification::create([
                'user_id' => $this->userMap[$n['userId']],
                'type' => $n['type'] ?: 'REQUEST_UPDATED',
                'title' => $n['title'] ?: 'إشعار',
                'message' => $n['message'] ?: '—',
                'request_id' => isset($this->requestMap[$n['requestId']]) ? $this->requestMap[$n['requestId']] : null,
                'is_read' => (bool) ($n['read'] ?? 0),
                'created_at' => $this->ts($n['createdAt']) ?? now(),
            ]);
        }
    }

    private function migrateNotes(\PDO $pdo): void
    {
        $this->info('⟳ ترحيل الملاحظات...');

        foreach ($this->rows($pdo, 'CustomerNote') as $n) {
            if (! isset($this->userMap[$n['customerId']]) || ! isset($this->userMap[$n['authorId']])) {
                $this->warnings++;
                continue;
            }
            CustomerNote::create([
                'customer_id' => $this->userMap[$n['customerId']],
                'author_id' => $this->userMap[$n['authorId']],
                'content' => $n['content'] ?: '—',
                'created_at' => $this->ts($n['createdAt']) ?? now(),
            ]);
        }

        foreach ($this->rows($pdo, 'RequestNote') as $n) {
            if (! isset($this->requestMap[$n['requestId']]) || ! isset($this->userMap[$n['authorId']])) {
                $this->warnings++;
                continue;
            }
            RequestNote::create([
                'request_id' => $this->requestMap[$n['requestId']],
                'author_id' => $this->userMap[$n['authorId']],
                'content' => $n['content'] ?: '—',
                'created_at' => $this->ts($n['createdAt']) ?? now(),
            ]);
        }
    }

    private function migrateActivity(\PDO $pdo): void
    {
        $this->info('⟳ ترحيل سجل النشاط...');
        foreach ($this->rows($pdo, 'ActivityLog') as $l) {
            if (! isset($this->userMap[$l['userId']])) {
                $this->warnings++;
                continue;
            }

            ActivityLog::create([
                'user_id' => $this->userMap[$l['userId']],
                'action' => $l['action'] ?: 'LOGIN',
                'details' => $l['details'] ?: '—',
                'ip_address' => $l['ipAddress'] ?: null,
                'created_at' => $this->ts($l['createdAt']) ?? now(),
            ]);
        }
    }
}
