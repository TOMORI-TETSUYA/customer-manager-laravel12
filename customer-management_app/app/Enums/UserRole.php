<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 権限 (§16)
 *
 *   管理者   : 全画面・全操作。ユーザー管理では全ロールを扱える。
 *   職員     : 全画面・全操作。ユーザー管理では職員とメンバーのみ扱える。
 *   メンバー : 顧客管理・契約管理・ユーザー管理のみ。
 *              顧客と契約は登録・編集できるが、ユーザー管理は閲覧のみ。
 */
enum UserRole: string
{
    case Admin  = 'admin';
    case Staff  = 'staff';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Admin  => '管理者',
            self::Staff  => '職員',
            self::Member => 'メンバー',
        };
    }

    /**
     * ユーザー管理の一覧に表示してよいロール。
     *
     * @return list<self>
     */
    public function visibleRoles(): array
    {
        return match ($this) {
            self::Admin  => [self::Admin, self::Staff, self::Member],
            self::Staff  => [self::Staff, self::Member],
            self::Member => [self::Member],
        };
    }

    /**
     * 新しいユーザーとして作成してよいロール。
     * メンバーはユーザーを作成できないため空になる。
     *
     * @return list<self>
     */
    public function creatableRoles(): array
    {
        return match ($this) {
            self::Admin  => [self::Admin, self::Staff, self::Member],
            self::Staff  => [self::Member],
            self::Member => [],
        };
    }

    /**
     * ロールの配列を、DBに入っている文字列の配列へ変換する。
     *
     * @param  list<self>  $roles
     * @return list<string>
     */
    public static function toValues(array $roles): array
    {
        return array_map(static fn (self $role): string => $role->value, $roles);
    }
}
