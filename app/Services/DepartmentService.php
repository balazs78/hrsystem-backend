<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Department;
use Illuminate\Database\Eloquent\Collection;

class DepartmentService
{
    public function getDepartments(): Collection
    {
        return Department::withCount('employees')->with(['manager'])->get();

    }

    public function getDepartmentById(int $id): Department
    {
        return Department::withCount('employees')->findOrFail($id);
    }

    public function saveDepartment(array $department): Department
    {
        return Department::create($department);
    }

    public function updateDepartment(array $departmentData, int $id): Department
    {
        $department = Department::findOrFail($id);

        // 2. Update
        $result = $department->update($departmentData);

        return $department->refresh(); // Return the updated model instance
    }
}
