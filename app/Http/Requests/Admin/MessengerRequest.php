<?php

namespace App\Http\Requests\Admin;

use App\Enums\MessengerName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MessengerRequest extends FormRequest
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
     * The unique rule mirrors the (messenger_name, messenger_user_id) unique
     * index, so linking an account that is already linked shows a form error
     * instead of failing the insert. On update it ignores the messenger being
     * edited; on store there is no bound messenger, so it ignores nothing.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'messenger_name' => ['required', Rule::enum(MessengerName::class)],
            'messenger_user_id' => [
                'required',
                'string',
                'max:255',
                Rule::unique('messengers')
                    ->where('messenger_name', $this->string('messenger_name')->value())
                    ->ignore($this->route('messenger')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'messenger_user_id.unique' => 'This messenger account is already linked to a user.',
        ];
    }
}
