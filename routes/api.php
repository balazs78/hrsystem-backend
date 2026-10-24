<?php

declare(strict_types=1);

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::apiResource('/departments', DepartmentController::class);
    Route::apiResource('/positions', PositionController::class);
    Route::get('/employees/search', [EmployeeController::class, 'getEmployeeNames']);
    Route::get('/employees/managers', [EmployeeController::class, 'getAllManagers']);
    Route::apiResource('/employees', EmployeeController::class);
    Route::apiResource('/users', RegisteredUserController::class);
    Route::get('/reports/summary', [ReportController::class, 'summaryReport']);
    Route::get('/reports/yearly-trend', [ReportController::class, 'getYearlyTrend']);
    Route::get('/reports/department-headcounts', [ReportController::class, 'getDepartmentHeadcounts']);
    Route::put('/employees/{id}/restore', [EmployeeController::class, 'restore']);
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
});
