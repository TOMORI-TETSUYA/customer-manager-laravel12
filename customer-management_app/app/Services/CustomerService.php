<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * 顧客の登録・更新の業務処理 (§19)
 */
class CustomerService
{
    public function __construct(
        private readonly SearchHashService $searchHash,
        private readonly AuditLogService $auditLog,
    ) {
    }

    /**
     * 電話番号・メールアドレスから重複候補を検索する (§19)。
     *
     * @return Collection<int, Customer>
     */
    public function findDuplicates(string $phone, ?string $email, ?int $excludeId = null): Collection
    {
        $phoneHash = $this->searchHash->hash(
            $this->searchHash->normalizePhone($phone)
        );

        $query = Customer::query()
            ->where(function ($q) use ($phoneHash, $email): void {
                $q->where('phone_hash', $phoneHash);

                if ($email !== null && $email !== '') {
                    $emailHash = $this->searchHash->hash(
                        $this->searchHash->normalizeEmail($email)
                    );
                    $q->orWhere('email_hash', $emailHash);
                }
            });

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->limit(5)->get();
    }

    /**
     * 顧客を登録する。トランザクション内で操作履歴も保存する。
     *
     * @param array<string, mixed> $validated
     */
    public function create(array $validated): Customer
    {
        return DB::transaction(function () use ($validated): Customer {
            $customer = new Customer();
            $this->fill($customer, $validated);

            $customer->customer_code = $this->issueCustomerCode();
            $customer->created_by    = Auth::id();
            $customer->updated_by    = Auth::id();
            $customer->save();

            $this->auditLog->record(
                'customer_create',
                'customer',
                $customer->id,
            );

            return $customer;
        });
    }

    /**
     * 顧客を更新する。変更カラム名のみを操作履歴へ保存する (§23.3)。
     *
     * @param array<string, mixed> $validated
     */
    public function update(Customer $customer, array $validated): Customer
    {
        return DB::transaction(function () use ($customer, $validated): Customer {
            $this->fill($customer, $validated);
            $customer->updated_by = Auth::id();

            $changed = array_values(array_diff(
                array_keys($customer->getDirty()),
                ['updated_by', 'updated_at'],
            ));

            $customer->save();

            $this->auditLog->record(
                'customer_update',
                'customer',
                $customer->id,
                $changed,
            );

            return $customer;
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    private function fill(Customer $customer, array $data): void
    {
        $normalizedPhone = $this->searchHash->normalizePhone((string) $data['phone']);

        $customer->fill([
            'customer_type'            => $data['customer_type'],
            'customer_name'            => $data['customer_name'] ?? null,
            'customer_name_kana'       => $data['customer_name_kana'] ?? null,
            'company_name'             => $data['company_name'] ?? null,
            'company_name_kana'        => $data['company_name_kana'] ?? null,
            'corporate_contact_name'   => $data['corporate_contact_name'] ?? null,
            'postal_code'              => $data['postal_code'] ?? null,
            'prefecture'               => $data['prefecture'] ?? null,
            'city'                     => $data['city'] ?? null,
            'preferred_contact_method' => $data['preferred_contact_method'] ?? null,
            'status'                   => $data['status'],
            'assigned_user_id'         => $data['assigned_user_id'],
            'source'                   => $data['source'] ?? null,
            'next_action_at'           => $data['next_action_at'] ?? null,
        ]);

        // 暗号化カラム(モデルの encrypted キャストで自動暗号化)
        $customer->phone_encrypted    = $normalizedPhone;
        $customer->address_encrypted  = $data['address'] ?? null;
        $customer->building_encrypted = $data['building'] ?? null;
        $customer->notes_encrypted    = $data['notes'] ?? null;

        // 検索用ハッシュ・マスク表示用データ
        $customer->phone_hash  = $this->searchHash->hash($normalizedPhone);
        $customer->phone_last4 = $this->searchHash->phoneLast4($normalizedPhone);

        $email = $data['email'] ?? null;
        if ($email !== null && $email !== '') {
            $normalizedEmail           = $this->searchHash->normalizeEmail((string) $email);
            $customer->email_encrypted = $normalizedEmail;
            $customer->email_hash      = $this->searchHash->hash($normalizedEmail);
        } else {
            $customer->email_encrypted = null;
            $customer->email_hash      = null;
        }
    }

    /** 顧客IDを自動発行する (例: PH-000001)。 */
    private function issueCustomerCode(): string
    {
        $nextId = (int) (Customer::withTrashed()->max('id') ?? 0) + 1;

        do {
            $code   = sprintf('PH-%06d', $nextId);
            $exists = Customer::withTrashed()
                ->where('customer_code', $code)
                ->exists();
            $nextId++;
        } while ($exists);

        return $code;
    }
}
