<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->loadCount('requests');

        $completed = $user->requests()->where('status', 'COMPLETED')->count();
        $warrantyActive = $user->requests()
            ->where('status', 'COMPLETED')
            ->whereNotNull('warranty_end_date')
            ->where('warranty_end_date', '>=', now()->toDateString())
            ->count();

        return view('profile', compact('user', 'completed', 'warrantyActive'));
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'required|string|min:2|max:255',
            'phone' => ['required', 'string', 'regex:/^01[0-9]{9}$/', Rule::unique('users', 'phone')->ignore($user->id)],
            'specialty' => 'nullable|string|max:255',
        ], [
            'phone.regex' => 'اكتب رقم موبايل مصري صحيح (11 رقم يبدأ بـ 01)',
            'phone.unique' => 'رقم الهاتف مستخدم بحساب آخر',
        ]);

        $user->update([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'specialty' => $user->role === 'TECHNICIAN' ? ($data['specialty'] ?? null) : $user->specialty,
        ]);

        return back()->with('success', 'تم تحديث البيانات بنجاح');
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'current_password.required' => 'اكتب كلمة المرور الحالية',
            'password.min' => 'كلمة المرور الجديدة 6 أحرف على الأقل',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق',
        ]);

        if (! Hash::check($data['current_password'], $request->user()->password)) {
            return back()->with('error', 'كلمة المرور الحالية غير صحيحة');
        }

        $request->user()->update(['password' => $data['password']]);

        return back()->with('success', 'تم تغيير كلمة المرور بنجاح');
    }
}
