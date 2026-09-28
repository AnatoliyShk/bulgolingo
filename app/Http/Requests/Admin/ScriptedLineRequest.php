<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ScriptedLineRequest extends FormRequest
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
            'scripted_dialogue_id' => ['required', 'integer', 'exists:scripted_dialogues,id'],
            'line_text' => ['required', 'string', 'max:1000'],
            'options' => ['required', 'array', 'size:3'],
            'options.*' => ['required', 'string', 'max:500'],
            'correct_option' => ['required', 'integer', 'min:0', 'max:2'],
        ];
    }

    /**
     * The form sends the line's fields flat, but the model keeps everything
     * except its dialogue in the `clause` JSON column, so this folds the
     * validated input into the shape the model stores.
     *
     * @return array{scripted_dialogue_id: int, clause: array{line_text: string, options: list<string>, correct_option: int}}
     */
    public function lineAttributes(): array
    {
        return [
            'scripted_dialogue_id' => $this->validated('scripted_dialogue_id'),
            'clause' => [
                'line_text' => $this->validated('line_text'),
                'options' => $this->validated('options'),
                'correct_option' => (int) $this->validated('correct_option'),
            ],
        ];
    }
}
