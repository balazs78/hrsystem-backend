<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\Auth;

class EmployeeObserver
{
    private $fieldMappings = [
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'address' => 'Address',
        'birthdate' => 'Birthdate',
        'hired_at' => 'Hired At',
        'profile_image' => 'Profile Image',
        'status' => 'Status',
        'manager_id' => 'Manager',
        'position_id' => 'Position',
        'department_id' => 'Department',
    ];

    public function created(Employee $employee): void
    {
        $userId = Auth::id();
        if ($userId === null) {
            return;
        }

        AuditLog::create([
            'user_id' => $userId,
            'employee_id' => $employee->id,
            'action' => "Employee {$employee->full_name} has been created",
            'new_value' => $employee->first_name.' '.$employee->last_name,
        ]);
    }

    public function updated(Employee $employee): void
    {
        $userId = Auth::id();
        if ($userId === null) {
            return;
        }

        $changes = $employee->getChanges(); // e.g., ['name' => 'New Name', 'email' => 'new@email.com']
        foreach ($changes as $field => $newValue) {
            $actionText = '';
            $oldValue = $employee->getOriginal($field);
            // 1. Handle Date Objects
            if ($newValue instanceof Carbon || $oldValue instanceof Carbon) {
                if ($newValue == $oldValue) {
                    continue;
                }
            }
            $oldValue = $this->normalizeAuditValue($oldValue);
            $newValue = $this->normalizeAuditValue($newValue);
            $actionText = $this->getActionText($field, $oldValue, $newValue);
            if (! empty($actionText)) {
                AuditLog::create([
                    'user_id' => $userId,
                    'employee_id' => $employee->id,
                    'action' => $actionText,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                ]);
            }
        }
    }

    public function deleted(Employee $employee): void
    {
        $userId = Auth::id();
        if ($userId === null) {
            return;
        }

        AuditLog::create([
            'user_id' => $userId,
            'employee_id' => $employee->id,
            'action' => "Employee {$employee->first_name} {$employee->last_name} has been deleted",
        ]);
    }

    private function getActionText(string $field, ?string $oldValue, ?string $newValue): string
    {
        $actionText = '';
        $simpleWatchedFields = ['first_name', 'last_name', 'email', 'phone', 'address', 'birthdate', 'hired_at', 'profile_image', 'status'];
        $otherWatchedFields = ['manager_id', 'position_id', 'department_id'];
        if (in_array($field, array_merge($simpleWatchedFields, $otherWatchedFields))) {

            $fieldName = $field;
            if (in_array($field, $otherWatchedFields)) {
                $oldValue = $this->getValueForForeignKey($field, $oldValue);
                $newValue = $this->getValueForForeignKey($field, $newValue);
                $fieldName = str_replace('_id', '', $field);
            }
            $actionText = "The {$this->fieldMappings[$field]} of the employee has changed from '{$oldValue}' to '{$newValue}'";
        }

        return $actionText;
    }

    private function getValueForForeignKey(string $field, ?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($field) {
            'manager_id' => Employee::find($value)->full_name,
            'department_id' => Department::find($value)->name,
            'position_id' => Position::find($value)->title
        };
    }

    private function normalizeAuditValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof DateTimeInterface
            ? $value->format('Y-m-d')
            : (string) $value;
    }
}
