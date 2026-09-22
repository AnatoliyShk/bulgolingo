<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AskTutorRequest extends FormRequest
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
     * The question is sent to a paid model, so the upper bound caps what one
     * request can cost the same way the catalog search caps its embedding
     * input. The history the widget replays is bounded on both counts: a long
     * conversation costs more per turn than a short one, and nothing in the
     * bot's job needs more than the last few exchanges.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'min:2', 'max:500'],
            'history' => ['sometimes', 'array', 'max:6'],
            'history.*.role' => ['required', 'string', 'in:user,assistant'],
            'history.*.content' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'question.max' => 'Please keep your question under 500 characters.',
        ];
    }
}
