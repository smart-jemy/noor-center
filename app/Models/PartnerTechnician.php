<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartnerTechnician extends Model
{
    protected $fillable = ['name', 'phone', 'notes', 'credit_limit', 'is_active', 'created_by_id'];

    protected function casts(): array
    {
        return ['credit_limit' => 'float', 'is_active' => 'boolean'];
    }

    public function repairs(): HasMany
    {
        return $this->hasMany(PartnerRepair::class, 'partner_id');
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(PartnerSettlement::class, 'partner_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /** إجمالي الآجل المستحق على الشريك = (مجاميع الصيانات - المدفوع) - تسويات */
    public function balance(): float
    {
        $total = (float) $this->repairs()->sum('total_amount');
        $paid = (float) $this->repairs()->sum('paid_amount');
        $settled = (float) $this->settlements()->sum('amount');

        return round($total - $paid - $settled, 2);
    }

    /** هل تجاوز حد الآجل؟ */
    public function overLimit(): bool
    {
        return $this->credit_limit > 0 && $this->balance() > $this->credit_limit;
    }

    public function repairCounts(): int
    {
        return $this->repairs()->count();
    }
}
