<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerContact extends Model
{
    protected $fillable = [
        'contacted_at',
        'contact_method',
        'subject',
        'status',
        'next_action_at',
    ];

    protected function casts(): array
    {
        return [
            'response_encrypted' => 'encrypted',
            'contacted_at'       => 'datetime',
            'next_action_at'     => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public const METHODS = [
        'phone' => '電話',
        'email' => 'メール',
        'visit' => '訪問',
        'line'  => 'LINE',
        'other' => 'その他',
    ];
}
