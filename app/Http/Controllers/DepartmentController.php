<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class DepartmentController extends Controller
{
    public function index(DepartmentService $departmentService): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Department::class);
        // The Controller handles the performance logic (Eager Loading)
        $departments = $departmentService->getDepartments();

        // The Resource handles the formatting
        return DepartmentResource::collection($departments);
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/departments
     */
    public function store(StoreDepartmentRequest $request, DepartmentService $departmentService): DepartmentResource
    {
        // Create
        $department = $departmentService->saveDepartment($request->validatedSnake());

        // Return the resource + 201 Created status
        return new DepartmentResource($department);
    }

    /**
     * Display the specified resource.
     * GET /api/departments/{id}
     */
    public function show(DepartmentService $departmentService, Department $department): DepartmentResource
    {
        // 1. Find or 404
        $department = $departmentService->getDepartmentById($department->id);
        $this->authorize('view', $department);

        // 2. Return single resource
        return new DepartmentResource($department);
    }

    /**
     * Update the specified resource in storage.
     * PUT/PATCH /api/departments/{id}
     */
    public function update(UpdateDepartmentRequest $request, DepartmentService $departmentService, Department $department): DepartmentResource
    {
        $department = $departmentService->updateDepartment($request->validatedSnake(), $department->id);

        return new DepartmentResource($department);
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/departments/{id}
     */
    public function destroy(DepartmentService $departmentService, int $id): JsonResponse|Response
    {
        $department = $departmentService->getDepartmentById($id);
        $this->authorize('delete', $department);
        // You cannot delete a department if it still has employees!
        if ($department->employees_count > 0) {
            return response()->json([
                'message' => 'Cannot delete department with active employees. Please reassign them first.',
            ], 422); // Unprocessable Entity
        }

        // 2. Delete
        $department->delete();

        // 3. Return 204 No Content (Standard for DELETE)
        return response()->noContent();
    }
}
