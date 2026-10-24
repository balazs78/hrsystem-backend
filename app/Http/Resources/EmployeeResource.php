<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class EmployeeResource extends JsonResource
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
            'firstName' => $this->first_name, // Keep raw for Edit Forms
            'lastName' => $this->last_name,   // Keep raw for Edit Forms
            'gender' => $this->gender,
            'birthdate' => $this->birthdate->toIso8601String(),
            'phone' => $this->phone,
            'address' => $this->address,
            'email' => $this->email,
            'profileImage' => $this->profile_image ? $this->profile_image : null,

            // 2. Formatting Enums/Dates
            'status' => $this->status,
            // Dates: Send standard ISO format for Angular DatePipe
            'hiredAt' => $this->hired_at?->toIso8601String(),
            'contractEndDate' => $this->contract_end_date?->toIso8601String(),
            // 3. Conditional Relationships (Prevents N+1 Performance Issues)
            // Only includes these if you used ::with() in the Controller
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'position' => new PositionResource($this->whenLoaded('position')),
            // Self-referencing relationship (The Manager)
            // We use a "Light" resource here to prevent infinite recursion
            'manager' => new EmployeeLightResource($this->whenLoaded('manager')),

            // 4. Conditional Attributes (Security)
            // Only show salary if the logged-in user is an Admin or HR
            // Note: detailed logic should be in a Policy, simplified here for demo

            // Meta data
            'updatedAt' => $this->updated_at->toIso8601String(),
        ];
    }
}
