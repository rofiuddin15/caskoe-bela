<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $order_number
 * @property int $branch_id
 * @property int|null $table_id
 * @property int $cashier_id
 * @property string $order_type
 * @property string $status
 * @property string|null $customer_name
 * @property string|null $customer_phone
 * @property string|null $delivery_address
 * @property string|null $notes
 * @property float $subtotal
 * @property float $tax_amount
 * @property float $service_charge
 * @property float $discount
 * @property float $total_amount
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Order extends Model
{
    protected $fillable = [
        'order_number',
        'branch_id',
        'table_id',
        'cashier_id',
        'order_type',
        'status',
        'subtotal',
        'tax_amount',
        'service_charge',
        'discount_amount',
        'total_amount',
        'promotion_id',
        'customer_name',
        'customer_phone',
        'delivery_address',
        'notes',
        'completed_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
