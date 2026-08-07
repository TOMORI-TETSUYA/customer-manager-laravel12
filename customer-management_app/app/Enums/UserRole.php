<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Admin  = 'admin';
    case Staff  = 'staff';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Admin  => '管理者',
            self::Staff  => '一般担当者',
            self::Viewer => '閲覧専用',
        };
    }
}
