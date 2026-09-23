<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with('customer:id,name', 'request:id,order_number,device_type');

        if ($request->filled('status') && in_array($request->status, ['PENDING', 'APPROVED', 'REJECTED'])) {
            $query->where('status', $request->status);
        }

        if ($request->filled('rating')) {
            $query->where('rating', (int) $request->rating);
        }

        $reviews = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'pending' => Review::where('status', 'PENDING')->count(),
            'approved' => Review::where('status', 'APPROVED')->count(),
            'rejected' => Review::where('status', 'REJECTED')->count(),
            'avgRating' => (float) Review::where('status', 'APPROVED')->avg('rating') ?: 0,
        ];

        return view('admin.reviews.index', compact('reviews', 'stats'));
    }

    public function approve(Request $request, Review $review)
    {
        $review->update(['status' => 'APPROVED']);

        ActivityLog::record($request->user()->id, 'REVIEW_UPDATED', "قبول تقييم الطلب {$review->request?->order_number}");

        return back()->with('success', 'تم قبول التقييم — هيظهر على الموقع');
    }

    public function reject(Request $request, Review $review)
    {
        $review->update(['status' => 'REJECTED']);

        ActivityLog::record($request->user()->id, 'REVIEW_UPDATED', "رفض تقييم الطلب {$review->request?->order_number}");

        return back()->with('success', 'تم رفض التقييم');
    }

    public function reply(Request $request, Review $review)
    {
        $data = $request->validate([
            'admin_reply' => 'required|string|min:2|max:1000',
        ], [
            'admin_reply.required' => 'اكتب الرد',
        ]);

        $review->update(['admin_reply' => $data['admin_reply']]);

        ActivityLog::record($request->user()->id, 'REVIEW_UPDATED', "رد على تقييم الطلب {$review->request?->order_number}");

        return back()->with('success', 'تم حفظ الرد');
    }

    public function destroy(Request $request, Review $review)
    {
        $orderNum = $review->request?->order_number;
        $review->delete();

        ActivityLog::record($request->user()->id, 'REVIEW_UPDATED', "حذف تقييم الطلب {$orderNum}");

        return back()->with('success', 'تم حذف التقييم');
    }
}
