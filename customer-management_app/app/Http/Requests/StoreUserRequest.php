<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-users');
    }

    public function rules(): array
    {
        /** @var User $actor */
        $actor = $this->user();

        return [
            'login_id' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9_\-\.]+$/',
                Rule::unique('users', 'login_id'),
            ],
            'name'     => ['required', 'string', 'max:100'],
            'role'     => [
                'required',
                // 作成できるロールはロールごとに異なる (§16)。
                //   管理者 : 管理者・職員・メンバー
                //   職員   : メンバーのみ
                // 画面のselectを改ざんして上位ロールを作られないようにする。
                Rule::in(UserRole::toValues($actor->creatableUserRoles())),
            ],
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

    public function messages(): array
    {
        return [
            'role.in' => 'その権限のユーザーを作成する権限がありません。',
        ];
    }
}
