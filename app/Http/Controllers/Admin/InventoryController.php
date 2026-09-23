<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\InventoryItem;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryItem::with('department:id,name');

        if ($request->filled('category') && array_key_exists($request->category, InventoryItem::CATEGORY_LABELS)) {
            $query->where('category', $request->category);
        }

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('brand', 'like', "%{$q}%")
                    ->orWhere('part_number', 'like', "%{$q}%");
            });
        }

        if ($request->filled('low_stock')) {
            $query->whereColumn('quantity', '<=', 'min_quantity');
        }

        $items = $query->orderBy('name')->paginate(20)->withQueryString();

        $departments = Department::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $stats = [
            'total' => InventoryItem::count(),
            'lowStock' => InventoryItem::whereColumn('quantity', '<=', 'min_quantity')->count(),
            'stockValue' => (float) \DB::table('inventory_items')->selectRaw('COALESCE(SUM(quantity * cost_price), 0) as v')->value('v'),
        ];

        return view('admin.inventory.index', compact('items', 'departments', 'stats'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:parts,tools,consumables,other',
            'brand' => 'nullable|string|max:255',
            'part_number' => 'nullable|string|max:255',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'cost_price' => 'nullable|numeric|min:0',
            'sell_price' => 'nullable|numeric|min:0',
            'min_quantity' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'department_id' => 'nullable|exists:departments,id',
        ], [
            'name.required' => 'اكتب اسم الصنف',
            'category.required' => 'اختر التصنيف',
            'quantity.required' => 'اكتب الكمية',
        ]);

        InventoryItem::create($data);

        ActivityLog::record($request->user()->id, 'INVENTORY_ADDED', "إضافة صنف: {$data['name']} (كمية {$data['quantity']})");

        return back()->with('success', 'تم إضافة الصنف');
    }

    public function update(Request $request, InventoryItem $inventoryItem)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:parts,tools,consumables,other',
            'brand' => 'nullable|string|max:255',
            'part_number' => 'nullable|string|max:255',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'cost_price' => 'nullable|numeric|min:0',
            'sell_price' => 'nullable|numeric|min:0',
            'min_quantity' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'department_id' => 'nullable|exists:departments,id',
        ], [
            'name.required' => 'اكتب اسم الصنف',
        ]);

        $inventoryItem->update($data);

        ActivityLog::record($request->user()->id, 'INVENTORY_UPDATED', "تحديث صنف: {$inventoryItem->name}");

        return back()->with('success', 'تم تحديث الصنف');
    }

    public function destroy(Request $request, InventoryItem $inventoryItem)
    {
        if ($inventoryItem->usedParts()->count() > 0) {
            return back()->with('error', 'الصنف مستخدم في طلبات — مش ممكن حذفه');
        }

        $name = $inventoryItem->name;
        $inventoryItem->delete();

        ActivityLog::record($request->user()->id, 'INVENTORY_DELETED', "حذف صنف: {$name}");

        return back()->with('success', 'تم حذف الصنف');
    }

    /** تعديل سريع للكمية (إضافة/خصم) */
    public function adjust(Request $request, InventoryItem $inventoryItem)
    {
        $data = $request->validate([
            'direction' => 'required|in:add,remove',
            'amount' => 'required|numeric|min:0.01',
        ]);

        if ($data['direction'] === 'add') {
            $inventoryItem->increment('quantity', $data['amount']);
        } else {
            if ($inventoryItem->quantity < $data['amount']) {
                return back()->with('error', 'الكمية المتاحة أقل من المطلوب خصمه');
            }
            $inventoryItem->decrement('quantity', $data['amount']);
        }

        ActivityLog::record($request->user()->id, 'INVENTORY_UPDATED', "تعديل مخزون {$inventoryItem->name}: {$data['direction']} {$data['amount']} (أصبح {$inventoryItem->fresh()->quantity})");

        return back()->with('success', 'تم تعديل الكمية');
    }
}
