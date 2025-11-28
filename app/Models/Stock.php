<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $raw_material_id
 * @property int $branch_id
 * @property float $quantity
 * @property float $reserved_quantity
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read float $available_quantity
 */
class Stock extends Model
{
    protected $fillable = [
        'raw_material_id',
        'branch_id',
        'quantity',
        'reserved_quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'reserved_quantity' => 'decimal:2',
    ];

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }

    // Get available quantity (total - reserved)
    public function getAvailableQuantityAttribute()
    {
        return $this->quantity - $this->reserved_quantity;
    }

    // Check if stock is below minimum
    public function isLowStock()
    {
        return $this->quantity <= $this->rawMaterial->min_stock;
    }
}
