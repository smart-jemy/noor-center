<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    public const CATEGORY_LABELS = [
        'rent' => 'إيجار',
        'utilities' => 'كهرباء ومياه',
        'salaries' => 'رواتب',
        'supplies' => 'مشتريات',
        'transport' => 'نقل',
        'other' => 'أخرى',
    ];

    protected $fillable = ['amount', 'description', 'category', 'date', 'created_by_id'];

    protected function casts(): array
    {
        return ['amount' => 'float', 'date' => 'date:Y-m-d'];
    }

    public function categoryLabel(): string
    {
        return self::CATEGORY_LABELS[$this->category] ?? $this->category;
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
