<?php

namespace App\Http\Requests\Exercise;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Both languages are optional: one left out of the request keeps the
     * exercise's own, and one sent empty falls back to the default. A board
     * cannot translate a language into itself.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'decision_type' => ['required', 'string'],
            'clause' => ['required', 'array'],
            'language_id' => ['nullable', 'integer', 'exists:languages,id'],
            'translation_language_id' => ['nullable', 'integer', 'exists:languages,id', 'different:language_id'],
            'image' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
