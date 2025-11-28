<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'stock_id',
        'type',
        'quantity',
        'balance_after',
        'reference_type',
        'reference_id',
        'from_branch_id',
        'to_branch_id',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function fromBranch()
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch()
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Get the reference model (polymorphic-like)
    public function reference()
    {
        if ($this->reference_type && $this->reference_id) {
            $class = "App\\Models\\" . $this->reference_type;
            if (class_exists($class)) {
                return $class::find($this->reference_id);
            }
        }
        return null;
    }
}
