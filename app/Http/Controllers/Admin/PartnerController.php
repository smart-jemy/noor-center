<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\PartnerRepair;
use App\Models\PartnerSettlement;
use App\Models\PartnerTechnician;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function index(Request $request)
    {
        $partners = PartnerTechnician::with('createdBy:id,name')
            ->withCount([
                'repairs as repairs_count',
                'settlements as settlements_count',
            ])
            ->withMax(['repairs as last_repair_date' => fn ($q) => $q->latest('date')], 'date')
            ->orderBy('name')
            ->paginate(12);

        // رصيد كل شريك
        $partnerIds = $partners->pluck('id');
        $totals = PartnerRepair::selectRaw('partner_id, COALESCE(SUM(total_amount),0) as total, COALESCE(SUM(paid_amount),0) as paid')
            ->whereIn('partner_id', $partnerIds)->groupBy('partner_id')->get()->keyBy('partner_id');
        $settled = PartnerSettlement::selectRaw('partner_id, COALESCE(SUM(amount),0) as s')
            ->whereIn('partner_id', $partnerIds)->groupBy('partner_id')->get()->keyBy('partner_id');

        foreach ($partners as $p) {
            $t = $totals->get($p->id);
            $p->total_amount = (float) ($t->total ?? 0);
            $p->paid_amount = (float) ($t->paid ?? 0);
            $p->settled_amount = (float) ($settled->get($p->id)->s ?? 0);
            $p->balance_amount = $p->total_amount - $p->paid_amount - $p->settled_amount;
        }

        $stats = [
            'total' => PartnerTechnician::count(),
            'active' => PartnerTechnician::where('is_active', true)->count(),
            'totalRepairs' => PartnerRepair::count(),
            'totalDeferred' => (function () {
                $total = (float) PartnerRepair::sum('total_amount');
                $paid = (float) PartnerRepair::sum('paid_amount');
                $settled = (float) PartnerSettlement::sum('amount');
                return $total - $paid - $settled;
            })(),
        ];

        // آخر الصيانات — اللي ملهاش سعر بتظهر الأول عشان تتحدد
        $recentRepairs = PartnerRepair::with('partner:id,name')
            ->orderByRaw('total_amount IS NULL DESC')->latest('date')->limit(12)->get();
        $recentSettlements = PartnerSettlement::with('partner:id,name')->latest('date')->limit(10)->get();

        return view('admin.partners.index', compact('partners', 'stats', 'recentRepairs', 'recentSettlements'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:255',
            'phone' => 'nullable|string|max:32',
            'notes' => 'nullable|string|max:1000',
            'credit_limit' => 'nullable|numeric|min:0',
        ], [
            'name.required' => 'اكتب اسم الشريك',
        ]);

        $data['created_by_id'] = $request->user()->id;
        $data['credit_limit'] = $data['credit_limit'] ?? 0;
        $data['is_active'] = true;

        PartnerTechnician::create($data);

        ActivityLog::record($request->user()->id, 'PARTNER_ADDED', "إضافة شريك: {$data['name']}");

        return back()->with('success', 'تم إضافة الشريك');
    }

    public function update(Request $request, PartnerTechnician $partnerTechnician)
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:255',
            'phone' => 'nullable|string|max:32',
            'notes' => 'nullable|string|max:1000',
            'credit_limit' => 'nullable|numeric|min:0',
        ], [
            'name.required' => 'اكتب اسم الشريك',
        ]);

        $partnerTechnician->update([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'notes' => $data['notes'] ?? null,
            'credit_limit' => $data['credit_limit'] ?? 0,
        ]);

        ActivityLog::record($request->user()->id, 'PARTNER_UPDATED', "تحديث شريك: {$partnerTechnician->name}");

        return back()->with('success', 'تم تحديث بيانات الشريك');
    }

    public function toggle(Request $request, PartnerTechnician $partnerTechnician)
    {
        $partnerTechnician->update(['is_active' => ! $partnerTechnician->is_active]);

        $state = $partnerTechnician->is_active ? 'تفعيل' : 'تعطيل';
        ActivityLog::record($request->user()->id, 'PARTNER_UPDATED', "{$state} حساب شريك {$partnerTechnician->name}");

        return back()->with('success', "تم {$state} الشريك");
    }

    /**
     * تسجيل صيانات الشريك — أجهزة متعددة في المرة الواحدة + سعر اختياري
     * (الاستقبال بيستلم الأجهزة الأول والسعر يتحدد بعدين من updateRepair)
     */
    public function storeRepair(Request $request, PartnerTechnician $partnerTechnician)
    {
        $data = $request->validate([
            // نموذج متعدد الأجهزة: devices[0][device_type] ... إلخ
            'devices' => 'present|array|min:1|max:10',
            'devices.*.device_type' => 'required|string|max:255',
            'devices.*.brand' => 'nullable|string|max:255',
            'devices.*.customer_name' => 'nullable|string|max:255',
            'devices.*.issue_description' => 'nullable|string|max:1000',
            'devices.*.total_amount' => 'nullable|numeric|min:0',
            'devices.*.paid_amount' => 'nullable|numeric|min:0',
            'date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ], [
            'devices.required' => 'اكتب بيانات الأجهزة',
            'devices.*.device_type.required' => 'اكتب نوع الجهاز',
            'date.required' => 'اختر التاريخ',
        ]);

        $created = 0;
        $unpriced = 0;
        foreach ($data['devices'] as $dev) {
            $total = ($dev['total_amount'] ?? null) !== null && $dev['total_amount'] !== '' ? (float) $dev['total_amount'] : null;
            $paid = (float) ($dev['paid_amount'] ?? 0);

            if ($total !== null && $paid > $total) {
                return back()->with('error', 'المدفوع أكبر من الإجمالي في أحد الأجهزة');
            }

            // حد الآجل بيتحسب على اللي عليه سعر بس
            if ($total !== null && $partnerTechnician->credit_limit > 0) {
                $newBalance = $partnerTechnician->balance() + ($total - $paid);
                if ($newBalance > $partnerTechnician->credit_limit) {
                    return back()->with('error', 'الحساب هيتجاوز حد الآجل ('.money($partnerTechnician->credit_limit).') — الرصيد الجديد هيبقى '.money($newBalance));
                }
            }

            $partnerTechnician->repairs()->create([
                'device_type' => $dev['device_type'],
                'brand' => $dev['brand'] ?? null,
                'customer_name' => $dev['customer_name'] ?? null,
                'issue_description' => $dev['issue_description'] ?? null,
                'total_amount' => $total,
                'paid_amount' => $paid,
                'date' => $data['date'],
                'notes' => $data['notes'] ?? null,
                'created_by_id' => $request->user()->id,
            ]);
            $created++;
            if ($total === null) {
                $unpriced++;
            }
        }

        ActivityLog::record($request->user()->id, 'PARTNER_REPAIR_ADDED', "أدمن سجّل {$created} صيانة للشريك {$partnerTechnician->name}".($unpriced ? " ({$unpriced} بدون سعر لحد الآن)" : ''));

        $msg = 'تم تسجيل '.$created.' صيانة للشريك';
        if ($unpriced > 0) {
            $msg .= " — {$unpriced} جهاز سعره لسه محددش (تقدر تضيفه من قائمة الصيانات في أي وقت)";
        }

        return back()->with('success', $msg);
    }

    /**
     * تحديد/تحديث سعر صيانة شريك بعدين — أو إضافة مدفوع
     * (الاستقبال استلم الجهاز بدون سعر وحدده دلوقتي)
     */
    public function updateRepair(Request $request, PartnerRepair $partnerRepair)
    {
        $data = $request->validate([
            'total_amount' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ], [
            'total_amount.min' => 'السعر لازم يكون رقم موجب أو فاضي',
            'paid_amount.min' => 'المدفوع لازم يكون رقم موجب',
        ]);

        $total = ($data['total_amount'] ?? null) !== null && $data['total_amount'] !== '' ? (float) $data['total_amount'] : null;
        $paid = $data['paid_amount'] !== null && $data['paid_amount'] !== '' ? (float) $data['paid_amount'] : (float) $partnerRepair->paid_amount;

        if ($total !== null && $paid > $total) {
            return back()->with('error', 'المدفوع أكبر من الإجمالي');
        }

        $partner = $partnerRepair->partner;
        if ($total !== null && $partner->credit_limit > 0) {
            $newBalance = $partner->balance() - ($partnerRepair->hasPrice() ? $partnerRepair->deferredAmount() : 0) + ($total - $paid);
            if ($newBalance > $partner->credit_limit) {
                return back()->with('error', 'الحساب هيتجاوز حد الآجل ('.money($partner->credit_limit).') — الرصيد الجديد هيبقى '.money($newBalance));
            }
        }

        $wasUnpriced = ! $partnerRepair->hasPrice();
        $partnerRepair->update([
            'total_amount' => $total,
            'paid_amount' => $paid,
            'notes' => $data['notes'] ?? $partnerRepair->notes,
        ]);

        ActivityLog::record($request->user()->id, 'PARTNER_REPAIR_UPDATED', "تحديث صيانة شريك {$partner->name} ({$partnerRepair->device_type})".($wasUnpriced && $total !== null ? ' — تحديد السعر: '.money($total) : ''));

        return back()->with('success', $wasUnpriced && $total !== null ? 'تم تحديد سعر الصيانة: '.money($total) : 'تم تحديث الصيانة');
    }

    public function storeSettlement(Request $request, PartnerTechnician $partnerTechnician)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ], [
            'amount.required' => 'اكتب مبلغ التسوية',
            'date.required' => 'اختر التاريخ',
        ]);

        $balance = $partnerTechnician->balance();

        if ($data['amount'] > $balance + 0.001) {
            return back()->with('error', 'المبلغ أكبر من الرصيد المستحق ('.money($balance).')');
        }

        $data['created_by_id'] = $request->user()->id;
        $partnerTechnician->settlements()->create($data);

        ActivityLog::record($request->user()->id, 'PARTNER_SETTLEMENT_ADDED', "تسوية {$data['amount']} ج.م من الشريك {$partnerTechnician->name}");

        return back()->with('success', 'تم تسجيل التسوية — المتبقي: '.money($partnerTechnician->fresh()->balance()));
    }
}
