<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeListResource extends JsonResource
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

            // 1. Computed Attributes (Easier for Angular Grid to display)
            'fullName' => $this->first_name.' '.$this->last_name,
            'profileThumbnail' => $this->profile_image
                ? str_replace('/profiles/', '/profiles/thumbnails/', $this->profile_image)
                : null,

            // 2. Formatting Enums/Dates
            // Status: Capitalize for UI (e.g., "active" -> "Active")
            'status' => ucfirst($this->status),
            // Dates: Send standard ISO format for Angular DatePipe

            // 3. Conditional Relationships (Prevents N+1 Performance Issues)
            // Only includes these if you used ::with() in the Controller
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'position' => $this->position?->title,
            // Self-referencing relationship (The Manager)
            // We use a "Light" resource here to prevent infinite recursion
            'manager' => new EmployeeLightResource($this->whenLoaded('manager')),
            'user_id' => $this->user_id,

            // 4. Conditional Attributes (Security)
            // Only show salary if the logged-in user is an Admin or HR
            // Note: detailed logic should be in a Policy, simplified here for demo

        ];
    }
}
