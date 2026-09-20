<?php

namespace App\Http\Requests\LearningPath;

use App\Enums\LanguageLevel;
use App\Services\SiteSettings;
use App\Support\LearningPathFilters;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetLearningPathRequest extends FormRequest
{
    public function __construct(private readonly SiteSettings $settings)
    {
        parent::__construct();
    }

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
     * is_finished is optional: without it the catalog renders, with it the
     * user's own in-progress (0) or finished (1) paths do.
     *
     * level and sort are optional too, but when present must be a known level
     * and one of the catalog's sorts.
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
        $rules = [
            'is_finished' => ['sometimes', 'boolean'],
            'level' => ['sometimes', Rule::enum(LanguageLevel::class)],
            'sort' => ['sometimes', Rule::in(LearningPathFilters::SORTS)],
        ];

        if ($this->settings->embeddingSearchEnabled()) {
            $rules['q'] = ['nullable', 'string', 'min:2', 'max:255'];
        }

        return $rules;
    }

    /**
     * The validated is_finished flag, or null when the request is for the
     * catalog rather than one of the user's own lists.
     */
    public function isFinished(): ?bool
    {
        return $this->has('is_finished') ? $this->boolean('is_finished') : null;
    }

    /**
     * The catalog's level and sort, from the validated query. Missing from the
     * query, the level falls back to the one remembered in the cookie from an
     * earlier visit, read leniently since the cookie is not validated; with
     * neither there is no level yet, and the page asks the visitor to choose.
     */
    public function filters(): LearningPathFilters
    {
        $level = $this->validated('level');
        $cookie = $this->cookie(LearningPathFilters::LEVEL_COOKIE);

        return new LearningPathFilters(
            $level !== null
                ? LanguageLevel::from($level)
                : (is_string($cookie) ? LanguageLevel::tryFrom($cookie) : null),
            $this->validated('sort', LearningPathFilters::DEFAULT_SORT),
        );
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'is_finished.boolean' => 'is_finished must be 0 or 1.',
            'q.min' => 'Type at least 2 characters to search.',
            'q.max' => 'Keep the search under 255 characters.',
        ];
    }
}
