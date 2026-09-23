<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\DeviceType;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // الإعدادات الافتراضية
        Setting::query()->firstOrCreate([], []);

        // الأدمن الافتراضي
        User::updateOrCreate(
            ['phone' => '01000000000'],
            [
                'name' => 'مدير النظام',
                'password' => 'admin123',
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ]
        );

        // أنواع الأجهزة الافتراضية
        $devices = [
            ['name' => 'تكييف', 'icon' => 'wind', 'sort_order' => 1],
            ['name' => 'غسالة', 'icon' => 'washing-machine', 'sort_order' => 2],
            ['name' => 'ثلاجة', 'icon' => 'refrigerator', 'sort_order' => 3],
            ['name' => 'بوتاجاز', 'icon' => 'flame', 'sort_order' => 4],
            ['name' => 'شاشة', 'icon' => 'tv', 'sort_order' => 5],
            ['name' => 'دش', 'icon' => 'satellite-dish', 'sort_order' => 6],
            ['name' => 'كاميرا', 'icon' => 'camera', 'sort_order' => 7],
            ['name' => 'أخرى', 'icon' => 'wrench', 'sort_order' => 8],
        ];

        foreach ($devices as $d) {
            DeviceType::updateOrCreate(['name' => $d['name']], $d + ['is_active' => true]);
        }

        // الأقسام الافتراضية
        $departments = [
            ['name' => 'تكييف وتبريد', 'icon' => 'wind', 'device_types' => ['تكييف', 'سبليت', 'مروحة']],
            ['name' => 'غسالات', 'icon' => 'washing-machine', 'device_types' => ['غسالة', 'غسالات', 'نشافة']],
            ['name' => 'ثلاجات', 'icon' => 'refrigerator', 'device_types' => ['ثلاجة', 'ديب فريزر', 'فريزر']],
            ['name' => 'بوتاجازات', 'icon' => 'flame', 'device_types' => ['بوتاجاز', 'فرن', 'سخان']],
            ['name' => 'دش وشاشات', 'icon' => 'satellite-dish', 'device_types' => ['دش', 'شاشة', 'رسيفر', 'كاميرا']],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(
                ['name' => $dept['name']],
                [
                    'icon' => $dept['icon'],
                    'device_types' => $dept['device_types'],
                    'is_active' => true,
                    'description' => 'قسم صيانة '.$dept['name'],
                ]
            );
        }
    }
}
