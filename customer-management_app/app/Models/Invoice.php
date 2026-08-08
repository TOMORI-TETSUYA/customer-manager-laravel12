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

    /**
     * 入金済み合計額
     *
     * 一覧のように複数件をまとめて表示する場面では with('payments') で
     * 読み込み済みのことが多い。その場合は取得済みの値から合計して、
     * 1件ごとにSQLを投げない(N+1を避ける)。
     */
    public function paidTotal(): int
    {
        if ($this->relationLoaded('payments')) {
            return (int) $this->payments->sum('amount');
        }

        return (int) $this->payments()->sum('amount');
    }

    /** 残額 */
    public function remaining(): int
    {
        return max(0, (int) $this->amount - $this->paidTotal());
    }
}
