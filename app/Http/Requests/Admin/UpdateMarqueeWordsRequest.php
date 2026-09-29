<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMarqueeWordsRequest extends FormRequest
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
     * At least one word is required, because an empty running line would leave
     * a blank strip on the welcome page. The cap keeps the page payload and the
     * scroll time (3.5 seconds a word) within reason.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'words' => ['required', 'array', 'min:1', 'max:100'],
            'words.*.bg' => ['required', 'string', 'max:60'],
            'words.*.en' => ['required', 'string', 'max:60'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'words.required' => 'Add at least one word.',
            'words.min' => 'Add at least one word.',
            'words.max' => 'The running line can hold at most 100 words.',
            'words.*.bg.required' => 'Every word needs its Bulgarian text.',
            'words.*.en.required' => 'Every word needs its English meaning.',
        ];
    }
}
