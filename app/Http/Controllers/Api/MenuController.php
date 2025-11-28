<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Menu;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
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
     * Store a newly created resource in storage.
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

        return response()->json($menu->load(['category', 'recipe']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Menu $menu)
    {
        $menu->load(['category', 'recipe.items.rawMaterial']);
        return response()->json($menu);
    }

    /**
     * Update the specified resource in storage.
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

        return response()->json($menu->load(['category', 'recipe']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Menu $menu)
    {
        $menu->delete();

        return response()->json([
            'message' => 'Menu deleted successfully'
        ]);
    }
}
