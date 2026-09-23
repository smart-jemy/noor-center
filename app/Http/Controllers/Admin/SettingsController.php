<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::current();

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string|max:32',
            'email' => 'required|email|max:255',
            'address' => 'required|string|max:500',
            'working_hours' => 'required|string|max:255',
            'facebook_url' => 'nullable|url|max:500',
            'instagram_url' => 'nullable|url|max:500',
            'whatsapp_num' => 'nullable|string|max:32',
            'receipt_title' => 'required|string|max:255',
            'receipt_subtitle' => 'required|string|max:255',
            'receipt_notes' => 'required|string|max:1000',
            'receipt_footer' => 'required|string|max:500',
            'maintenance_reminder_enabled' => 'nullable|boolean',
            'maintenance_reminder_months' => 'nullable|integer|min:1|max:36',
            'sound_notification_enabled' => 'nullable|boolean',
            'sound_notification_url' => 'nullable|string|max:500',
        ], [
            'phone.required' => 'اكتب رقم الهاتف',
            'email.required' => 'اكتب البريد الإلكتروني',
            'email.email' => 'بريد إلكتروني غير صحيح',
            'address.required' => 'اكتب العنوان',
            'working_hours.required' => 'اكتب ساعات العمل',
        ]);

        $settings = Setting::current();

        $settings->update([
            'phone' => $data['phone'],
            'email' => $data['email'],
            'address' => $data['address'],
            'working_hours' => $data['working_hours'],
            'facebook_url' => $data['facebook_url'] ?? null,
            'instagram_url' => $data['instagram_url'] ?? null,
            'whatsapp_num' => $data['whatsapp_num'] ?? null,
            'receipt_title' => $data['receipt_title'],
            'receipt_subtitle' => $data['receipt_subtitle'],
            'receipt_notes' => $data['receipt_notes'],
            'receipt_footer' => $data['receipt_footer'],
            'maintenance_reminder_enabled' => $request->boolean('maintenance_reminder_enabled'),
            'maintenance_reminder_months' => $data['maintenance_reminder_months'] ?? 6,
            'sound_notification_enabled' => $request->boolean('sound_notification_enabled'),
            'sound_notification_url' => $data['sound_notification_url'] ?? null,
        ]);


        ActivityLog::record($request->user()->id, 'SETTINGS_UPDATED', 'تحديث إعدادات الموقع');

        return back()->with('success', 'تم حفظ الإعدادات — هتظهر في كل الصفحات فوراً');
    }
}
