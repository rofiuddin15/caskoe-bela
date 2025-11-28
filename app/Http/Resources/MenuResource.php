<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'menu_category_id' => $this->menu_category_id,
            'recipe_id' => $this->recipe_id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'image' => $this->image,
            'price' => $this->price,
            'cost' => $this->cost,
            'preparation_time' => $this->preparation_time,
            'is_available' => $this->is_available,
            'is_active' => $this->is_active,
            'profit_margin' => $this->profit_margin,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
