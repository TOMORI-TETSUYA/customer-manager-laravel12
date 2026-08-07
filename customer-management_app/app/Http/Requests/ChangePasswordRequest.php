<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password'         => [
                'required',
                'string',
                'confirmed',
                'max:128',
                Password::min(12)->letters()->numbers(),
                'different:current_password',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'current_password' => '現在のパスワード',
            'password'         => '新しいパスワード',
        ];
    }
}
