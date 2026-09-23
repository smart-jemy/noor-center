<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerNote;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'CUSTOMER')
            ->withCount('requests')
            ->withCount(['requests as completed_count' => fn ($q) => $q->where('status', 'COMPLETED')]);

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%");
            });
        }

        if ($request->has('debtors')) {
            // عملاء عليهم آجل (طلبات مكتملة غير مسددة بالكامل)
            $query->whereHas('requests', fn ($r) => $r->where('status', 'COMPLETED')
                ->whereColumn('paid_amount', '<', 'price'));
        }

        $customers = $query->orderByDesc('requests_count')->paginate(15)->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function show(User $user)
    {
        abort_unless($user->role === 'CUSTOMER', 403, 'الصفحة للعملاء فقط');

        $user->loadCount(['requests as completed_count' => fn ($q) => $q->where('status', 'COMPLETED')]);

        $requests = $user->requests()
            ->with('assignedTechnician:id,name', 'review:id,request_id,rating,status')
            ->latest()->paginate(10);

        $notes = CustomerNote::where('customer_id', $user->id)
            ->with('author:id,name,role')->latest()->get();

        $totalDebt = (float) $user->requests()
            ->where('status', 'COMPLETED')
            ->whereColumn('paid_amount', '<', 'price')
            ->selectRaw('COALESCE(SUM(price - paid_amount), 0)')->value('total_debt') ?? 0;

        $totalDebt = (float) \DB::table('service_requests')
            ->where('customer_id', $user->id)->where('status', 'COMPLETED')
            ->whereColumn('paid_amount', '<', 'price')
            ->selectRaw('COALESCE(SUM(price - paid_amount), 0)')->value('total_debt');

        return view('admin.customers.show', compact('user', 'requests', 'notes', 'totalDebt'));
    }

    public function storeNote(Request $request, User $user)
    {
        abort_unless($user->role === 'CUSTOMER', 403);

        $data = $request->validate([
            'content' => 'required|string|min:2|max:2000',
        ], [
            'content.required' => 'اكتب الملاحظة',
        ]);

        CustomerNote::create([
            'customer_id' => $user->id,
            'author_id' => $request->user()->id,
            'content' => $data['content'],
        ]);

        return back()->with('success', 'تمت إضافة الملاحظة');
    }
}
