<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ServiceRequest extends Model
{
    /** دورة حياة الطلب: كشف ← تواصل ← تأكيد ← تنفيذ ← جاهز ← تسليم (+ ملغى / مرتجع) */
    public const STATUSES = ['PENDING', 'CONTACTED', 'CONFIRMED', 'IN_PROGRESS', 'READY', 'COMPLETED', 'CANCELLED', 'RETURNED'];

    /** الحالات النشطة (قابلة للتعديل للاستقبال) — التسليم/الإلغاء حالة نهائية */
    public const ACTIVE_STATUSES = ['PENDING', 'CONTACTED', 'CONFIRMED', 'IN_PROGRESS', 'READY', 'RETURNED'];

    /** تسلسل سير الحياة للعرض (الخط الزمني للطلب) */
    public const LIFE_CYCLE = ['PENDING', 'CONTACTED', 'CONFIRMED', 'IN_PROGRESS', 'READY', 'COMPLETED'];

    public const STATUS_LABELS = [
        'PENDING' => 'كشف',
        'CONTACTED' => 'تواصل',
        'CONFIRMED' => 'تأكيد',
        'IN_PROGRESS' => 'تنفيذ',
        'READY' => 'جاهز',
        'COMPLETED' => 'تسليم',
        'CANCELLED' => 'ملغى',
        'RETURNED' => 'مرتجع',
    ];

    public const STATUS_COLORS = [
        'PENDING' => 'bg-amber-100 text-amber-800 border-amber-200',
        'CONTACTED' => 'bg-cyan-100 text-cyan-800 border-cyan-200',
        'CONFIRMED' => 'bg-blue-100 text-blue-800 border-blue-200',
        'IN_PROGRESS' => 'bg-purple-100 text-purple-800 border-purple-200',
        'READY' => 'bg-teal-100 text-teal-800 border-teal-200',
        'COMPLETED' => 'bg-green-100 text-green-800 border-green-200',
        'CANCELLED' => 'bg-red-100 text-red-800 border-red-200',
        'RETURNED' => 'bg-orange-100 text-orange-800 border-orange-300',
    ];

    public const STATUS_DOT = [
        'PENDING' => 'bg-amber-500',
        'CONTACTED' => 'bg-cyan-500',
        'CONFIRMED' => 'bg-blue-500',
        'IN_PROGRESS' => 'bg-purple-500',
        'READY' => 'bg-teal-500',
        'COMPLETED' => 'bg-green-500',
        'CANCELLED' => 'bg-red-500',
        'RETURNED' => 'bg-orange-500',
    ];

    /** وضع الطلب: عادي / مستعجل / طوارئ — بيحدد مهلة التسليم */
    public const URGENCY_LABELS = [
        'normal' => 'عادي',
        'urgent' => 'مستعجل',
        'emergency' => 'طوارئ',
    ];

    /** مهلة التسليم بالساعات حسب وضع الطلب (SLA) — طوارئ 24س / مستعجل 48س / عادي 7 أيام */
    public const URGENCY_SLA_HOURS = [
        'emergency' => 24,
        'urgent' => 48,
        'normal' => 168,
    ];

    public const URGENCY_COLORS = [
        'normal' => 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-800/60 dark:text-slate-300 dark:border-slate-600',
        'urgent' => 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-700',
        'emergency' => 'bg-red-100 text-red-800 border-red-300 dark:bg-red-950/50 dark:text-red-300 dark:border-red-700',
    ];

    public const URGENCY_DOTS = [
        'normal' => 'bg-slate-400',
        'urgent' => 'bg-amber-500',
        'emergency' => 'bg-red-500',
    ];

    public const PAYMENT_LABELS = [
        'cash' => 'كاش',
        'transfer' => 'تحويل',
    ];

    public const AREAS = [
        '6_october' => '6 أكتوبر',
        'pyramids_gardens' => 'حدائق الأهرام',
    ];

    public const TIME_SLOTS = [
        '09:00-12:00' => 'صباحاً (9 - 12)',
        '12:00-15:00' => 'ظهراً (12 - 3)',
        '15:00-18:00' => 'عصراً (3 - 6)',
        '18:00-21:00' => 'مساءً (6 - 9)',
    ];

    public const IMMEDIATE = 'فوري';

    protected $fillable = [
        'order_number', 'customer_id', 'device_type', 'brand', 'issue_description',
        'area', 'address', 'phone', 'preferred_date', 'preferred_time', 'photos',
        'status', 'admin_notes', 'price', 'paid_amount', 'assigned_technician_id',
        'assigned_at', 'completed_at', 'warranty_months', 'warranty_end_date', 'department_id',
        'urgency', 'payment_method', 'entered_at', 'expected_exit_at',
        'is_returned', 'returned_at', 'return_reason', 'return_count',
    ];

    protected function casts(): array
    {
        return [
            'photos' => 'array',
            'preferred_date' => 'date:Y-m-d',
            'price' => 'float',
            'paid_amount' => 'float',
            'warranty_months' => 'integer',
            'warranty_end_date' => 'date:Y-m-d',
            'assigned_at' => 'datetime',
            'completed_at' => 'datetime',
            'entered_at' => 'datetime',
            'expected_exit_at' => 'datetime',
            'returned_at' => 'datetime',
            'is_returned' => 'boolean',
            'return_count' => 'integer',
        ];
    }

    // ===== Helpers =====

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? '';
    }

    public function statusDot(): string
    {
        return self::STATUS_DOT[$this->status] ?? 'bg-gray-400';
    }

    public function areaLabel(): string
    {
        if ($this->area === null || $this->area === '') {
            return 'بدون منطقة';
        }

        return self::AREAS[$this->area] ?? $this->area;
    }

    public function timeSlotLabel(): string
    {
        if ($this->preferred_time === self::IMMEDIATE) {
            return 'فوري';
        }
        if ($this->preferred_time === null || $this->preferred_time === '') {
            return 'بدون ميعاد';
        }

        return self::TIME_SLOTS[$this->preferred_time] ?? $this->preferred_time;
    }

    /** صيانة فورية؟ */
    public function isImmediate(): bool
    {
        return $this->preferred_time === self::IMMEDIATE;
    }

    // ===== الاستعجال ومهلة التسليم (SLA) =====

    public function urgencyLabel(): string
    {
        return self::URGENCY_LABELS[$this->urgency] ?? 'عادي';
    }

    public function urgencyColor(): string
    {
        return self::URGENCY_COLORS[$this->urgency] ?? self::URGENCY_COLORS['normal'];
    }

    public function urgencyDot(): string
    {
        return self::URGENCY_DOTS[$this->urgency] ?? 'bg-slate-400';
    }

    /** مهلة التسليم بالساعات حسب وضع الطلب */
    public function slaHours(): int
    {
        return self::URGENCY_SLA_HOURS[$this->urgency] ?? 168;
    }

    /** ميعاد الخروج المتوقع (يحسب من ميعاد الدخول + مهلة الاستعجال) */
    public function slaDeadline(): Carbon
    {
        if ($this->expected_exit_at) {
            return Carbon::parse($this->expected_exit_at);
        }

        return Carbon::parse($this->entered_at ?? $this->created_at)->addHours($this->slaHours());
    }

    /** المتبقي من المهلة (سالب = تجاوز) بالساعات */
    public function slaRemainingHours(): float
    {
        return now()->floatDiffInHours($this->slaDeadline(), false); // موجب = فاضل، سالب = متأخر
    }

    /** المتبقي بصيغة نصية: "فاضل 5 ساعات" أو "متأخر 3 ساعات" */
    public function slaRemainingLabel(): string
    {
        $h = $this->slaRemainingHours();
        if ($h >= 0) {
            if ($h < 1) {
                return 'فاضل '.max(1, (int) round($h * 60)).' دقيقة';
            }

            return 'فاضل '.round($h).' ساعة';
        }
        $late = abs($h);
        if ($late < 24) {
            return 'متأخر '.round($late).' ساعة';
        }
        $days = floor($late / 24);

        return 'متأخر '.$days.($days == 1 ? ' يوم' : ($days == 2 ? ' يومين' : ' أيام'));
    }

    /** نسبة استهلاك المهلة (0→1، أكبر من 1 يعني تجاوز) */
    public function slaProgress(): float
    {
        $total = $this->slaHours();
        if ($total <= 0) {
            return 0;
        }
        $elapsed = Carbon::parse($this->entered_at ?? $this->created_at)->floatDiffInHours(now());

        return max(0, $elapsed / $total);
    }

    /** طريقة الدفع (كاش/تحويل) */
    public function paymentLabel(): string
    {
        return self::PAYMENT_LABELS[$this->payment_method] ?? '—';
    }

    /** الوقت النسبي منذ إنشاء الطلب — زي الأصل (منذ X دقيقة/ساعة/يوم) */
    public function timeAgo(): string
    {
        $diff = now()->diffInSeconds($this->created_at);

        if ($diff < 60) {
            return 'الآن';
        }
        if ($diff < 3600) {
            return 'منذ '.floor($diff / 60).' دقيقة';
        }
        if ($diff < 86400) {
            return 'منذ '.floor($diff / 3600).' ساعة';
        }
        $days = floor($diff / 86400);

        return 'منذ '.$days.($days == 1 ? ' يوم' : ($days == 2 ? ' يومين' : ' أيام'));
    }

    /**
     * متأخر؟ — بيتحسب حسب وضع الطلب (طوارئ/مستعجل/عادي) ومهلة التسليم
     * مش بس لأن له ميعاد دخول وخروج — اللي يحدد هو استهلاك مهلة الاستعجال
     */
    public function isOverdue(): bool
    {
        if (in_array($this->status, ['COMPLETED', 'CANCELLED'])) {
            return false;
        }

        return now()->gt($this->slaDeadline());
    }

    /** المرحلة التالية في سير حياة الطلب (للأزرار السريعة) — null لو حالة نهائية */
    public function nextStatus(): ?string
    {
        return match ($this->status) {
            'PENDING' => 'CONTACTED',
            'CONTACTED' => 'CONFIRMED',
            'CONFIRMED' => 'IN_PROGRESS',
            'IN_PROGRESS' => 'READY',
            'READY' => 'COMPLETED',
            'RETURNED' => 'IN_PROGRESS',
            default => null,
        };
    }

    /** الخطوة الحالية داخل دورة الحياة (0-based) — للحالات خارج الدورة يرجع null */
    public function lifeCycleStep(): ?int
    {
        $step = array_search($this->status, self::LIFE_CYCLE, true);

        return $step === false ? null : (int) $step;
    }

    /** حالة إنذار التأخير حسب الاستعجال: emergency/urgent/normal/none */
    public function overdueSeverity(): string
    {
        if (! $this->isOverdue()) {
            return 'none';
        }

        return $this->urgency ?: 'normal';
    }

    /** المبلغ المتبقي (آجل) — التحصيل من العملاء بكامل السعر دائماً فيرجع صفر */
    public function deferredAmount(): float
    {
        if (in_array($this->status, ['COMPLETED'])) {
            return 0.0;
        }

        return max(0, (float) $this->price - (float) $this->paid_amount);
    }

    public function hasWarranty(): bool
    {
        return $this->warranty_months > 0 && $this->warranty_end_date !== null;
    }

    public function warrantyActive(): bool
    {
        return $this->hasWarranty()
            && Carbon::parse($this->warranty_end_date)->isFuture();
    }

    public function canBeReviewed(): bool
    {
        return $this->status === 'COMPLETED' && ! $this->review;
    }

    // ===== Relations =====

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function assignedTechnician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_technician_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class, 'request_id');
    }

    public function usedParts(): HasMany
    {
        return $this->hasMany(UsedPart::class, 'request_id');
    }

    public function internalNotes(): HasMany
    {
        return $this->hasMany(RequestNote::class, 'request_id')->latest();
    }

    public function partsTotal(): float
    {
        return (float) $this->usedParts()->sum(\DB::raw('quantity * unit_price'));
    }
}
