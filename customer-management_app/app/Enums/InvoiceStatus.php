<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string
{
    case Unpaid  = 'unpaid';
    case Partial = 'partial';
    case Paid    = 'paid';
    case Void    = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid  => '未入金',
            self::Partial => '一部入金',
            self::Paid    => '入金済み',
            self::Void    => '取消',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Unpaid  => 'ph-badge--danger',
            self::Partial => 'ph-badge--warning',
            self::Paid    => 'ph-badge--success',
            self::Void    => 'ph-badge--muted',
        };
    }
}
