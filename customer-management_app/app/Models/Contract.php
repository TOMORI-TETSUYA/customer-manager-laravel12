<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'service_name',
        'contract_date',
        'amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'end_reason_encrypted' => 'encrypted',
            'contract_date'        => 'date',
            'amount'               => 'decimal:0',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public const STATUSES = [
        'active'    => '契約中',
        'completed' => '完了',
        'cancelled' => '解約',
    ];
}
