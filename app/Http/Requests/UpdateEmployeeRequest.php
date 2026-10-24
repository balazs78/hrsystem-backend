<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $employee = $this->route('employee');

        return $employee instanceof Employee
            && ($this->user()?->can('update', $employee) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(Request $request): array
    {
        return [
            'firstName' => 'required|string|max:50',
            'lastName' => 'required|string|max:50',
            'gender' => 'required|in:male,female',
            'email' => [
                'nullable',
                'email',
                Rule::unique('employees', 'email')->ignore($request->email, 'email'),
            ],
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
