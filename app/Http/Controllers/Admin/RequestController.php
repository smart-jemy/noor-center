<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\InventoryItem;
use App\Models\RequestNote;
use App\Models\ServiceRequest;
use App\Models\UsedPart;
use App\Models\User;
use App\Support\Noor;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ServiceRequest::with('customer:id,name,phone', 'assignedTechnician:id,name', 'department:id,name');

        if ($request->filled('status') && in_array($request->status, ServiceRequest::STATUSES)) {
            $query->where('status', $request->status);
        }

        // إخفاء المكتمل/الملغي — زي الأصل
        if ($request->boolean('hide_completed')) {
            $query->whereNotIn('status', ['COMPLETED', 'CANCELLED']);
        }

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($w) use ($q) {
                $w->where('order_number', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('device_type', 'like', "%{$q}%")
                    ->orWhere('brand', 'like', "%{$q}%")
                    ->orWhere('address', 'like', "%{$q}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$q}%"));
            });
        }

        if ($request->filled('area') && array_key_exists($request->area, ServiceRequest::AREAS)) {
            $query->where('area', $request->area);
        }

        $requests = $query->latest()->paginate(15)->withQueryString();

        // شريط المتأخرات — زي الأصل
        $overdueCount = ServiceRequest::whereNotIn('status', ['COMPLETED', 'CANCELLED'])
            ->where('expected_exit_at', '<', now())->count();

        return view('admin.requests.index', compact('requests', 'overdueCount'));
    }

    public function show(ServiceRequest $serviceRequest)
    {
        $serviceRequest->load('customer', 'assignedTechnician', 'department', 'usedParts.inventoryItem', 'internalNotes.author', 'review');

        $technicians = User::where('role', 'TECHNICIAN')->where('is_active', true)
            ->get(['id', 'name', 'specialty']);

        $inventory = InventoryItem::where('quantity', '>', 0)->orderBy('name')
            ->get(['id', 'name', 'sell_price', 'quantity']);

        return view('admin.requests.show', compact('serviceRequest', 'technicians', 'inventory'));
    }

    public function update(Request $request, ServiceRequest $serviceRequest)
    {
        $data = $request->validate([
            'status' => 'required|in:'.implode(',', ServiceRequest::STATUSES),
            'price' => 'nullable|numeric|min:0',
            'admin_notes' => 'nullable|string|max:2000',
            'warranty_months' => 'nullable|integer|min:0|max:60',
            'payment_method' => 'nullable|in:cash,transfer',
            'urgency' => 'nullable|in:normal,urgent,emergency',
            'return_reason' => 'nullable|string|min:3|max:500',
        ], [
            'status.required' => 'اختر الحالة',
        ]);

        $oldStatus = $serviceRequest->status;

        $update = [
            'status' => $data['status'],
            'price' => $data['price'] ?? null,
            // التحصيل من العملاء بكامل السعر — التسليم يحصّل الأجر كاملاً تلقائياً
            'paid_amount' => $data['status'] === 'COMPLETED' ? (float) ($data['price'] ?? $serviceRequest->price) : ($serviceRequest->paid_amount ?? 0),
            'admin_notes' => $data['admin_notes'] ?? null,
            'warranty_months' => $data['warranty_months'] ?? $serviceRequest->warranty_months,
        ];

        // طريقة الدفع (كاش/تحويل) — التسليم = تم التحصيل
        if (! empty($data['payment_method'])) {
            $update['payment_method'] = $data['payment_method'];
        }

        // تغيير الاستعجال = مهلة تسليم جديدة
        if (! empty($data['urgency']) && $data['urgency'] !== $serviceRequest->urgency) {
            $update['urgency'] = $data['urgency'];
            $update['expected_exit_at'] = ($serviceRequest->entered_at ?? now())->addHours(ServiceRequest::URGENCY_SLA_HOURS[$data['urgency']] ?? 168);
        }

        // مرتجع للفني من لوحة الأدمن مباشرة
        if ($data['status'] === 'RETURNED' && $oldStatus !== 'RETURNED') {
            Noor::returnRequest($serviceRequest->fill($update), $data['return_reason'] ?? 'إرجاع بأمر الأدمن', (int) $request->user()->id);
            ActivityLog::record($request->user()->id, 'REQUEST_RETURNED', "مرتجع للفني: الطلب {$serviceRequest->order_number} رجع من العميل");

            return back()->with('success', 'تم تسجيل مرتجع للفني بنجاح');
        }

        if ($data['status'] === 'COMPLETED' && $oldStatus !== 'COMPLETED') {
            $serviceRequest->fill($update);
            Noor::completeRequest($serviceRequest, (int) $update['warranty_months'], $data['payment_method'] ?? null);
        } elseif ($data['status'] !== 'COMPLETED' && $oldStatus === 'COMPLETED') {
            // رجوع من التسليم — إلغاء الضمان وسحب نقاط الولاء (زي الأصل)
            $serviceRequest->fill($update);
            Noor::revertCompletion($serviceRequest);
        } else {
            $serviceRequest->update($update);
        }

        if ($data['status'] !== $oldStatus) {
            Noor::notifyUser(
                $serviceRequest->customer_id,
                'REQUEST_UPDATED',
                'تحديث حالة الطلب',
                "طلبك {$serviceRequest->order_number} أصبح: ".$serviceRequest->fresh()->statusLabel(),
                $serviceRequest->id
            );
        }

        ActivityLog::record($request->user()->id, 'REQUEST_UPDATED', "تحديث الطلب {$serviceRequest->order_number} من {$oldStatus} إلى {$data['status']}");

        return back()->with('success', 'تم تحديث الطلب بنجاح');
    }

    public function assign(Request $request, ServiceRequest $serviceRequest)
    {
        $data = $request->validate([
            'technician_id' => 'nullable|exists:users,id',
        ], [
            'technician_id.exists' => 'الفني غير موجود',
        ]);

        // إلغاء التعيين — «بدون فني» زي الأصل
        if (empty($data['technician_id'])) {
            $oldTech = $serviceRequest->assignedTechnician?->name;
            $serviceRequest->update(['assigned_technician_id' => null, 'assigned_at' => null]);
            ActivityLog::record($request->user()->id, 'REQUEST_ASSIGNED', "إلغاء تعيين".($oldTech ? " {$oldTech}" : '')." من الطلب {$serviceRequest->order_number}");

            return back()->with('success', 'تم إلغاء تعيين الفني للطلب '.$serviceRequest->order_number);
        }

        $tech = User::where('role', 'TECHNICIAN')->where('is_active', true)->findOrFail($data['technician_id']);

        $serviceRequest->update([
            'assigned_technician_id' => $tech->id,
            'assigned_at' => now(),
        ]);

        if ($serviceRequest->status === 'PENDING') {
            $serviceRequest->update(['status' => 'CONFIRMED']);
            Noor::notifyUser(
                $serviceRequest->customer_id,
                'REQUEST_UPDATED',
                'تم تأكيد طلبك',
                "طلبك {$serviceRequest->order_number} اتأكد واتعين له فني: {$tech->name}",
                $serviceRequest->id
            );
        }

        Noor::notifyUser($tech->id, 'TECHNICIAN_ASSIGNED', 'تعيين طلب جديد', "تم تعيين الطلب {$serviceRequest->order_number} ليك", $serviceRequest->id);

        ActivityLog::record($request->user()->id, 'REQUEST_ASSIGNED', "تعيين {$tech->name} للطلب {$serviceRequest->order_number}");

        return back()->with('success', 'تم تعيين الفني '.$tech->name);
    }

    public function storeNote(Request $request, ServiceRequest $serviceRequest)
    {
        $data = $request->validate([
            'content' => 'required|string|min:2|max:2000',
        ], [
            'content.required' => 'اكتب الملاحظة',
        ]);

        RequestNote::create([
            'request_id' => $serviceRequest->id,
            'author_id' => $request->user()->id,
            'content' => $data['content'],
        ]);

        return back()->with('success', 'تمت إضافة الملاحظة');
    }

    public function destroyNote(RequestNote $requestNote)
    {
        $requestNote->delete();

        return back()->with('success', 'تم حذف الملاحظة');
    }

    public function storePart(Request $request, ServiceRequest $serviceRequest)
    {
        $data = $request->validate([
            'inventory_item_id' => 'nullable|exists:inventory_items,id',
            'custom_name' => 'nullable|required_without:inventory_item_id|string|max:255',
            'quantity' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
        ], [
            'quantity.required' => 'اكتب الكمية',
            'unit_price.required' => 'اكتب سعر الوحدة',
        ]);

        // خصم من المخزن لو صنف داخلي
        if (! empty($data['inventory_item_id'])) {
            $item = InventoryItem::findOrFail($data['inventory_item_id']);

            if ($item->quantity < $data['quantity']) {
                return back()->with('error', 'الكمية غير متوفرة في المخزن (المتاح: '.$item->quantity.')');
            }

            $item->decrement('quantity', $data['quantity']);

            if ($data['unit_price'] == 0) {
                $data['unit_price'] = $item->sell_price;
            }
        }

        UsedPart::create([
            'request_id' => $serviceRequest->id,
            'inventory_item_id' => $data['inventory_item_id'] ?? null,
            'custom_name' => $data['custom_name'] ?? null,
            'quantity' => $data['quantity'],
            'unit_price' => $data['unit_price'],
        ]);

        return back()->with('success', 'تمت إضافة القطعة');
    }

    public function destroyPart(Request $request, UsedPart $usedPart)
    {
        // إرجاع الكمية للمخزن
        if ($usedPart->inventoryItem) {
            $usedPart->inventoryItem->increment('quantity', $usedPart->quantity);
        }

        $usedPart->delete();

        return back()->with('success', 'تم حذف القطعة وإرجاعها للمخزن');
    }

    /**
     * حذف الطلب نهائياً مع كل البيانات المرتبطة به — زي الأصل بالظبط
     * (تقييمات + ملاحظات + قطع مستخدمة + إشعارات)
     */
    public function destroy(Request $request, ServiceRequest $serviceRequest)
    {
        $orderNumber = $serviceRequest->order_number;

        // لو الطلب مكتمل: نرجّع قطع المخزن المستخدمة ونحذف الكمية الممنوحة
        foreach ($serviceRequest->usedParts as $part) {
            if ($part->inventoryItem) {
                $part->inventoryItem->increment('quantity', $part->quantity);
            }
        }

        // سحب نقاط الولاء لو كان مكتمل (نفس منطق الرجوع من مكتمل)
        if ($serviceRequest->status === 'COMPLETED') {
            $price = (float) ($serviceRequest->price ?? 0);
            $paid = (float) ($serviceRequest->paid_amount ?? 0);
            $effectivePaid = min($price, $paid > 0 ? $paid : $price);
            if ($effectivePaid > 0 && $serviceRequest->customer) {
                $customer = $serviceRequest->customer;
                $customer->decrement('loyalty_points', (int) floor($effectivePaid));
                $customer->decrement('total_spent', $effectivePaid);
                $customer->update([
                    'loyalty_points' => max(0, $customer->fresh()->loyalty_points),
                    'total_spent' => max(0, $customer->fresh()->total_spent),
                ]);
            }
        }

        $serviceRequest->usedParts()->delete();
        $serviceRequest->internalNotes()->delete();
        $serviceRequest->review()?->delete();
        \App\Models\NoorNotification::where('request_id', $serviceRequest->id)->delete();
        $serviceRequest->delete();

        ActivityLog::record($request->user()->id, 'REQUEST_DELETED', "حذف الطلب {$orderNumber} نهائياً بكل بياناته المرتبطة");

        return redirect()->route('admin.requests.index')->with('success', "تم حذف الطلب {$orderNumber} نهائياً");
    }
}
