<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // 1. Basic Identity
            'id' => $this->id,
            'name' => $this->name,

            // 2. Renaming Keys (Decoupling)
            // We expose 'code' to the API, even though the DB column is 'cost_center_code'.
            // This allows us to refactor the DB later without breaking the frontend.
            'costCenterCode' => $this->cost_center_code,

            // 3. Computed/Aggregate Data (Performance Aware)
            // 'whenCounted' ensures we only return this if the Controller used withCount('employees').
            // If not, this key is completely omitted from the JSON.
            'stats' => [
                'headcount' => $this->whenCounted('employees'),
            ],

            // 4. Relationships
            // 'whenLoaded' prevents N+1 issues. It only includes the manager
            // if the Controller explicitly called with('manager').
            'manager' => $this->manager ? [
                'id' => $this->manager->id,
                'fullName' => $this->manager->first_name.' '.$this->manager->last_name,
            ] : null,
            // 5. Timestamps (Standardization)
            // Always return dates in ISO 8601 for the Frontend (Angular) to parse easily.
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
