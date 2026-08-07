<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CustomerStatus;
use App\Enums\CustomerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_type',
        'customer_name',
        'customer_name_kana',
        'company_name',
        'company_name_kana',
        'corporate_contact_name',
        'postal_code',
        'prefecture',
        'city',
        'preferred_contact_method',
        'status',
        'assigned_user_id',
        'source',
        'next_action_at',
    ];

    protected function casts(): array
    {
        return [
            'customer_type'      => CustomerType::class,
            'status'             => CustomerStatus::class,
            // 個人情報カラムは Laravel の encrypted キャストで暗号化 (§23.1)
            'phone_encrypted'    => 'encrypted',
            'email_encrypted'    => 'encrypted',
            'address_encrypted'  => 'encrypted',
            'building_encrypted' => 'encrypted',
            'notes_encrypted'    => 'encrypted',
            'last_contacted_at'  => 'datetime',
            'next_action_at'     => 'datetime',
        ];
    }

    // ------------------------------------------------------------------
    // リレーション
    // ------------------------------------------------------------------
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class)->latest('contacted_at');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class)->latest('contract_date');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->latest('issue_date');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'customer_tag');
    }

    // ------------------------------------------------------------------
    // 表示用アクセサ
    // ------------------------------------------------------------------

    /** 一覧に表示する名称(法人は会社名を優先) */
    public function getDisplayNameAttribute(): string
    {
        if ($this->customer_type === CustomerType::Corporate) {
            return (string) ($this->company_name ?: $this->customer_name);
        }

        return (string) ($this->customer_name ?: $this->company_name);
    }

    /** マスク済み電話番号 (§32.2) 例: ***-****-5678 */
    public function getMaskedPhoneAttribute(): string
    {
        return '***-****-' . $this->phone_last4;
    }

    /** マスク済みメールアドレス 例: t***@example.com */
    public function getMaskedEmailAttribute(): ?string
    {
        $email = $this->email_encrypted;

        if ($email === null || $email === '') {
            return null;
        }

        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($local, 0, 1) . '***@' . $domain;
    }
}
