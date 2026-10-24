<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Department;
use Illuminate\Contracts\Validation\ValidationRule;

class StoreDepartmentRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Department::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'managerId' => 'nullable|exists:employees,id',
            'costCenterCode' => ['required', 'string', 'unique:departments,cost_center_code', 'regex:/^[A-Z]{2}-\d{4}$/'],
        ];
    }
}
