<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'value',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'min_purchase',
        'max_usage',
        'usage_count',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'min_purchase' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // Check if promotion is currently valid
    public function isValid()
    {
        $now = now();
        $today = $now->toDateString();

        if (!$this->is_active) return false;
        if ($today < $this->start_date || $today > $this->end_date) return false;
        if ($this->max_usage && $this->usage_count >= $this->max_usage) return false;

        return true;
    }
}
