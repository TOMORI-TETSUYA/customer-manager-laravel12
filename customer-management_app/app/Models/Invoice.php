<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'issue_date',
        'due_date',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'notes_encrypted' => 'encrypted',
            'issue_date'      => 'date',
            'due_date'        => 'date',
            'amount'          => 'decimal:0',
            'status'          => InvoiceStatus::class,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('paid_at');
    }

    /** 入金済み合計額 */
    public function paidTotal(): int
    {
        return (int) $this->payments()->sum('amount');
    }

    /** 残額 */
    public function remaining(): int
    {
        return max(0, (int) $this->amount - $this->paidTotal());
    }
}
