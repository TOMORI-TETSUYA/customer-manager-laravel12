<?php

declare(strict_types=1);

namespace App\Enums;

enum CustomerStatus: string
{
    case Prospect = 'prospect';
    case Active   = 'active';
    case Dormant  = 'dormant';
    case Closed   = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Prospect => '見込み',
            self::Active   => '取引中',
            self::Dormant  => '休眠',
            self::Closed   => '取引終了',
        };
    }

    /** ステータスバッジ用のCSSクラス */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Prospect => 'ph-badge--info',
            self::Active   => 'ph-badge--success',
            self::Dormant  => 'ph-badge--warning',
            self::Closed   => 'ph-badge--muted',
        };
    }
}
