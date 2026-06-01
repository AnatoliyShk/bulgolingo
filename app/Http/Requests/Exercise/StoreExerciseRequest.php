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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'lesson_id'     => ['required', 'integer', 'exists:lessons,id'],
            'clause'        => ['required', 'array'],
            'decision_type' => ['required', 'string', Rule::enum(ExerciseType::class)],
        ];
    }
}
