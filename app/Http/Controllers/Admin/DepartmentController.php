<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::withCount([
            'requests as requests_count',
            'inventoryItems as items_count',
            'technicians as technicians_count',
        ])->with('manager:id,name')->orderBy('name')->get();

        return view('admin.departments.index', compact('departments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name',
            'icon' => 'nullable|string|max:100',
            'device_types' => 'nullable|string|max:2000',
            'description' => 'nullable|string|max:1000',
        ], [
            'name.required' => 'اكتب اسم القسم',
            'name.unique' => 'اسم القسم موجود بالفعل',
        ]);

        Department::create([
            'name' => $data['name'],
            'icon' => $data['icon'] ?? 'layers',
            'device_types' => array_values(array_filter(array_map('trim', explode(',', $data['device_types'] ?? '')))),
            'description' => $data['description'] ?? null,
            'is_active' => true,
        ]);

        ActivityLog::record($request->user()->id, 'DEPARTMENT_CREATED', "إضافة قسم: {$data['name']}");

        return back()->with('success', 'تم إضافة القسم');
    }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name,'.$department->id,
            'icon' => 'nullable|string|max:100',
            'device_types' => 'nullable|string|max:2000',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'اكتب اسم القسم',
            'name.unique' => 'الاسم مستخدم',
        ]);

        $department->update([
            'name' => $data['name'],
            'icon' => $data['icon'] ?? $department->icon,
            'device_types' => array_values(array_filter(array_map('trim', explode(',', $data['device_types'] ?? '')))),
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        ActivityLog::record($request->user()->id, 'DEPARTMENT_UPDATED', "تحديث قسم: {$department->name}");

        return back()->with('success', 'تم تحديث القسم');
    }

    public function destroy(Request $request, Department $department)
    {
        if ($department->requests()->count() > 0) {
            return back()->with('error', 'مش ممكن حذف قسم فيه طلبات — عطّله بدل كده');
        }

        $name = $department->name;
        $department->delete();

        ActivityLog::record($request->user()->id, 'DEPARTMENT_DELETED', "حذف قسم: {$name}");

        return back()->with('success', 'تم حذف القسم');
    }
}
