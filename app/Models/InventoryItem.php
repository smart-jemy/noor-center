<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    public const CATEGORY_LABELS = [
        'parts' => 'قطع غيار',
        'tools' => 'أدوات',
        'consumables' => 'مواد استهلاكية',
        'other' => 'أخرى',
    ];

    protected $fillable = [
        'name', 'category', 'brand', 'part_number', 'quantity', 'unit',
        'cost_price', 'sell_price', 'min_quantity', 'location', 'notes', 'department_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'cost_price' => 'float',
            'sell_price' => 'float',
            'min_quantity' => 'float',
        ];
    }

    public function categoryLabel(): string
    {
        return self::CATEGORY_LABELS[$this->category] ?? $this->category;
    }

    public function isLowStock(): bool
    {
        return $this->quantity <= $this->min_quantity;
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function usedParts(): HasMany
    {
        return $this->hasMany(UsedPart::class);
    }
}
