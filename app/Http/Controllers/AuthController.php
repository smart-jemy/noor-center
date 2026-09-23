<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'regex:/^01[0-9]{9}$/'],
            'password' => 'required|string',
        ], [
            'phone.regex' => 'اكتب رقم موبايل مصري صحيح (11 رقم يبدأ بـ 01)',
        ]);

        $user = User::where('phone', $data['phone'])->first();

        // محاولة الدخول العادية (bcrypt) أو كلمة مرور النظام القديم (تُحدّث تلقائياً)
        $valid = $user
            && (
                \Illuminate\Support\Facades\Auth::validate(['phone' => $data['phone'], 'password' => $data['password']])
                || $user->verifyAndUpgradeLegacyPassword($data['password'])
            );

        if (! $valid) {
            return back()->withInput($request->only('phone'))->with('error', 'رقم الهاتف أو كلمة المرور غير صحيحة');
        }

        Auth::login($user, $request->boolean('remember'));

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            return redirect()->route('login')->with('error', 'هذا الحساب معطل، تواصل مع الإدارة');
        }

        $request->session()->regenerate();
        ActivityLog::record($user->id, 'LOGIN', 'تسجيل دخول');

        // شاشة الإقلاع 3D (لوجو NC) بعد دخول الأدمن والاستقبال — 3 ثواني
        if (in_array($user->role, ['ADMIN', 'RECEPTION'], true)) {
            $request->session()->flash('boot_screen', $user->role === 'ADMIN' ? 'لوحة تحكم المدير العام' : 'لوحة الاستقبال');
        }

        return redirect()->intended(match ($user->role) {
            'ADMIN' => route('admin.dashboard'),
            'RECEPTION' => route('reception.index'),
            'DEPARTMENT_MANAGER' => route('department.index'),
            'TECHNICIAN' => route('technician'),
            default => route('home'),
        });
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:255',
            'phone' => ['required', 'string', 'regex:/^01[0-9]{9}$/', 'unique:users,phone'],
            'password' => 'required|string|min:6|confirmed',
        ], [
            'phone.regex' => 'اكتب رقم موبايل مصري صحيح (11 رقم يبدأ بـ 01)',
            'phone.unique' => 'رقم الهاتف مسجل قبل كده، سجل دخول',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => $data['password'],
            'role' => User::ROLE_CUSTOMER,
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        ActivityLog::record($user->id, 'LOGIN', 'تسجيل حساب جديد');

        return redirect()->route('home')->with('success', 'أهلاً بك يا '.$user->name.' 👋 ابدأ بطلب أول صيانة');
    }

    public function logout(Request $request)
    {
        if ($request->user()) {
            ActivityLog::record($request->user()->id, 'LOGOUT', 'تسجيل خروج');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
