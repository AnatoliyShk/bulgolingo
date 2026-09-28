<?php

namespace App\Http\Requests\Admin;

use App\Enums\LanguageLevel;
use App\Enums\LearningPathType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LearningPathRequest extends FormRequest
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
     * The level is optional: a path can be saved before anyone has judged its
     * level, and the form's "Not set" sends null for that. Only the edit form
     * sends lesson_ids, since lessons are picked once the path exists.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'language' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(LearningPathType::class)],
            'level' => ['nullable', Rule::enum(LanguageLevel::class)],
            'lesson_ids' => ['nullable', 'array'],
            'lesson_ids.*' => ['integer', 'exists:lessons,id'],
        ];
    }

    /**
     * The path's own columns, without the lesson ids that are synced
     * separately. A missing level comes back as null, so an update clears it.
     *
     * @return array{name: string, language: string, type: string, level: ?string}
     */
    public function pathAttributes(): array
    {
        return [
            'name' => $this->validated('name'),
            'language' => $this->validated('language'),
            'type' => $this->validated('type'),
            'level' => $this->validated('level'),
        ];
    }
}
