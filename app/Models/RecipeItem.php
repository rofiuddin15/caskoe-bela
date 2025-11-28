<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $recipe_id
 * @property int $raw_material_id
 * @property float $quantity
 * @property float $cost
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class RecipeItem extends Model
{
    protected $fillable = [
        'recipe_id',
        'raw_material_id',
        'quantity',
        'cost',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'cost' => 'decimal:2',
    ];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }

    // Auto-calculate cost based on quantity and raw material price
    public function calculateCost()
    {
        $rawMaterial = $this->rawMaterial;
        if ($rawMaterial) {
            $this->cost = $this->quantity * $rawMaterial->unit_price;
            $this->save();
        }
        return $this->cost;
    }
}
