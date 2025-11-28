<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $branch_id
 * @property string $employee_code
 * @property string|null $position
 * @property \Illuminate\Support\Carbon|null $hire_date
 * @property float|null $salary
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Employee extends Model
{
    protected $fillable = [
        'user_id',
        'branch_id',
        'employee_code',
        'position',
        'hire_date',
        'salary',
        'is_active',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'salary' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
