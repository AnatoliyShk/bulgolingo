<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreScriptedLineRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * `ScriptedLinePolicy` is enforced by the `#[Authorize]` attribute on the
     * `ScriptedLineController` action this request is injected into.
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
            //
        ];
    }
}
