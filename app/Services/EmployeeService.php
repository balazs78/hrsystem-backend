<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Laravel\Facades\Image;

class EmployeeService
{
    public function getEmployeeList(array $request): LengthAwarePaginator
    {
        // 1. Start with the query
        $query = Employee::query()
            ->with(['department', 'position', 'manager']); // Eager load relations to avoid N+1

        // 2. Apply Filters
        $query->when($request['department'] ?? null, function ($q, $deptId) {
            return $q->whereIn('department_id', $deptId);
        });

        $query->when($request['position'] ?? null, function ($q, $posId) {
            return $q->whereIn('position_id', $posId);
        });

        $query->when($request['manager'] ?? null, function ($q, $managerId) {
            return $q->whereIn('manager_id', $managerId);
        });

        $query->when($request['status'] ?? null, function ($q, $status) {
            return $q->whereIn('status', $status);
        });

        $query->when($request['search'] ?? null, function ($q, $search) {
            return $q->where(function ($subQ) use ($search) {
                $subQ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        });

        $query->when($request['sort'] ?? null, function ($q, $sort) use ($request) {
            $direction = $request['direction'] ?? 'asc';

            return $q->orderByRaw("{$sort} COLLATE NOCASE {$direction}");
        });

        // 3. Paginate (Angular will use 'current_page' and 'last_page' from this)
        $employees = $query
            ->paginate($request['page_size'] ?? 10);

        // 4. Return Resource Collection (Transforms data to JSON)
        return $employees;
    }

    public function getAllManagers(): Collection
    {
        $query = Employee::select('id', 'first_name AS firstName', 'last_name AS lastName', DB::raw("first_name || ' ' || last_name AS fullName"))
            ->where('status', 'active')
            ->whereIn('id', function ($q) {
                $q->select('manager_id')
                    ->distinct()
                    ->from('employees')
                    ->whereNotNull('manager_id');
            });

        return $query->get();
    }

    public function saveEmployee(array $request): Employee
    {
        // Validation is already handled by StoreEmployeeRequest before reaching here.
        // Use a Transaction. If creating the log fails, the employee isn't created.
        $employee = DB::transaction(function () use ($request) {

            $employee = Employee::create($request);

            return $employee;
        });

        // Load relations so the frontend gets the full object immediately
        $employee->load(['department', 'position']);

        return $employee;
    }

    public function updateEmployee(array $employeeData, int $id): Employee
    {
        // Validation is already handled by UpdateEmployeeRequest before reaching here.
        // Use a Transaction. If creating the log fails, the employee isn't created.
        $employee = DB::transaction(function () use ($employeeData, $id) {

            // 1. Find the specific model instance first
            $employee = Employee::findOrFail($id);

            // 2. Perform the update on the instance to fire the observer hooks
            $employee->update($employeeData);

            return $employee;
        });

        // Load relations so the frontend gets the full object immediately
        $employee->load(['department', 'position']);

        return $employee;
    }

    /**
     * Return employee names for the dashboard search.
     *
     * @return Collection<int, Employee>
     */
    public function getEmployeeListWithOnlyNames(Request $request): Collection
    {

        $query = Employee::select('id', 'first_name AS firstName', 'last_name AS lastName', DB::raw("first_name || ' ' || last_name AS fullName"));

        if (! $request->has('allstatuses')) {
            $query->where('status', 'active');
        }
        if ($request->has('query')) {
            $search = $request->input('query');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "$search%")
                    ->orWhere('last_name', 'like', "$search%");
            });
        }

        $result = $query->get();

        return $result;
    }

    public function storeProfileImage(Request $request): string
    {
        if (!$request->hasFile('profileImage')) {
            throw new \Exception('No file uploaded with key "profileImage". Check Angular payload.');
        }
        $activeDisk = config('filesystems.default');
        $file = $request->file('profileImage');
        $filename = time().'.jpg';
        $filepath = 'profiles/'.$filename;
        $targetUrl = Storage::disk($activeDisk)->url($filepath);
        // Resize image to 300x300 and crop (center)
        $img = Image::read($file);
        $img->cover(300, 300);
        $encodedBinary = $img->toJpeg(90)->toString();
        try {
            Storage::disk($activeDisk)->put($filepath, (string) $encodedBinary);
            $img->scale(50, 50);
            $thumbnailPath = 'profiles/thumbnails/'.$filename;
            Storage::disk($activeDisk)->put($thumbnailPath, (string) $img->toJpeg(90)->toString());
        } catch (\Exception $e) {
            Log::error('Failed to store profile image or thumbnail: '.$e->getMessage());
        }
        return $targetUrl;
    }
}
