<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MenuCategory;
use App\Http\Resources\MenuCategoryResource;

class MenuCategoryController extends Controller
{
    /**
     * Daftar semua kategori menu
     *
     * Menampilkan daftar kategori menu (Minuman, Makanan, Snack, dll).
     *
     * @authenticated
     * @queryParam search string Cari berdasarkan nama kategori. Example: minuman
     */
    public function index(Request $request)
    {
        $query = MenuCategory::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $categories = $query->orderBy('sort_order')->get();

        return MenuCategoryResource::collection($categories);
    }

    /**
     * Buat kategori menu baru
     *
     * Membuat kategori baru untuk pengelompokan menu.
     *
     * @authenticated
     * @bodyParam name string required Nama kategori. Example: Minuman Panas
     * @bodyParam description string Deskripsi kategori. Example: Kategori untuk minuman panas
     * @bodyParam sort_order integer Urutan tampilan. Example: 1
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $category = MenuCategory::create($validated);

        return new MenuCategoryResource($category);
    }

    /**
     * Detail kategori menu
     *
     * Menampilkan detail kategori termasuk menu yang ada di kategori ini.
     *
     * @authenticated
     * @urlParam id integer required ID kategori. Example: 1
     */
    public function show(MenuCategory $menuCategory)
    {
        $menuCategory->load('menus');
        return new MenuCategoryResource($menuCategory);
    }

    /**
     * Update kategori menu
     *
     * Memperbarui data kategori menu.
     *
     * @authenticated
     * @urlParam id integer required ID kategori. Example: 1
     * @bodyParam name string Nama kategori. Example: Minuman Dingin
     * @bodyParam description string Deskripsi. Example: Kategori minuman dingin
     * @bodyParam sort_order integer Urutan tampilan. Example: 2
     * @bodyParam is_active boolean Status aktif. Example: true
     */
    public function update(Request $request, MenuCategory $menuCategory)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $menuCategory->update($validated);

        return new MenuCategoryResource($menuCategory);
    }

    /**
     * Hapus kategori menu
     *
     * Menghapus kategori menu dari database.
     *
     * @authenticated
     * @urlParam id integer required ID kategori. Example: 1
     */
    public function destroy(MenuCategory $menuCategory)
    {
        $menuCategory->delete();

        return response()->json([
            'message' => 'Menu category deleted successfully'
        ]);
    }
}
