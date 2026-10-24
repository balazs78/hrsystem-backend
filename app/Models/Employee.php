<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\EmployeeObserver;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(EmployeeObserver::class)]
class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'gender',
        'birthdate',
        'email',
        'phone',
        'address',
        'status',
        'profile_image',
        'department_id',
        'position_id',
        'manager_id',
        'hired_at',
        'contract_end_date',
    ];

    protected $casts = [
        'contract_end_date' => 'date',
        'hired_at' => 'date',
    ];

    protected function fullName(): Attribute
    {
        return Attribute::get(
            fn (mixed $value, array $attributes) => $attributes['first_name'].' '.$attributes['last_name'],
        );
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function serializeDate(DateTimeInterface $date): string
    {
        return parent::serializeDate($date);
    }

    protected function address(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value ? str_replace(["\r\n", "\r"], "\n", $value) : null
        );
    }

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'hired_at' => 'date',
            'contract_end_date' => 'date',
        ];
    }
}
