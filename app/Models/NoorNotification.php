<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoorNotification extends Model
{
    protected $table = 'notifications_custom';

    public const TYPE_ICONS = [
        'NEW_REQUEST' => 'wrench',
        'NEW_REVIEW' => 'star',
        'REQUEST_UPDATED' => 'refresh',
        'NEW_USER' => 'user-plus',
        'TECHNICIAN_ASSIGNED' => 'hard-hat',
    ];

    protected $fillable = ['user_id', 'type', 'title', 'message', 'request_id', 'is_read'];

    protected function casts(): array
    {
        return ['is_read' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function icon(): string
    {
        return self::TYPE_ICONS[$this->type] ?? 'bell';
    }

    public function timeAgo(): string
    {
        $diff = now()->diffInMinutes($this->created_at);

        if ($diff < 1) return 'الآن';
        if ($diff < 60) return "منذ {$diff} دقيقة";
        if ($diff < 1440) return 'منذ '.round($diff / 60).' ساعة';
        return 'منذ '.round($diff / 1440).' يوم';
    }
}
