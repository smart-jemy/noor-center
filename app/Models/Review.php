<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Review extends Model
{
    public const STATUS_LABELS = [
        'PENDING' => 'في الانتظار',
        'APPROVED' => 'مقبول',
        'REJECTED' => 'مرفوض',
    ];

    protected $fillable = ['request_id', 'customer_id', 'rating', 'comment', 'status', 'admin_reply'];

    protected function casts(): array
    {
        return ['rating' => 'integer'];
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'APPROVED' => 'bg-green-100 text-green-800 border-green-200',
            'REJECTED' => 'bg-red-100 text-red-800 border-red-200',
            default => 'bg-amber-100 text-amber-800 border-amber-200',
        };
    }

    public function starsHtml(): string
    {
        return str_repeat('★', $this->rating).str_repeat('☆', 5 - $this->rating);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'request_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
