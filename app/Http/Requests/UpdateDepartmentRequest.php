<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Department;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $department = $this->route('department');

        return $department instanceof Department
            && ($this->user()?->can('update', $department) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(Request $request): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'managerId' => 'sometimes|exists:employees,id',
            'costCenterCode' => [
                'sometimes',
                'string',
                Rule::unique('departments', 'cost_center_code')->ignore($request->costCenterCode, 'cost_center_code'),
                "regex:/^[A-Z]{2}-\d{4}$/",
            ],
        ];
    }
}
