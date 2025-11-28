<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property float $total_cost
 * @property float $yield_quantity
 * @property string $yield_unit
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Recipe extends Model
{
    protected $fillable = [
        'name',
        'description',
        'total_cost',
        'yield_quantity',
        'yield_unit',
    ];

    protected $casts = [
        'total_cost' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(RecipeItem::class);
    }

    public function menu()
    {
        return $this->hasOne(Menu::class);
    }

    // Calculate total cost from recipe items
    public function calculateTotalCost()
    {
        $this->total_cost = $this->items()->sum('cost');
        $this->save();
        return $this->total_cost;
    }

    // Get cost per unit
    public function getCostPerUnit()
    {
        return $this->yield_quantity > 0 ? $this->total_cost / $this->yield_quantity : 0;
    }
}
