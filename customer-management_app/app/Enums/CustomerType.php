<?php

declare(strict_types=1);

namespace App\Enums;

enum CustomerType: string
{
    case Individual = 'individual';
    case Corporate  = 'corporate';

    public function label(): string
    {
        return match ($this) {
            self::Individual => '個人',
            self::Corporate  => '法人',
        };
    }
}
