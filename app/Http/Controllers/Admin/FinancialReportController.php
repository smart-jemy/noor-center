<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\PartnerRepair;
use App\Models\PartnerSettlement;
use App\Models\ServiceRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinancialReportController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        // ===== إيرادات الصيانات المكتملة =====
        $completed = ServiceRequest::where('status', 'COMPLETED')
            ->whereBetween('completed_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->get(['id', 'price', 'paid_amount', 'completed_at']);

        $revenue = (float) $completed->sum(fn ($r) => (float) $r->price);
        $collected = (float) $completed->sum(fn ($r) => (float) $r->paid_amount);
        $receivable = $revenue - $collected; // آجل العملاء

        // ===== المدفوع من الشركاء =====
        $partnerRepairs = PartnerRepair::whereBetween('date', [$from, $to])->get();
        $partnerPaid = (float) $partnerRepairs->sum('paid_amount');
        $partnerDeferred = (float) $partnerRepairs->sum(fn ($r) => $r->deferredAmount());

        $settlements = PartnerSettlement::whereBetween('date', [$from, $to])->get();
        $settlementsTotal = (float) $settlements->sum('amount');

        // ===== المصروفات =====
        $expenses = Expense::whereBetween('date', [$from, $to])->get();
        $expensesTotal = (float) $expenses->sum('amount');

        $expensesByCategory = $expenses->groupBy('category')
            ->map(fn ($group) => (float) $group->sum('amount'));

        // ===== الصافي =====
        $net = $collected + $partnerPaid + $settlementsTotal - $expensesTotal;

        // ===== تفصيل يومي =====
        $days = collect();
        $start = Carbon::parse($from);
        $end = Carbon::parse($to);
        for ($d = $start; $d->lte($end) && $days->count() < 62; $d->addDay()) {
            $ds = $d->toDateString();
            $days->push([
                'date' => $ds,
                'maintenance' => (float) $completed->filter(fn ($r) => $r->completed_at?->toDateString() === $ds)->sum(fn ($r) => (float) $r->paid_amount),
                'partner' => (float) $partnerRepairs->where('date', $ds)->sum('paid_amount')
                    + (float) $settlements->where('date', $ds)->sum('amount'),
                'expenses' => (float) $expenses->where('date', $ds)->sum('amount'),
            ]);
        }

        // ===== إجمالي المستحق على الشركاء (كله، مش الفترة فقط) =====
        $partnerBalances = \App\Models\PartnerTechnician::where('is_active', true)
            ->withCount('repairs as repairs_count')
            ->get()
            ->map(fn ($p) => ['name' => $p->name, 'balance' => $p->balance(), 'repairs' => $p->repairs_count])
            ->filter(fn ($p) => $p['balance'] != 0.0)
            ->values();

        $summary = [
            'revenue' => $revenue,
            'collected' => $collected,
            'receivable' => $receivable,
            'partnerPaid' => $partnerPaid,
            'partnerDeferred' => $partnerDeferred,
            'settlementsTotal' => $settlementsTotal,
            'expensesTotal' => $expensesTotal,
            'net' => $net,
            'completedCount' => $completed->count(),
            'partnerRepairsCount' => $partnerRepairs->count(),
            'settlementsCount' => $settlements->count(),
            'expensesCount' => $expenses->count(),
        ];

        return view('admin.financial.index', compact('summary', 'days', 'expensesByCategory', 'partnerBalances', 'from', 'to'));
    }
}
