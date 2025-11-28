<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Menu;
use App\Http\Resources\MenuResource;

class MenuController extends Controller
{
    /**
     * Daftar semua menu
     *
     * Menampilkan daftar menu dengan kategori dan resep. Mendukung filter dan pencarian.
     *
     * @authenticated
     * @queryParam category_id integer Filter berdasarkan ID kategori. Example: 1
     * @queryParam is_available integer Filter menu yang tersedia (0/1). Example: 1
     * @queryParam is_active integer Filter menu aktif (0/1). Example: 1
     * @queryParam search string Cari berdasarkan nama, kode, atau deskripsi. Example: kopi
     * @queryParam per_page integer Jumlah data per halaman. Default: 15. Example: 10
     */
    public function index(Request $request)
    {
        $query = Menu::with(['category', 'recipe']);

        if ($request->has('category_id')) {
            $query->where('menu_category_id', $request->category_id);
        }

        if ($request->has('is_available')) {
            $query->where('is_available', $request->is_available);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $menus = $query->paginate($request->get('per_page', 15));

        return response()->json($menus);
    }

    /**
     * Buat menu baru
     *
     * Membuat item menu baru. HPP (cost) akan otomatis dihitung dari resep jika recipe_id disediakan.
     *
     * @authenticated
     * @bodyParam menu_category_id integer required ID kategori menu. Example: 1
     * @bodyParam recipe_id integer ID resep untuk auto-calculate HPP. Example: 1
     * @bodyParam name string required Nama menu. Example: Kopi Susu Signature
     * @bodyParam code string required Kode unik menu. Example: MENU001
     * @bodyParam description string Deskripsi menu. Example: Kopi susu dengan rasa nikmat
     * @bodyParam image string URL atau path gambar menu. Example: /storage/images/menu/kopi-susu.jpg
     * @bodyParam price numeric required Harga jual. Example: 25000
     * @bodyParam preparation_time integer Waktu persiapan dalam menit. Example: 5
     *
     * @response 201 {
     *   "id": 1,
     *   "name": "Kopi Susu Signature",
     *   "price": 25000,
     *   "cost": 8500,
     *   "profit_margin": 66
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'menu_category_id' => 'required|exists:menu_categories,id',
            'recipe_id' => 'nullable|exists:recipes,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:menus,code',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'preparation_time' => 'nullable|integer',
        ]);

        // Auto-calculate cost from recipe if recipe_id is provided
        if (isset($validated['recipe_id'])) {
            $recipe = \App\Models\Recipe::find($validated['recipe_id']);
            $validated['cost'] = $recipe->total_cost ?? 0;
        }

        $menu = Menu::create($validated);

        return new MenuResource($menu->load(['category', 'recipe']));
    }

    /**
     * Detail menu
     *
     * Menampilkan detail menu termasuk kategori, resep, dan bahan baku yang digunakan.
     *
     * @authenticated
     * @urlParam menu integer required ID menu. Example: 1
     */
    public function show(Menu $menu)
    {
        $menu->load(['category', 'recipe.items.rawMaterial']);
        return new MenuResource($menu);
    }

    /**
     * Update menu
     *
     * Memperbarui data menu. HPP akan otomatis diupdate jika recipe_id diubah.
     *
     * @authenticated
     * @urlParam menu integer required ID menu. Example: 1
     * @bodyParam menu_category_id integer ID kategori menu. Example: 1
     * @bodyParam recipe_id integer ID resep untuk auto-calculate HPP. Example: 1
     * @bodyParam name string Nama menu. Example: Kopi Susu Premium
     * @bodyParam code string Kode unik menu. Example: MENU001
     * @bodyParam description string Deskripsi menu. Example: Kopi susu premium
     * @bodyParam image string URL atau path gambar menu. Example: /storage/images/menu/kopi-susu.jpg
     * @bodyParam price numeric Harga jual. Example: 28000
     * @bodyParam preparation_time integer Waktu persiapan dalam menit. Example: 5
     * @bodyParam is_available boolean Menu tersedia untuk dijual. Example: true
     * @bodyParam is_active boolean Status aktif menu. Example: true
     */
    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'menu_category_id' => 'sometimes|exists:menu_categories,id',
            'recipe_id' => 'nullable|exists:recipes,id',
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:menus,code,' . $menu->id,
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'preparation_time' => 'nullable|integer',
            'is_available' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // Update cost from recipe if recipe_id is changed
        if (isset($validated['recipe_id'])) {
            $recipe = \App\Models\Recipe::find($validated['recipe_id']);
            $validated['cost'] = $recipe->total_cost ?? 0;
        }

        $menu->update($validated);

        return new MenuResource($menu->load(['category', 'recipe']));
    }

    /**
     * Hapus menu
     *
     * Menghapus menu dari database.
     *
     * @authenticated
     * @urlParam menu integer required ID menu. Example: 1
     *
     * @response 200 {
     *   "message": "Menu deleted successfully"
     * }
     */
    public function destroy(Menu $menu)
    {
        $menu->delete();

        return response()->json([
            'message' => 'Menu deleted successfully'
        ]);
    }
}
