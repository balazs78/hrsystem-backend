<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class AuditLogService
{
    public function getAuditLogs(array $request): LengthAwarePaginator
    {
        // 1. Start the query with eager loading
        $query = AuditLog::with([
            'user' => function ($query) {
                $query->withTrashed();
            },
            'employee' => function ($query) {
                $query->withTrashed();
            },
        ]);
        // 2. Filter by User (Actor)
        $query->when(! empty($request['user_id']), function ($q) use ($request) {
            return $q->where('user_id', $request['user_id']);
        });
        // 3. Filter by Employee (Target)
        $query->when(! empty($request['employee_id']), function ($q) use ($request) {
            return $q->where('employee_id', $request['employee_id']);
        });
        // 4. Handle Date Filtering (Period vs Custom Dates)
        $this->applyDateFilters($query, $request);
        // 5. Handle sorting
        $query->when(! empty($request['sort']), function ($q) use ($request) {
            return $q->orderBy('created_at', $request['sort'] ?? 'desc');
        });
        // 6. Finalize with ordering and pagination
        $logs = $query->latest()->paginate($request['page_size'] ?? 10);

        return $logs;
    }

    /**
     * Helper to process date-related filtering logic.
     */
    private function applyDateFilters(Builder $query, array $request): void
    {
        // Option A: Predefined Period (today, yesterday, last_7_days, etc.)
        if (! empty($request['period'])) {
            $range = match ($request['period']) {
                'today' => [Carbon::today(), Carbon::tomorrow()],
                'yesterday' => [Carbon::yesterday(), Carbon::today()],
                'last_7_days' => [Carbon::now()->subDays(7), Carbon::now()],
                'last_30_days' => [Carbon::now()->subDays(30), Carbon::now()],
                default => null
            };

            if ($range) {
                $query->whereBetween('created_at', $range);

                return; // If period is set, skip custom start/end dates
            }
        }

        // Option B: Custom Start and End Date
        $query->when(! empty($request['start_date']), function ($q) use ($request) {
            return $q->whereDate('created_at', '>=', $request['start_date']);
        });

        $query->when(! empty($request['end_date']), function ($q) use ($request) {
            return $q->whereDate('created_at', '<=', $request['end_date']);
        });
    }
}
