<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customerId = $this->route('customer')?->id;

        return [
            'contract_id' => [
                'nullable',
                Rule::exists('contracts', 'id')
                    ->where('customer_id', $customerId),
            ],
            'issue_date'  => ['required', 'date'],
            'due_date'    => ['required', 'date', 'after_or_equal:issue_date'],
            'amount'      => ['required', 'integer', 'min:1', 'max:999999999999'],
            'notes'       => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'contract_id' => '関連契約',
            'issue_date'  => '発行日',
            'due_date'    => '支払期限',
            'amount'      => '請求金額',
            'notes'       => '請求備考',
        ];
    }
}
