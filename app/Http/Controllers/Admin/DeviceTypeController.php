<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DeviceType;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;

class DeviceTypeController extends Controller
{
    public function index()
    {
        $deviceTypes = DeviceType::orderBy('sort_order')->orderBy('name')->get();

        // عدد الطلبات لكل نوع (device_type مخزّن كنص في الطلبات)
        $counts = ServiceRequest::selectRaw('device_type, COUNT(*) as c')
            ->groupBy('device_type')->pluck('c', 'device_type');

        return view('admin.device-types.index', compact('deviceTypes', 'counts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:device_types,name',
            'icon' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'accessories' => 'nullable|string|max:500',
        ], [
            'name.required' => 'اكتب اسم نوع الجهاز',
            'name.unique' => 'النوع موجود بالفعل',
        ]);

        $accessories = array_slice(array_values(array_filter(array_map('trim', explode(',', $data['accessories'] ?? '')))), 0, 5);

        DeviceType::create([
            'name' => $data['name'],
            'icon' => $data['icon'] ?? 'wrench',
            'sort_order' => $data['sort_order'] ?? 99,
            'accessories' => $accessories,
            'is_active' => true,
        ]);

        ActivityLog::record($request->user()->id, 'DEVICE_TYPE_CREATED', "إضافة نوع جهاز: {$data['name']}");

        return back()->with('success', 'تم إضافة النوع');
    }

    public function update(Request $request, DeviceType $deviceType)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:device_types,name,'.$deviceType->id,
            'icon' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'accessories' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'اكتب اسم النوع',
            'name.unique' => 'الاسم مستخدم',
        ]);

        $accessories = array_slice(array_values(array_filter(array_map('trim', explode(',', $data['accessories'] ?? '')))), 0, 5);

        $deviceType->update([
            'name' => $data['name'],
            'icon' => $data['icon'] ?? $deviceType->icon,
            'sort_order' => $data['sort_order'] ?? $deviceType->sort_order,
            'accessories' => $accessories,
            'is_active' => $request->boolean('is_active', true),
        ]);

        ActivityLog::record($request->user()->id, 'DEVICE_TYPE_UPDATED', "تحديث نوع جهاز: {$deviceType->name}");

        return back()->with('success', 'تم تحديث النوع');
    }

    public function destroy(Request $request, DeviceType $deviceType)
    {
        $used = ServiceRequest::where('device_type', $deviceType->name)->count();

        if ($used > 0) {
            return back()->with('error', "النوع مستخدم في {$used} طلب — عطّله بدل الحذف");
        }

        $name = $deviceType->name;
        $deviceType->delete();

        ActivityLog::record($request->user()->id, 'DEVICE_TYPE_DELETED', "حذف نوع جهاز: {$name}");

        return back()->with('success', 'تم حذف النوع');
    }
}
