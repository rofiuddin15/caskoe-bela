<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
