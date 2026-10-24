<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class BaseFormRequest extends FormRequest
{
    /**
     * Get the validated data converted to snake_case.
     */
    public function validatedSnake(): array
    {
        $validated = $this->validated();
        $snaked = [];

        foreach ($validated as $key => $value) {
            $snaked[Str::snake($key)] = $value;
        }

        return $snaked;
    }
}
