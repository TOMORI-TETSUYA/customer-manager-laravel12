<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

/**
 * 顧客に対する権限 (§16)
 *   管理者   : 全操作(他人の担当顧客の編集・削除・復元を含む)
 *   職員     : 閲覧・登録・自分が担当する顧客の編集
 *   メンバー : 閲覧・登録・自分が担当する顧客の編集(職員と同じ)
 *
 * 削除と復元は管理者のみ。
 */
class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Customer $customer): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->canWrite();
    }

    public function update(User $user, Customer $customer): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->canWrite()
            && $customer->assigned_user_id === $user->id;
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Customer $customer): bool
    {
        return $user->isAdmin();
    }
}
