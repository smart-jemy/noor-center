<?php

namespace App\Http\Controllers;

use App\Models\DeviceType;
use App\Models\Review;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $deviceTypes = DeviceType::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $reviews = Review::where('status', 'APPROVED')
            ->with('customer:id,name', 'request:id,device_type')
            ->latest()
            ->limit(6)
            ->get();

        $avgRating = (float) Review::where('status', 'APPROVED')->avg('rating') ?: 4.9;

        return view('home', compact('deviceTypes', 'reviews', 'avgRating'));
    }

    public function track()
    {
        return view('track');
    }

    /** تتبع عام برقم الطلب + الهاتف (بدون تسجيل دخول) */
    public function trackSearch(Request $request)
    {
        $data = $request->validate([
            'order_number' => 'required|string',
            'phone' => 'required|string',
        ], [
            'order_number.required' => 'اكتب رقم الطلب',
            'phone.required' => 'اكتب رقم الهاتف',
        ]);

        $result = ServiceRequest::with('assignedTechnician:id,name,specialty')
            ->where('order_number', trim($data['order_number']))
            ->where('phone', 'like', '%'.trim($data['phone']))
            ->first();

        if (! $result) {
            return back()->withInput()->with('error', 'لا يوجد طلب مطابق لهذه البيانات — تأكد من رقم الطلب ورقم الهاتف');
        }

        return view('track', compact('result'));
    }
}
