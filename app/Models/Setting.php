<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Setting extends Model
{
    protected $fillable = [
        'phone', 'email', 'address', 'working_hours', 'facebook_url', 'instagram_url', 'whatsapp_num',
        'receipt_title', 'receipt_subtitle', 'receipt_notes', 'receipt_footer',
        'maintenance_reminder_enabled', 'maintenance_reminder_months',
        'sound_notification_enabled', 'sound_notification_url',
    ];

    protected function casts(): array
    {
        return [
            'maintenance_reminder_enabled' => 'boolean',
            'maintenance_reminder_months' => 'integer',
            'sound_notification_enabled' => 'boolean',
        ];
    }

    /** الصف الوحيد من الإعدادات */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], []);
    }

    public function whatsappLink(): string
    {
        $num = $this->whatsapp_num ?: $this->phone;
        return 'https://wa.me/2'.ltrim((string) $num, '0');
    }
}
