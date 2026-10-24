<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'cost_center_code', 'manager_id'];

    public function employees(): HasMany
    {
        return $this->HasMany(Employee::class, 'department_id');
    }

    public function manager(): HasOne
    {
        return $this->hasOne(Employee::class, 'id', 'manager_id');
    }
}
