<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CustomerStatus;
use App\Enums\CustomerType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 顧客登録 (§17)
 *   個人顧客: 顧客名を必須 / 法人顧客: 会社名を必須
 */
class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 認可はコントローラーのPolicyで行う
    }

    public function rules(): array
    {
        return [
            'customer_type'            => ['required', Rule::enum(CustomerType::class)],
            'customer_name'            => [
                'nullable',
                'required_if:customer_type,' . CustomerType::Individual->value,
                'string',
                'max:100',
            ],
            'customer_name_kana'       => ['nullable', 'string', 'max:100'],
            'company_name'             => [
                'nullable',
                'required_if:customer_type,' . CustomerType::Corporate->value,
                'string',
                'max:150',
            ],
            'company_name_kana'        => ['nullable', 'string', 'max:150'],
            'corporate_contact_name'   => ['nullable', 'string', 'max:100'],
            'phone'                    => ['required', 'string', 'max:20', 'regex:/^[0-9\-\+\(\) ]+$/'],
            'email'                    => ['nullable', 'email:rfc', 'max:255'],
            'postal_code'              => ['nullable', 'string', 'max:8', 'regex:/^[0-9\-]+$/'],
            'prefecture'               => ['nullable', 'string', 'max:10'],
            'city'                     => ['nullable', 'string', 'max:50'],
            'address'                  => ['nullable', 'string', 'max:255'],
            'building'                 => ['nullable', 'string', 'max:255'],
            'preferred_contact_method' => ['nullable', Rule::in(['phone', 'email', 'line', 'mail'])],
            'status'                   => ['required', Rule::enum(CustomerStatus::class)],
            'assigned_user_id'         => [
                'required',
                Rule::exists('users', 'id')->where('is_active', true),
            ],
            'source'                   => ['nullable', 'string', 'max:50'],
            'next_action_at'           => ['nullable', 'date'],
            'notes'                    => ['nullable', 'string', 'max:2000'],
            'tags'                     => ['nullable', 'array'],
            'tags.*'                   => ['integer', Rule::exists('tags', 'id')],
            'force'                    => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_type'            => '顧客区分',
            'customer_name'            => '顧客名',
            'customer_name_kana'       => '顧客名フリガナ',
            'company_name'             => '会社名',
            'company_name_kana'        => '会社名フリガナ',
            'corporate_contact_name'   => '法人担当者名',
            'phone'                    => '電話番号',
            'email'                    => 'メールアドレス',
            'postal_code'              => '郵便番号',
            'prefecture'               => '都道府県',
            'city'                     => '市区町村',
            'address'                  => '住所',
            'building'                 => '建物名',
            'preferred_contact_method' => '希望連絡方法',
            'status'                   => '顧客ステータス',
            'assigned_user_id'         => '担当者',
            'source'                   => '流入経路',
            'next_action_at'           => '次回対応日',
            'notes'                    => '備考',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required_if' => '個人顧客の場合、顧客名は必須です。',
            'company_name.required_if'  => '法人顧客の場合、会社名は必須です。',
        ];
    }
}
