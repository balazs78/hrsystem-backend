<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Position;
use Illuminate\Contracts\Validation\ValidationRule;

class StorePositionRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Position::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255|unique:positions,title',
            'salaryGrade' => 'required|string|max:10', // e.g. "L1", "Senior"
            'description' => 'nullable|string',
        ];
    }
}
