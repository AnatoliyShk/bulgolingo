<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
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
     * The floor is a cosine similarity, so only 0 to 1 is meaningful: at 0
     * every exercise counts as related, at 1 only an identical text does.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'embedding_search_enabled' => ['required', 'boolean'],
            'embedding_min_similarity' => ['required', 'numeric', 'between:0,1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'embedding_min_similarity.between' => 'The minimum similarity must be between 0 and 1.',
        ];
    }
}
