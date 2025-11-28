<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'order_number' => $this->order_number,
            'branch_id' => $this->branch_id,
            'table_id' => $this->table_id,
            'cashier_id' => $this->cashier_id,
            'order_type' => $this->order_type,
            'status' => $this->status,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'delivery_address' => $this->delivery_address,
            'notes' => $this->notes,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'service_charge' => $this->service_charge,
            'discount' => $this->discount,
            'total_amount' => $this->total_amount,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
