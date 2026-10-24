<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AuditLogRequest;
use App\Http\Resources\AuditLogResource;
use App\Services\AuditLogService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuditLogController extends Controller
{
    public function index(AuditLogRequest $request, AuditLogService $auditLogService): AnonymousResourceCollection
    {
        $logs = $auditLogService->getAuditLogs($request->validatedSnake());

        return AuditLogResource::collection($logs);
    }
}
