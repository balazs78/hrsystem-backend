<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title, // e.g., "Senior Backend Engineer"
            'salaryGrade' => $this->salary_grade, // e.g., "L4"

            // Conditional: Only show employee count if requested by the controller
            // This prevents N+1 queries on the index page
            'headcount' => $this->whenCounted('employees'),
            'description' => $this->description,

            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
