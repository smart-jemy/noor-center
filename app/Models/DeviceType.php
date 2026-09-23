<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceType extends Model
{
    protected $fillable = ['name', 'icon', 'is_active', 'sort_order', 'accessories'];

    protected function casts(): array
    {
        return [
            'accessories' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
