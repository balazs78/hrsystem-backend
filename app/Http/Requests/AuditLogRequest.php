<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\AuditLog;
use Illuminate\Contracts\Validation\ValidationRule;

class AuditLogRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', AuditLog::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'page' => 'integer|min:0',
            'pageSize' => 'integer|min:1|max:1000',
            'startDate' => 'date|nullable',
            'endDate' => 'date|nullable',
            'period' => 'in:today,yesterday,last_7_days,last_30_days|nullable',
            'userId' => 'integer|min:1|exists:users,id',
            'employeeId' => 'integer|min:1|exists:employees,id',
            'sort' => 'in:asc,desc|nullable',
        ];
    }
}
