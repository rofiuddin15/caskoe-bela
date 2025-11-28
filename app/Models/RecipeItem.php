<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
