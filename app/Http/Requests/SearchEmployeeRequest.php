<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Contracts\Validation\ValidationRule;

class SearchEmployeeRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Employee::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => 'string|max:50|nullable',
            'direction' => 'in:asc,desc',
            'sort' => 'in:first_name,status|nullable',
            'page' => 'integer|min:0',
            'pageSize' => 'integer|min:1|max:1000',
            'position' => 'array|nullable',
            'position.*' => 'integer|min:1|exists:positions,id',
            'department' => 'array|nullable',
            'department.*' => 'integer|min:1|exists:departments,id',
            'manager' => 'array|nullable',
            'manager.*' => 'integer|min:1|exists:employees,id',
            'status' => 'array|nullable',
            'status.*' => 'in:active,on_leave,terminated',
        ];
    }
}
