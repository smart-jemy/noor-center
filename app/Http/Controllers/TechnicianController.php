<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Review;
use App\Models\ServiceRequest;
use App\Support\Noor;
use Illuminate\Http\Request;

class TechnicianController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = $user->assignedRequests()
            ->with('customer:id,name,phone', 'department:id,name', 'review');

        if ($request->filled('status') && in_array($request->status, ServiceRequest::STATUSES)) {
            $query->where('status', $request->status);
        }

        $requests = $query->latest()->paginate(10)->withQueryString();

        // تقييمات الفني المعتمدة — زي الأصل (كروت التقييمات + متوسط التقييم)
        $reviewsCount = Review::whereHas('request', fn ($q) => $q->where('assigned_technician_id', $user->id))
            ->where('status', 'APPROVED')->count();
        $avgRating = (float) Review::whereHas('request', fn ($q) => $q->where('assigned_technician_id', $user->id))
            ->where('status', 'APPROVED')->avg('rating');

        $stats = [
            'active' => $user->assignedRequests()->whereIn('status', ['CONFIRMED', 'IN_PROGRESS', 'READY', 'RETURNED'])->count(),
            'returned' => $user->assignedRequests()->where('status', 'RETURNED')->count(),
            'ready' => $user->assignedRequests()->where('status', 'READY')->count(),
            'completed' => $user->assignedRequests()->where('status', 'COMPLETED')->count(),
            'total' => $user->assignedRequests()->count(),
            'reviews' => $reviewsCount,
            'avgRating' => $avgRating,
        ];

        return view('technician', compact('requests', 'stats'));
    }

    public function status(Request $request, ServiceRequest $serviceRequest)
    {
        abort_unless($serviceRequest->assigned_technician_id === $request->user()->id, 403);

        $data = $request->validate([
            'status' => 'required|in:IN_PROGRESS,READY,COMPLETED',
            'warranty_months' => 'nullable|integer|min:0|max:60',
        ], [
            'status.required' => 'اختر الحالة الجديدة',
        ]);

        if ($data['status'] === 'COMPLETED') {
            Noor::completeRequest($serviceRequest, (int) ($data['warranty_months'] ?? 0));
        } elseif ($data['status'] === 'READY') {
            $serviceRequest->update(['status' => 'READY']);

            Noor::notifyUser(
                $serviceRequest->customer_id,
                'REQUEST_UPDATED',
                'جهازك جاهز',
                "الإصلاح خلص — جهازك في طلبك {$serviceRequest->order_number} جاهز للاستلام",
                $serviceRequest->id
            );
        } else {
            $serviceRequest->update(['status' => 'IN_PROGRESS']);

            Noor::notifyUser(
                $serviceRequest->customer_id,
                'REQUEST_UPDATED',
                'تحديث حالة الطلب',
                "الفني بدأ شغل الصيانة على طلبك {$serviceRequest->order_number}",
                $serviceRequest->id
            );
        }

        ActivityLog::record(
            $request->user()->id,
            'REQUEST_UPDATED',
            "الفني {$request->user()->name} حدّث الطلب {$serviceRequest->order_number} إلى ".$serviceRequest->fresh()->statusLabel()
        );

        return back()->with('success', 'تم تحديث حالة الطلب بنجاح');
    }
}
