<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_CUSTOMER = 'CUSTOMER';
    public const ROLE_ADMIN = 'ADMIN';
    public const ROLE_RECEPTION = 'RECEPTION';
    public const ROLE_TECHNICIAN = 'TECHNICIAN';
    public const ROLE_DEPARTMENT_MANAGER = 'DEPARTMENT_MANAGER';

    public const ROLES = [
        self::ROLE_CUSTOMER => 'عميل',
        self::ROLE_ADMIN => 'مدير النظام',
        self::ROLE_RECEPTION => 'استقبال',
        self::ROLE_TECHNICIAN => 'فني صيانة',
        self::ROLE_DEPARTMENT_MANAGER => 'مدير قسم',
    ];

    protected $fillable = [
        'name', 'phone', 'password', 'legacy_password', 'role', 'specialty',
        'department_id', 'managed_by', 'is_active', 'loyalty_points', 'total_spent',
    ];

    protected $hidden = ['password', 'legacy_password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'loyalty_points' => 'integer',
            'total_spent' => 'float',
        ];
    }

    /** التحقق من كلمة مرور النظام القديم (scrypt) وترقيتها تلقائياً */
    public function verifyAndUpgradeLegacyPassword(string $plain): bool
    {
        if (! $this->legacy_password) {
            return false;
        }

        // salt:hash — نفس طريقة Node.js scryptSync(password, salt, 64)
        [$saltHex, $hashHex] = explode(':', $this->legacy_password, 2);

        if (! preg_match('/^[0-9a-f]+$/i', $saltHex) || ! preg_match('/^[0-9a-f]+$/i', $hashHex)) {
            return false;
        }

        // التحقق عبر Python (hashlib.scrypt) — جسر ترحيل
        $saltHex = escapeshellarg($saltHex);
        $hashHex = escapeshellarg($hashHex);
        $plainPy = escapeshellarg($plain);
        $cmd = "python3 -c \"import hashlib,sys; salt=bytes.fromhex(sys.argv[1]); expected=bytes.fromhex(sys.argv[2]); sys.exit(0 if hashlib.scrypt(sys.argv[3].encode(), salt=salt, n=16384, r=8, p=1, dklen=64)==expected else 1)\" $saltHex $hashHex $plainPy";
        exec($cmd, $out, $code);

        if ($code === 0) {
            // ترقية فورية لـ bcrypt وتنظيف القديم
            $this->update(['password' => $plain, 'legacy_password' => null]);
            return true;
        }

        return false;
    }

    // ===== Helpers =====

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    public function isAdmin(): bool { return $this->role === self::ROLE_ADMIN; }
    public function isStaff(): bool { return $this->role !== self::ROLE_CUSTOMER; }

    public function initials(): string
    {
        return mb_substr($this->name, 0, 1);
    }

    // ===== Relations =====

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'managed_by');
    }

    public function managedTechnicians(): HasMany
    {
        return $this->hasMany(self::class, 'managed_by');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'customer_id');
    }

    public function assignedRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'assigned_technician_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'customer_id');
    }

    public function customerNotes(): HasMany
    {
        return $this->hasMany(CustomerNote::class, 'customer_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(NoorNotification::class);
    }

    public function unreadNotificationsCount(): int
    {
        return $this->notifications()->where('is_read', false)->count();
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
