<?php

namespace App\Http\Requests\Exercise;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SearchExerciseRequest extends FormRequest
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
     * The query is embedded before it is compared, so the lower bound keeps a
     * single keystroke from paying for an embedding call that could not match
     * anything meaningful, and the upper bound caps what is sent to the model.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'min:2', 'max:255'],
        ];
    }
}
