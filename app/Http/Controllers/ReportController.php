<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    // Dependency Injection
    public function __construct(private ReportService $reportService) {}

    public function summaryReport(Request $request): JsonResponse
    {
        $data = $this->reportService->getDashboardSummary();

        return response()->json($data);
    }

    public function getYearlyTrend(): JsonResponse
    {
        $data = $this->reportService->getYearlyTrend();

        return response()->json($data);
    }

    public function getDepartmentHeadcounts(): JsonResponse
    {
        $data = $this->reportService->getEmployeesPerDepartment();

        return response()->json($data);
    }
}
