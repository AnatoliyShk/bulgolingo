<?php

namespace App\Http\Requests\LearningPath;

use App\Services\SiteSettings;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexLearningPathRequest extends FormRequest
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
     * The search text is optional: without it the catalog renders whole. When
     * present it is embedded before anything is compared, so the lower bound
     * keeps a single keystroke from paying for an embedding call that could not
     * match anything meaningful, and the upper bound caps what is sent to the
     * model.
     *
     * With embedding search turned off there is no search field to answer
     * for, so q goes unvalidated and a stale ?q= in a bookmark loads the
     * catalog rather than bouncing back with an error about a hidden field.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if (! app(SiteSettings::class)->embeddingSearchEnabled()) {
            return [];
        }

        return [
            'q' => ['nullable', 'string', 'min:2', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'q.min' => 'Type at least 2 characters to search.',
            'q.max' => 'Keep the search under 255 characters.',
        ];
    }
}
