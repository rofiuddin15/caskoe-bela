<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Http\Resources\PurchaseOrderResource;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    /**
     * Daftar semua purchase order
     *
     * Menampilkan daftar PO pembelian bahan baku dari supplier.
     *
     * @authenticated
     * @queryParam supplier_id integer Filter berdasarkan supplier. Example: 1
     * @queryParam branch_id integer Filter berdasarkan cabang. Example: 1
     * @queryParam status string Filter status (pending, approved, received, cancelled). Example: pending
     * @queryParam start_date date Tanggal mulai. Example: 2025-01-01
     * @queryParam end_date date Tanggal akhir. Example: 2025-12-31
     */
    public function index(Request $request)
    {
        $query = PurchaseOrder::query();

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('order_date', [$request->start_date, $request->end_date]);
        }

        $pos = $query->with(['supplier', 'branch', 'items.rawMaterial'])->get();

        return PurchaseOrderResource::collection($pos);
    }

    /**
     * Buat purchase order baru
     *
     * Membuat PO baru untuk pembelian bahan baku. Stok otomatis bertambah saat status menjadi 'received'.
     *
     * @authenticated
     * @bodyParam supplier_id integer required ID supplier. Example: 1
     * @bodyParam branch_id integer required ID cabang tujuan. Example: 1
     * @bodyParam order_date date required Tanggal order. Example: 2025-01-15
     * @bodyParam expected_delivery_date date Tanggal estimasi pengiriman. Example: 2025-01-20
     * @bodyParam notes string Catatan PO. Example: Order urgent
     * @bodyParam items array required Daftar item yang dipesan.
     * @bodyParam items.*.raw_material_id integer required ID bahan baku. Example: 1
     * @bodyParam items.*.quantity numeric required Jumlah. Example: 10000
     * @bodyParam items.*.unit_price numeric required Harga per unit. Example: 150
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'branch_id' => 'required|exists:branches,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $totalAmount += $item['quantity'] * $item['unit_price'];
            }

            $po = PurchaseOrder::create([
                'po_number' => 'PO-' . date('Ymd') . '-' . str_pad(PurchaseOrder::count() + 1, 4, '0', STR_PAD_LEFT),
                'supplier_id' => $validated['supplier_id'],
                'branch_id' => $validated['branch_id'],
                'created_by' => auth()->id(),
                'order_date' => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'status' => 'pending',
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $po->items()->create([
                    'raw_material_id' => $item['raw_material_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            DB::commit();

            return new PurchaseOrderResource($po->load(['supplier', 'branch', 'items.rawMaterial']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create purchase order', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Detail purchase order
     *
     * Menampilkan detail PO termasuk semua item dan supplier.
     *
     * @authenticated
     * @urlParam id integer required ID purchase order. Example: 1
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'branch', 'creator', 'items.rawMaterial']);
        return new PurchaseOrderResource($purchaseOrder);
    }

    /**
     * Update purchase order
     *
     * Memperbarui PO. Hanya bisa diupdate jika status masih pending.
     *
     * @authenticated
     * @urlParam id integer required ID purchase order. Example: 1
     * @bodyParam expected_delivery_date date Tanggal estimasi pengiriman. Example: 2025-01-22
     * @bodyParam notes string Catatan. Example: Updated notes
     * @bodyParam status string Status PO (pending, approved, received, cancelled). Example: approved
     * @bodyParam items array Daftar item (untuk update item).
     * @bodyParam items.*.raw_material_id integer ID bahan baku. Example: 1
     * @bodyParam items.*.quantity numeric Jumlah. Example: 12000
     * @bodyParam items.*.unit_price numeric Harga per unit. Example: 145
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:pending,approved,received,cancelled',
        ]);

        $purchaseOrder->update($validated);

        return new PurchaseOrderResource($purchaseOrder->load(['supplier', 'branch', 'items.rawMaterial']));
    }

    /**
     * Hapus purchase order
     *
     * Menghapus PO. Hanya PO dengan status pending yang bisa dihapus.
     *
     * @authenticated
     * @urlParam id integer required ID purchase order. Example: 1
     */
    public function destroy(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'pending') {
            return response()->json(['message' => 'Only pending purchase orders can be deleted'], 400);
        }

        $purchaseOrder->delete();

        return response()->json([
            'message' => 'Purchase order deleted successfully'
        ]);
    }
}
