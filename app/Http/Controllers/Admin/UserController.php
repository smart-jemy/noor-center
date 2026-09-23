<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\User;
use App\Support\Noor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('department:id,name', 'manager:id,name')
            ->withCount('assignedRequests as assigned_count');

        if ($request->filled('role') && array_key_exists($request->role, User::ROLES)) {
            $query->where('role', $request->role);
        }

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%");
            });
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        $departments = Department::where('is_active', true)->get(['id', 'name']);
        $managers = User::where('role', 'DEPARTMENT_MANAGER')->where('is_active', true)->get(['id', 'name']);

        return view('admin.users.index', compact('users', 'departments', 'managers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:255',
            'phone' => ['required', 'string', 'regex:/^01[0-9]{9}$/', 'unique:users,phone'],
            'password' => 'required|string|min:6',
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'specialty' => 'nullable|string|max:255|required_if:role,TECHNICIAN',
            'department_id' => 'nullable|exists:departments,id|required_if:role,DEPARTMENT_MANAGER',
            'managed_by' => 'nullable|exists:users,id',
            // إنشاء قسم داخلي عند إضافة مدير قسم — زي الأصل (يُتحقق يدوياً حسب الدور)
            'new_department_name' => 'nullable|string|max:255',
            'new_department_device_types' => 'nullable|string|max:1000',
            'new_department_description' => 'nullable|string|max:1000',
        ], [
            'name.required' => 'اكتب الاسم',
            'phone.regex' => 'رقم موبايل صحيح (11 رقم يبدأ بـ 01)',
            'phone.unique' => 'الرقم مسجل قبل كده',
            'password.min' => 'كلمة المرور 6 أحرف على الأقل',
            'role.required' => 'اختر الدور',
            'specialty.required_if' => 'اكتب تخصص الفني',
            'department_id.required_if' => 'اختر قسم موجود أو اكتب بيانات قسم جديد',
        ]);

        // مدير القسم لازم قسم: موجود أو جديد — زي الأصل
        if ($data['role'] === 'DEPARTMENT_MANAGER' && empty($data['department_id'])) {
            if (empty($data['new_department_name'])) {
                return back()->withInput()->withErrors(['department_id' => 'اختر قسم موجود أو اكتب بيانات قسم جديد لمدير القسم']);
            }
            if (empty($data['new_department_device_types'])) {
                return back()->withInput()->withErrors(['new_department_device_types' => 'اكتب أنواع الأجهزة للقسم الجديد']);
            }
        }

        // إنشاء قسم جديد داخلياً لو مدير قسم ببيانات قسم جديدة — زي الأصل
        if ($data['role'] === 'DEPARTMENT_MANAGER' && empty($data['department_id']) && ! empty($data['new_department_name'])) {
            $department = \App\Models\Department::create([
                'name' => $data['new_department_name'],
                'device_types' => array_values(array_filter(array_map('trim', explode('،', str_replace(',', '،', $data['new_department_device_types'] ?? ''))))),
                'description' => $data['new_department_description'] ?? null,
            ]);
            $data['department_id'] = $department->id;

            ActivityLog::record($request->user()->id, 'DEPARTMENT_CREATED', "إنشاء قسم {$department->name} مع إضافة مدير {$data['name']}");
        }

        User::create(collect($data)->only(['name', 'phone', 'password', 'role', 'specialty', 'department_id', 'managed_by'])->all() + ['is_active' => true]);

        ActivityLog::record($request->user()->id, 'USER_CREATED', "إنشاء حساب {$data['name']} ({$data['role']})");

        Noor::notifyAdmins('NEW_USER', 'مستخدم جديد', "تم إنشاء حساب {$data['name']} بدور {$data['role']}");

        return back()->with('success', 'تم إنشاء الحساب بنجاح');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:255',
            'phone' => ['required', 'string', 'regex:/^01[0-9]{9}$/', Rule::unique('users', 'phone')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'specialty' => 'nullable|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'managed_by' => 'nullable|exists:users,id',
        ], [
            'name.required' => 'اكتب الاسم',
            'phone.regex' => 'رقم موبايل صحيح',
            'phone.unique' => 'الرقم مستخدم',
        ]);

        $update = [
            'name' => $data['name'],
            'phone' => $data['phone'],
            'role' => $data['role'],
            'specialty' => $data['specialty'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'managed_by' => $data['managed_by'] ?? null,
        ];

        if (! empty($data['password'])) {
            $update['password'] = $data['password'];
        }

        $user->update($update);

        ActivityLog::record($request->user()->id, 'USER_UPDATED', "تحديث حساب {$user->name} ({$user->role})");

        return back()->with('success', 'تم تحديث الحساب');
    }

    public function toggle(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'مش ممكن تعطّل حسابك');
        }

        $user->update(['is_active' => ! $user->is_active]);

        $state = $user->is_active ? 'تفعيل' : 'تعطيل';
        ActivityLog::record($request->user()->id, 'USER_UPDATED', "{$state} حساب {$user->name}");

        return back()->with('success', "تم {$state} الحساب");
    }

    /**
     * حذف حساب موظف — زي الأصل مع نفس الحمايات:
     * - ممنوع حذف الأدمن
     * - فني له طلبات نشطة → يُرفض ويُقترح التعطيل
     * - الطلبات المقفلة تُفكّ من الفني قبل الحذف
     */
    public function destroy(Request $request, User $user)
    {
        if ($user->role === User::ROLE_ADMIN) {
            return back()->with('error', 'لا يمكن حذف حساب مدير النظام');
        }

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'مش ممكن تحذف حسابك');
        }

        // فني له طلبات نشطة؟
        if ($user->role === User::ROLE_TECHNICIAN) {
            $activeCount = $user->assignedRequests()->whereIn('status', ['PENDING', 'CONTACTED', 'CONFIRMED', 'IN_PROGRESS', 'READY', 'RETURNED'])->count();
            if ($activeCount > 0) {
                return back()->with('error', "لا يمكن حذف الفني لأنه لديه {$activeCount} طلب نشط. عطل الحساب بدلاً من ذلك.");
            }

            // فك الطلبات المقفلة من الفني
            $user->assignedRequests()->update(['assigned_technician_id' => null]);
        }

        // فنيون تابعين لمدير قسم يتم حذفه → تفكيك الارتباط
        if ($user->role === User::ROLE_DEPARTMENT_MANAGER) {
            $user->managedTechnicians()->update(['managed_by' => null]);
        }

        $name = $user->name;
        $role = $user->role;
        $user->delete();

        ActivityLog::record($request->user()->id, 'USER_DELETED', "حذف حساب {$name} ({$role})");

        return back()->with('success', "تم حذف حساب {$name} نهائياً");
    }
}
