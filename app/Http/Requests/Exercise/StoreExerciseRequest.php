<?php

namespace App\Http\Requests\Exercise;

use App\Enums\ExerciseType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExerciseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Both languages are optional: an exercise sent without one is created in
     * the default content language, and a word-pair board without a
     * translation language translates into the default one. A board cannot
     * translate a language into itself.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'lesson_id' => ['required', 'integer', 'exists:lessons,id'],
            'clause' => ['required', 'array'],
            'decision_type' => ['required', 'string', Rule::enum(ExerciseType::class)],
            'language_id' => ['nullable', 'integer', 'exists:languages,id'],
            'translation_language_id' => ['nullable', 'integer', 'exists:languages,id', 'different:language_id'],
            'image' => [
                Rule::requiredIf(ExerciseType::tryFrom((string) $this->input('decision_type'))?->definition()->requiresImage() ?? false),
                'nullable',
                'image',
                'max:5120',
            ],
        ];
    }
}
