<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\CustomerContact;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contacted_at'   => ['required', 'date'],
            'contact_method' => ['required', Rule::in(array_keys(CustomerContact::METHODS))],
            'subject'        => ['required', 'string', 'max:200'],
            'response'       => ['nullable', 'string', 'max:5000'],
            'status'         => ['required', Rule::in(['done', 'pending', 'follow_up'])],
            'next_action_at' => ['nullable', 'date', 'after_or_equal:contacted_at'],
        ];
    }

    public function attributes(): array
    {
        return [
            'contacted_at'   => '対応日時',
            'contact_method' => '対応方法',
            'subject'        => '件名',
            'response'       => '対応内容',
            'status'         => '対応ステータス',
            'next_action_at' => '次回対応日',
        ];
    }
}
