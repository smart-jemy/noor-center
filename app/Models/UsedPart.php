<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsedPart extends Model
{
    protected $fillable = ['request_id', 'inventory_item_id', 'custom_name', 'quantity', 'unit_price'];

    protected function casts(): array
    {
        return ['quantity' => 'float', 'unit_price' => 'float'];
    }

    public function total(): float
    {
        return $this->quantity * $this->unit_price;
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'request_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function name(): string
    {
        return $this->custom_name ?: ($this->inventoryItem?->name ?? '—');
    }
}
