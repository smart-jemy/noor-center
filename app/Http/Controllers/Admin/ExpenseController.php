<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with('createdBy:id,name');

        if ($request->filled('category') && array_key_exists($request->category, Expense::CATEGORY_LABELS)) {
            $query->where('category', $request->category);
        }

        if ($request->filled('from')) {
            $query->where('date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->where('date', '<=', $request->to);
        }

        $expenses = $query->orderByDesc('date')->orderByDesc('created_at')->paginate(20)->withQueryString();

        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $stats = [
            'today' => (float) Expense::where('date', $today)->sum('amount'),
            'todayCount' => Expense::where('date', $today)->count(),
            'month' => (float) Expense::whereBetween('date', [$monthStart, $today])->sum('amount'),
            'total' => (float) Expense::sum('amount'),
        ];

        // توزيع الفئات هذا الشهر
        $byCategory = Expense::whereBetween('date', [$monthStart, $today])
            ->selectRaw('category, COALESCE(SUM(amount), 0) as total, COUNT(*) as c')
            ->groupBy('category')->get();

        return view('admin.expenses.index', compact('expenses', 'stats', 'byCategory'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|min:2|max:500',
            'category' => 'required|in:rent,utilities,salaries,supplies,transport,other',
            'date' => 'required|date',
        ], [
            'amount.required' => 'اكتب المبلغ',
            'description.required' => 'اكتب الوصف',
            'category.required' => 'اختر الفئة',
            'date.required' => 'اختر التاريخ',
        ]);

        $data['created_by_id'] = $request->user()->id;
        Expense::create($data);

        ActivityLog::record($request->user()->id, 'EXPENSE_ADDED', "تسجيل مصروف: {$data['description']} بمبلغ {$data['amount']} ج.م");

        return back()->with('success', 'تم تسجيل المصروف');
    }

    public function update(Request $request, Expense $expense)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|min:2|max:500',
            'category' => 'required|in:rent,utilities,salaries,supplies,transport,other',
            'date' => 'required|date',
        ], [
            'amount.required' => 'اكتب المبلغ',
            'description.required' => 'اكتب الوصف',
        ]);

        $expense->update($data);

        ActivityLog::record($request->user()->id, 'EXPENSE_UPDATED', "تحديث مصروف: {$expense->description}");

        return back()->with('success', 'تم تحديث المصروف');
    }

    public function destroy(Request $request, Expense $expense)
    {
        $desc = $expense->description;
        $expense->delete();

        ActivityLog::record($request->user()->id, 'EXPENSE_DELETED', "حذف مصروف: {$desc}");

        return back()->with('success', 'تم حذف المصروف');
    }
}
