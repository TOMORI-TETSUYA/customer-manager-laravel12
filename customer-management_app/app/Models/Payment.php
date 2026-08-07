<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'paid_at',
        'amount',
        'payment_method',
    ];

    protected function casts(): array
    {
        return [
            'notes_encrypted' => 'encrypted',
            'paid_at'         => 'date',
            'amount'          => 'decimal:0',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public const METHODS = [
        'bank'  => '銀行振込',
        'cash'  => '現金',
        'card'  => 'カード',
        'other' => 'その他',
    ];
}
