<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerRepair extends Model
{
    protected $fillable = [
        'partner_id', 'device_type', 'brand', 'customer_name', 'issue_description',
        'total_amount', 'paid_amount', 'date', 'notes', 'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'float',
            'paid_amount' => 'float',
            'date' => 'date:Y-m-d',
        ];
    }

    /** السعر لسه محددش؟ — الاستقبال بيستلم الجهاز الأول ويحدد السعر بعدين */
    public function hasPrice(): bool
    {
        return $this->total_amount !== null && (float) $this->total_amount > 0;
    }

    public function deferredAmount(): float
    {
        // بدون سعر: مفيش آجل محسوب لحد ما السعر يتحدد
        if (! $this->hasPrice()) {
            return 0.0;
        }

        return round((float) $this->total_amount - $this->paid_amount, 2);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(PartnerTechnician::class, 'partner_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
