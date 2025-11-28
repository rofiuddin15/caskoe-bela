<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Http\Resources\RecipeResource;
use Illuminate\Support\Facades\DB;

class RecipeController extends Controller
{
    /**
     * Daftar semua resep
     *
     * Menampilkan daftar resep menu dengan kalkulasi HPP.
     *
     * @authenticated
     * @queryParam search string Cari berdasarkan nama resep. Example: kopi susu
     */
    public function index(Request $request)
    {
        $query = Recipe::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $recipes = $query->with('items.rawMaterial')->get();

        return RecipeResource::collection($recipes);
    }

    /**
     * Buat resep baru
     *
     * Membuat resep menu dengan daftar bahan baku. HPP dihitung otomatis.
     *
     * @authenticated
     * @bodyParam name string required Nama resep. Example: Kopi Susu
     * @bodyParam description string Deskripsi resep. Example: Kopi susu signature
     * @bodyParam yield_quantity numeric required Jumlah output. Example: 1
     * @bodyParam yield_unit string required Satuan output. Example: cup
     * @bodyParam items array required Daftar bahan baku.
     * @bodyParam items.*.raw_material_id integer required ID bahan baku. Example: 1
     * @bodyParam items.*.quantity numeric required Jumlah bahan. Example: 15
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'yield_quantity' => 'required|numeric|min:0',
            'yield_unit' => 'required|string|max:20',
            'items' => 'required|array',
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.quantity' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $recipe = Recipe::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'yield_quantity' => $validated['yield_quantity'],
                'yield_unit' => $validated['yield_unit'],
                'total_cost' => 0,
            ]);

            foreach ($validated['items'] as $item) {
                $recipe->items()->create($item);
            }

            $recipe->calculateTotalCost();

            DB::commit();

            return new RecipeResource($recipe->load('items.rawMaterial'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create recipe', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Detail resep
     *
     * Menampilkan detail resep termasuk semua bahan baku dan total HPP.
     *
     * @authenticated
     * @urlParam id integer required ID resep. Example: 1
     */
    public function show(Recipe $recipe)
    {
        $recipe->load('items.rawMaterial', 'menu');
        return new RecipeResource($recipe);
    }

    /**
     * Update resep
     *
     * Memperbarui resep dan bahan bakunya. HPP dihitung ulang otomatis.
     *
     * @authenticated
     * @urlParam id integer required ID resep. Example: 1
     * @bodyParam name string Nama resep. Example: Kopi Susu Premium
     * @bodyParam description string Deskripsi. Example: Kopi susu dengan kualitas premium
     * @bodyParam yield_quantity numeric Jumlah output. Example: 1
     * @bodyParam yield_unit string Satuan output. Example: cup
     * @bodyParam items array Daftar bahan baku.
     * @bodyParam items.*.raw_material_id integer ID bahan baku. Example: 1
     * @bodyParam items.*.quantity numeric Jumlah bahan. Example: 20
     */
    public function update(Request $request, Recipe $recipe)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'yield_quantity' => 'sometimes|numeric|min:0',
            'yield_unit' => 'sometimes|string|max:20',
            'items' => 'sometimes|array',
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.quantity' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $recipe->update([
                'name' => $validated['name'] ?? $recipe->name,
                'description' => $validated['description'] ?? $recipe->description,
                'yield_quantity' => $validated['yield_quantity'] ?? $recipe->yield_quantity,
                'yield_unit' => $validated['yield_unit'] ?? $recipe->yield_unit,
            ]);

            if (isset($validated['items'])) {
                $recipe->items()->delete();
                foreach ($validated['items'] as $item) {
                    $recipe->items()->create($item);
                }
            }

            $recipe->calculateTotalCost();

            DB::commit();

            return new RecipeResource($recipe->load('items.rawMaterial'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update recipe', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Hapus resep
     *
     * Menghapus resep dari database.
     *
     * @authenticated
     * @urlParam id integer required ID resep. Example: 1
     */
    public function destroy(Recipe $recipe)
    {
        $recipe->delete();

        return response()->json([
            'message' => 'Recipe deleted successfully'
        ]);
    }
}
