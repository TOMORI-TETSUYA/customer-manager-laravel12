<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_name'  => ['required', 'string', 'max:150'],
            'contract_date' => ['required', 'date'],
            'amount'        => ['required', 'integer', 'min:0', 'max:999999999999'],
            'status'        => ['required', Rule::in(array_keys(Contract::STATUSES))],
        ];
    }

    public function attributes(): array
    {
        return [
            'service_name'  => 'サービス名',
            'contract_date' => '契約日',
            'amount'        => '契約金額',
            'status'        => '契約ステータス',
        ];
    }
}
