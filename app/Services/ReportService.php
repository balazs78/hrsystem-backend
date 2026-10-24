<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Aggregates all data required for the main dashboard.
     */
    public function getDashboardSummary(): array
    {
        // 1. Fetch Key Metrics
        // Note: In a high-traffic app, we might Cache::remember() these for 10-60 mins.
        $totalEmployees = Employee::where('status', 'active')->count();
        $departmentsCount = Department::count();
        $positionsCount = Position::count();
        $contractorCount = Employee::where('status', 'active')->where('contract_end_date', '>=', Carbon::now())->count();
        $managerCount = Employee::where('status', 'active')->where('position_id', Position::where('title', 'Manager')->value('id'))->count();
        $regularEmployeesCount = Employee::where('status', 'active')->whereNull('contract_end_date')->count();
        $managerIds = Employee::whereNotNull('manager_id')
            ->distinct()
            ->pluck('manager_id');

        $managerCount = Employee::whereIn('id', $managerIds)->count();

        // 2. Identify "Individual Contributors" (Everyone else)
        $individualContributorCount = Employee::whereNotIn('id', $managerIds)->count();

        // 2. Fetch Onboarding (New Hires in last 30 days)
        $onboarding = $this->getOnboardingEmployees(30);

        // 3. Fetch Expiring Contracts (Next 60 days)
        $expiring = Employee::select('first_name', 'last_name', 'contract_end_date')
            ->where('status', 'active')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($e) => ['fullName' => $e->first_name.' '.$e->last_name]);

        // 3. Fetch Expiring Contracts (Next 60 days)
        $expiring = $this->getExpiringContracts(60);

        // 4. Construct the Final Array Structure requested
        return [
            'summary' => [
                ['title' => 'Total Employees', 'value' => $totalEmployees],
                ['title' => 'Contractors', 'value' => $contractorCount],
                ['title' => 'Active Positions', 'value' => $positionsCount],
                ['title' => 'Departments', 'value' => $departmentsCount],
            ],
            'onboardingEmployees' => $onboarding,
            'expiringContracts' => $expiring,
            'employeeTypeChartData' => [
                'contractorCount' => $contractorCount,
                'employeeCount' => $regularEmployeesCount,
            ],
            'managerVsEmployeeChartData' => [
                'managerCount' => $managerCount,
                'individualContributorCount' => $individualContributorCount,
            ],
        ];
    }

    private function getOnboardingEmployees(int $days = 30): Collection
    {
        return Employee::select('first_name', 'last_name')
            ->where('hired_at', '>=', Carbon::now()->subDays($days))
            ->where('status', 'active')
            ->latest()
            ->limit(5)
            ->get()
            ->transform(fn ($e) => ['fullName' => $e->first_name.' '.$e->last_name]);
    }

    private function getExpiringContracts(int $days = 60): Collection
    {
        return Employee::select('first_name', 'last_name', 'contract_end_date')
            ->where('status', 'active')
            ->whereBetween('contract_end_date', [Carbon::now(), Carbon::now()->addDays($days)])
            ->orderBy('contract_end_date', 'asc')
            ->limit(10)
            ->get()
            ->transform(function ($e) {
                return [
                    'fullName' => $e->first_name.' '.$e->last_name,
                    'contractExpireDate' => $e->contract_end_date->format('Y-m-d'),
                ];
            });
    }

    public function getEmployeesPerDepartment(): array
    {
        // withCount('employees') adds an 'employees_count' attribute to each department
        // We only fetch active departments that actually have employees to keep the chart clean
        $data = Department::select('id', 'name')
            ->withCount('employees')
            ->orderBy('employees_count', 'desc')
            ->get();

        return $data->map(fn ($dept) => [
            'name' => $dept->name,
            'count' => $dept->employees_count,
        ])->toArray();
    }

    public function getYearlyTrend(): array
    {
        $sql = <<<'SQL'
        WITH RECURSIVE month_series(month_date) AS (
            -- Start with the month 11 months ago
            SELECT date('now', 'start of month', '-11 months')
            UNION ALL
            -- Add one month at a time
            SELECT date(month_date, '+1 month')
            FROM month_series
            WHERE month_date < date('now', 'start of month')
        )
        SELECT 
            strftime('%Y-%m', month_date) AS month_label,
            (
                SELECT COUNT(*) 
                FROM employees 
                WHERE hired_at <= date(month_date, 'start of month', '+1 month', '-1 day')
                AND (left_at IS NULL OR left_at > date(month_date, 'start of month', '+1 month', '-1 day'))
            ) AS total_headcount
        FROM month_series
        SQL;

        return DB::select($sql);
    }
}
