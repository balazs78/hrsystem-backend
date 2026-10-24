<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Position;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdatePositionRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $position = $this->route('position');

        return $position instanceof Position
            && ($this->user()?->can('update', $position) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(Request $request): array
    {
        return [
            'title' => [
                'string',
                'max:255',
                Rule::unique('positions', 'title')->ignore($request->title, 'title'),
            ],
            'salaryGrade' => 'string|max:10', // e.g. "L1", "Senior"
            'description' => 'nullable|string',
        ];
    }
}
