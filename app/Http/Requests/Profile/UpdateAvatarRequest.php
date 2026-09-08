<?php

namespace App\Http\Requests\Profile;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAvatarRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * Capped well below the 5MB an exercise picture is allowed: this one is
     * rendered at 7rem square and every viewer of the profile pays to download
     * it, where an exercise image is at least the point of the screen it is on.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'avatar.max' => 'The avatar may not be larger than 2MB.',
            'avatar.mimes' => 'The avatar must be a JPG, PNG or WebP image.',
        ];
    }
}
