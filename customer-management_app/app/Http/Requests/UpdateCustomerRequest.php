<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Closure;

/**
 * 顧客編集。入力ルールは登録時と共通。
 *
 * ただし担当者だけは扱いが異なる。
 * 担当者に指定できないアカウント(config/auth.php の non_assignable_login_ids)が
 * 既にその顧客の担当になっている場合、登録時と同じルールだと
 * 「担当者を変えない限り保存できない」状態になってしまう。
 * そのため、現在の担当者のままであれば通すようにしている。
 */
class UpdateCustomerRequest extends StoreCustomerRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $currentId = $this->route('customer')?->assigned_user_id;

        $rules['assigned_user_id'] = [
            'required',
            'integer',
            function (string $attribute, mixed $value, Closure $fail) use ($currentId): void {
                // 担当者を変更していないならそのまま通す
                if ($currentId !== null && (int) $value === (int) $currentId) {
                    return;
                }

                $selectable = User::query()
                    ->assignable()
                    ->whereKey($value)
                    ->exists();

                if (! $selectable) {
                    $fail('選択された担当者は指定できません。');
                }
            },
        ];

        return $rules;
    }
}
