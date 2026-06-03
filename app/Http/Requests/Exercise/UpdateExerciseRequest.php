<?php

namespace App\Http\Requests\Exercise;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'decision_type' => ['required', 'string'],
            'clause'        => ['required', 'array'],
        ];
    }
}
