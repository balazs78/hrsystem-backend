<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Contracts\Validation\ValidationRule;

class StoreEmployeeRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Employee::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'firstName' => 'required|string|max:50',
            'lastName' => 'required|string|max:50',
            'gender' => 'required|in:male,female',
            'email' => 'required|email|unique:employees,email',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:100',
            'birthdate' => 'required|date',
            'hiredAt' => 'required|date',
            'departmentId' => 'required|exists:departments,id',
            'positionId' => 'required|exists:positions,id',
            'managerId' => 'nullable|exists:employees,id',
            'status' => 'required|in:active,on_leave,terminated',
            'profileImage' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'contractEndDate' => 'nullable|date',
        ];
    }
}
