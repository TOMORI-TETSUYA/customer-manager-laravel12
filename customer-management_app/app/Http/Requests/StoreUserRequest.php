<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login_id' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9_\-\.]+$/',
                Rule::unique('users', 'login_id'),
            ],
            'name'     => ['required', 'string', 'max:100'],
            'role'     => ['required', Rule::enum(UserRole::class)],
            'password' => [
                'required',
                'string',
                'max:128',
                Password::min(12)->letters()->numbers(),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'login_id' => 'ログインID',
            'name'     => '表示名',
            'role'     => '権限',
            'password' => '初期パスワード',
        ];
    }
}
