<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $menu_category_id
 * @property int|null $recipe_id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property string|null $image
 * @property float $price
 * @property float $cost
 * @property int|null $preparation_time
 * @property bool $is_available
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read float $profit_margin
 */
class Menu extends Model
{
    protected $fillable = [
        'menu_category_id',
        'recipe_id',
        'name',
        'code',
        'description',
        'image',
        'price',
        'cost',
        'preparation_time',
        'is_available',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'is_available' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(MenuCategory::class, 'menu_category_id');
    }

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Calculate profit margin
    public function getProfitMarginAttribute()
    {
        if ($this->price > 0) {
            return (($this->price - $this->cost) / $this->price) * 100;
        }
        return 0;
    }
}
