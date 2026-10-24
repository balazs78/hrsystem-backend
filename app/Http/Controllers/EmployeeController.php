<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SearchEmployeeRequest;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeListResource;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmployeeController extends Controller
{
    public function index(SearchEmployeeRequest $request, EmployeeService $employeeService): AnonymousResourceCollection
    {
        $employees = $employeeService->getEmployeeList($request->validatedSnake());

        return EmployeeListResource::collection($employees)->additional([
            'meta' => [
                'total_users' => $employees->count(),
            ],
        ]);
    }

    /**
     * Store a newly created employee in storage.
     */
    public function store(StoreEmployeeRequest $request, EmployeeService $employeeService): EmployeeResource
    {
        $validatedData = $request->validatedSnake();
        if ($request->hasFile('profileImage')) {
            $validatedData['profile_image'] = $employeeService->storeProfileImage($request);
        }
        $employee = $employeeService->saveEmployee($validatedData);

        return new EmployeeResource($employee);
    }

    /**
     * Display the specified employee.
     */
    public function show(Employee $employee): EmployeeResource
    {
        $this->authorize('view', $employee);
        // Laravel's "Route Model Binding" automatically fetches the Employee by ID.
        // We just need to ensure relations are loaded.
        $employee->load(['department', 'position', 'manager', 'subordinates']);

        return new EmployeeResource($employee);
    }

    /**
     * Update the specified employee in storage.
     */
    public function update(UpdateEmployeeRequest $request, EmployeeService $employeeService, Employee $employee): EmployeeResource
    {
        $validatedData = $request->validatedSnake();
        if ($request->hasFile('profileImage')) {
            $validatedData['profile_image'] = $employeeService->storeProfileImage($request);
        }
        $employee = $employeeService->updateEmployee($validatedData, $employee->id);

        return new EmployeeResource($employee->refresh()->load(['department', 'position', 'manager', 'subordinates'])); // Refresh to get latest data
    }

    /**
     * Remove the specified employee (Soft Delete).
     */
    public function destroy(Employee $employee): JsonResponse
    {
        $this->authorize('delete', $employee);
        $employee->delete(); // Soft delete based on model trait

        return response()->json(null, 204); // 204 = No Content
    }

    /**
     * Restore a soft-deleted employee.
     * Route: POST /api/employees/{id}/restore
     */
    public function restore(string $id): EmployeeResource
    {
        // We must query withTrashed() because normal Route Binding won't find it.
        $employee = Employee::withTrashed()->findOrFail($id);
        $this->authorize('restore', $employee);

        $employee->restore();

        return new EmployeeResource($employee);
    }

    public function getEmployeeNames(Request $request, EmployeeService $employeeService): JsonResponse
    {
        $names = $employeeService->getEmployeeListWithOnlyNames($request);

        return response()->json($names);
    }

    public function getAllManagers(EmployeeService $employeeService): JsonResponse
    {
        $managers = $employeeService->getAllManagers();

        return response()->json($managers);
    }
}
