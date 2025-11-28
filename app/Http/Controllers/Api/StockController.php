<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Stock;
use App\Http\Resources\StockResource;

class StockController extends Controller
{
    /**
     * Daftar semua stok
     *
     * Menampilkan stok bahan baku di semua cabang atau cabang tertentu.
     *
     * @authenticated
     * @queryParam branch_id integer Filter berdasarkan cabang. Example: 1
     * @queryParam raw_material_id integer Filter berdasarkan bahan baku. Example: 1
     * @queryParam low_stock boolean Filter stok yang di bawah minimum. Example: true
     */
    public function index(Request $request)
    {
        $query = Stock::query();

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('raw_material_id')) {
            $query->where('raw_material_id', $request->raw_material_id);
        }

        if ($request->has('low_stock') && $request->low_stock) {
            $query->whereHas('rawMaterial', function($q) {
                $q->whereColumn('stocks.quantity', '<=', 'raw_materials.min_stock');
            });
        }

        $stocks = $query->with(['rawMaterial', 'branch'])->get();

        return StockResource::collection($stocks);
    }

    /**
     * Tambah stok manual
     *
     * Menambahkan stok bahan baku secara manual (untuk adjustment atau stock opname).
     *
     * @authenticated
     * @bodyParam raw_material_id integer required ID bahan baku. Example: 1
     * @bodyParam branch_id integer required ID cabang. Example: 1
     * @bodyParam quantity numeric required Jumlah perubahan stok (+ untuk tambah, - untuk kurang). Example: 1000
     * @bodyParam type string required Tipe pergerakan (in, out, adjustment). Example: adjustment
     * @bodyParam reference_type string Tipe referensi (manual, purchase_order, recipe). Example: manual
     * @bodyParam notes string Catatan. Example: Stock opname
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'raw_material_id' => 'required|exists:raw_materials,id',
            'branch_id' => 'required|exists:branches,id',
            'quantity' => 'required|numeric',
        ]);

        $stock = Stock::firstOrCreate(
            [
                'raw_material_id' => $validated['raw_material_id'],
                'branch_id' => $validated['branch_id'],
            ],
            [
                'quantity' => 0,
                'reserved_quantity' => 0,
            ]
        );

        $stock->increment('quantity', $validated['quantity']);

        return new StockResource($stock->load(['rawMaterial', 'branch']));
    }

    /**
     * Detail stok
     *
     * Menampilkan detail stok termasuk riwayat pergerakan (stock movements).
     *
     * @authenticated
     * @urlParam id integer required ID stok. Example: 1
     */
    public function show(Stock $stock)
    {
        $stock->load(['rawMaterial', 'branch', 'movements']);
        return new StockResource($stock);
    }

    /**
     * Update stok
     *
     * Memperbarui informasi stok (biasanya untuk koreksi).
     *
     * @authenticated
     * @urlParam id integer required ID stok. Example: 1
     * @bodyParam quantity numeric Jumlah stok baru. Example: 5000
     * @bodyParam notes string Catatan koreksi. Example: Koreksi stok fisik
     */
    public function update(Request $request, Stock $stock)
    {
        $validated = $request->validate([
            'quantity' => 'sometimes|numeric|min:0',
            'reserved_quantity' => 'sometimes|numeric|min:0',
        ]);

        $stock->update($validated);

        return new StockResource($stock->load(['rawMaterial', 'branch']));
    }

    /**
     * Hapus stok
     *
     * Menghapus record stok (jarang digunakan, biasanya untuk cleanup data).
     *
     * @authenticated
     * @urlParam id integer required ID stok. Example: 1
     */
    public function destroy(Stock $stock)
    {
        $stock->delete();

        return response()->json([
            'message' => 'Stock deleted successfully'
        ]);
    }
}
