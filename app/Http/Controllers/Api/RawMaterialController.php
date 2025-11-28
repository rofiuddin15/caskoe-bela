<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RawMaterial;
use App\Http\Resources\RawMaterialResource;

class RawMaterialController extends Controller
{
    /**
     * Daftar semua bahan baku
     *
     * Menampilkan daftar bahan baku/raw materials untuk inventori.
     *
     * @authenticated
     * @queryParam search string Cari berdasarkan nama atau kode. Example: kopi
     * @queryParam category string Filter berdasarkan kategori. Example: beverage
     */
    public function index(Request $request)
    {
        $query = RawMaterial::query();

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%');
            });
        }

        $materials = $query->with('stocks')->get();

        return RawMaterialResource::collection($materials);
    }

    /**
     * Buat bahan baku baru
     *
     * Membuat data bahan baku baru untuk inventori.
     *
     * @authenticated
     * @bodyParam name string required Nama bahan baku. Example: Kopi Arabica
     * @bodyParam code string required Kode unik bahan baku. Example: RM001
     * @bodyParam unit string required Satuan (gram, ml, pcs, kg). Example: gram
     * @bodyParam unit_price numeric required Harga per satuan. Example: 150
     * @bodyParam min_stock numeric Stok minimum untuk alert. Example: 5000
     * @bodyParam category string Kategori bahan. Example: coffee
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:raw_materials,code',
            'unit' => 'required|string|max:20',
            'unit_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'min_stock' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $material = RawMaterial::create($validated);

        return new RawMaterialResource($material);
    }

    /**
     * Detail bahan baku
     *
     * Menampilkan detail bahan baku termasuk stok di semua cabang.
     *
     * @authenticated
     * @urlParam id integer required ID bahan baku. Example: 1
     */
    public function show(RawMaterial $rawMaterial)
    {
        $rawMaterial->load(['stocks.branch', 'recipeItems.recipe']);
        return new RawMaterialResource($rawMaterial);
    }

    /**
     * Update bahan baku
     *
     * Memperbarui data bahan baku.
     *
     * @authenticated
     * @urlParam id integer required ID bahan baku. Example: 1
     * @bodyParam name string Nama bahan baku. Example: Kopi Robusta
     * @bodyParam code string Kode bahan baku. Example: RM002
     * @bodyParam unit string Satuan. Example: kg
     * @bodyParam unit_price numeric Harga per satuan. Example: 120
     * @bodyParam min_stock numeric Stok minimum. Example: 10000
     * @bodyParam category string Kategori. Example: coffee
     */
    public function update(Request $request, RawMaterial $rawMaterial)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:raw_materials,code,' . $rawMaterial->id,
            'unit' => 'sometimes|string|max:20',
            'unit_price' => 'sometimes|numeric|min:0',
            'description' => 'nullable|string',
            'min_stock' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $rawMaterial->update($validated);

        return new RawMaterialResource($rawMaterial);
    }

    /**
     * Hapus bahan baku
     *
     * Menghapus data bahan baku dari database.
     *
     * @authenticated
     * @urlParam id integer required ID bahan baku. Example: 1
     */
    public function destroy(RawMaterial $rawMaterial)
    {
        $rawMaterial->delete();

        return response()->json([
            'message' => 'Raw material deleted successfully'
        ]);
    }
}
