<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paid_at'        => ['required', 'date'],
            'amount'         => ['required', 'integer', 'min:1', 'max:999999999999'],
            'payment_method' => ['required', Rule::in(array_keys(Payment::METHODS))],
            'notes'          => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'paid_at'        => '入金日',
            'amount'         => '入金額',
            'payment_method' => '入金方法',
            'notes'          => '入金備考',
        ];
    }
}
