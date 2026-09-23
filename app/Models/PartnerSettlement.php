<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerSettlement extends Model
{
    protected $fillable = ['partner_id', 'amount', 'date', 'notes', 'created_by_id'];

    protected function casts(): array
    {
        return ['amount' => 'float', 'date' => 'date:Y-m-d'];
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
