{{--
    顧客フォーム共通パーシャル (CUS-02 / CUS-04)
    受け取る変数:
      $customer : App\Models\Customer (新規時は空インスタンス)
      $users    : 担当者候補
      $tags     : タグ一覧
--}}

@php
    $type = old('customer_type', $customer->customer_type?->value ?? 'individual');
    $isCorporate = $type === 'corporate';
@endphp

<div class="ph-form-grid">

    {{-- 顧客区分 --}}
    <div class="ph-field is-span-2">
        <span class="ph-field__label">顧客区分<span class="is-required">必須</span></span>
        <div class="ph-row" role="radiogroup" aria-label="顧客区分">
            @foreach (App\Enums\CustomerType::cases() as $caseType)
                <label class="login-remember">
                    <input
                        type="radio"
                        name="customer_type"
                        value="{{ $caseType->value }}"
                        @checked($type === $caseType->value)
                    >
                    {{ $caseType->label() }}
                </label>
            @endforeach
        </div>
        @error('customer_type')
            <p class="ph-field__error">{{ $message }}</p>
        @enderror
    </div>

    {{-- 顧客名(個人は必須) --}}
    <div class="ph-field">
        <label class="ph-field__label" for="customer_name">
            顧客名<span class="is-required" @if($isCorporate) hidden @endif>必須</span>
        </label>
        <input
            type="text"
            class="form-control @error('customer_name') is-invalid @enderror"
            id="customer_name"
            name="customer_name"
            value="{{ old('customer_name', $customer->customer_name) }}"
            maxlength="100"
        >
        @error('customer_name')
            <p class="ph-field__error">{{ $message }}</p>
        @enderror
    </div>

    <div class="ph-field">
        <label class="ph-field__label" for="customer_name_kana">顧客名フリガナ</label>
        <input
            type="text"
            class="form-control @error('customer_name_kana') is-invalid @enderror"
            id="customer_name_kana"
            name="customer_name_kana"
            value="{{ old('customer_name_kana', $customer->customer_name_kana) }}"
            maxlength="100"
        >
        @error('customer_name_kana')
            <p class="ph-field__error">{{ $message }}</p>
        @enderror
    </div>

    {{-- 会社名(法人は必須) --}}
    <div class="ph-field">
        <label class="ph-field__label" for="company_name">
            会社名<span class="is-required" @unless($isCorporate) hidden @endunless>必須</span>
        </label>
        <input
            type="text"
            class="form-control @error('company_name') is-invalid @enderror"
            id="company_name"
            name="company_name"
            value="{{ old('company_name', $customer->company_name) }}"
            maxlength="150"
        >
        @error('company_name')
            <p class="ph-field__error">{{ $message }}</p>
        @enderror
    </div>

    <div class="ph-field">
        <label class="ph-field__label" for="company_name_kana">会社名フリガナ</label>
        <input
            type="text"
            class="form-control @error('company_name_kana') is-invalid @enderror"
            id="company_name_kana"
            name="company_name_kana"
            value="{{ old('company_name_kana', $customer->company_name_kana) }}"
            maxlength="150"
        >
        @error('company_name_kana')
            <p class="ph-field__error">{{ $message }}</p>
        @enderror
    </div>

    {{-- 法人担当者名(法人のみ表示 §17.2) --}}
    <div class="ph-field" data-corporate-only @unless($isCorporate) hidden @endunless>
        <label class="ph-field__label" for="corporate_contact_name">法人担当者名</label>
        <input
            type="text"
            class="form-control @error('corporate_contact_name') is-invalid @enderror"
            id="corporate_contact_name"
            name="corporate_contact_name"
            value="{{ old('corporate_contact_name', $customer->corporate_contact_name) }}"
            maxlength="100"
        >
        @error('corporate_contact_name')
            <p class="ph-field__error">{{ $message }}</p>
        @enderror
    </div>

    {{-- 電話番号(暗号化保存 §18.2) --}}
    <div class="ph-field">
        <label class="ph-field__label" for="phone">
            電話番号<span class="is-required">必須</span>
        </label>
        <input
            type="tel"
            class="form-control @error('phone') is-invalid @enderror"
            id="phone"
            name="phone"
            value="{{ old('phone', $customer->phone_encrypted) }}"
            maxlength="20"
            inputmode="tel"
            required
        >
        <p class="ph-field__help" data-phone-preview aria-live="polite"></p>
        @error('phone')
            <p class="ph-field__error">{{ $message }}</p>
        @enderror
    </div>

    <div class="ph-field">
        <label class="ph-field__label" for="email">メールアドレス</label>
        <input
            type="email"
            class="form-control @error('email') is-invalid @enderror"
            id="email"
            name="email"
            value="{{ old('email', $customer->email_encrypted) }}"
            maxlength="255"
            inputmode="email"
        >
        @error('email')
            <p class="ph-field__error">{{ $message }}</p>
        @enderror
    </div>

    <div class="ph-field">
        <label class="ph-field__label" for="postal_code">郵便番号</label>
        <input
            type="text"
            class="form-control @error('postal_code') is-invalid @enderror"
            id="postal_code"
            name="postal_code"
            value="{{ old('postal_code', $customer->postal_code) }}"
            maxlength="8"
            inputmode="numeric"
        >
        @error('postal_code')
            <p class="ph-field__error">{{ $message }}</p>
        @enderror
    </div>

    <div class="ph-field">
        <label class="ph-field__label" for="prefecture">都道府県</label>
        <input
            type="text"
            class="form-control @error('prefecture') is-invalid @enderror"
            id="prefecture"
            name="prefecture"
            value="{{ old('prefecture', $customer->prefecture) }}"
            maxlength="10"
        >
        @error('prefecture')
            <p class="ph-field__error">{{ $message }}</p>
        @enderror
    </div>

    <div class="ph-field">
        <label class="ph-field__label" for="city">市区町村</label>
        <input
            type="text"
            class="form-control @error('city') is-invalid @enderror"
            id="city"
            name="city"
            value="{{ old('city', $customer->city) }}"
            maxlength="50"
        >
        @error('city')
            <p class="ph-field__error">{{ $message }}</p>
        @enderror
    </div>

    <div class="ph-field">
        <label class="ph-field__label" for="address">住所</label>
        <input
            type="text"
            class="form-control @error('address') is-invalid @enderror"
            id="address"
            name="address"
            value="{{ old('address', $customer->address_encrypted) }}"
            maxlength="255"
        >
        @error('address')
            <p class="ph-field__error">{{ $message }}</p>
        @enderror
    </div>

    <div class="ph-field">
        <label class="ph-field__label" for="building">建物名</label>
        <input
            type="text"
            class="form-control @error('building') is-invalid @enderror"
            id="building"
            name="building"
            value="{{ old('building', $customer->building_encrypted) }}"
            maxlength="255"
        >
        @error('building')
            <p class="ph-field__error">{{ $message }}</p>
        @enderror
    </div>

    <div class="ph-field">
        <label class="ph-field__label" for="preferred_contact_method">希望連絡方法</label>
        <select
            class="form-select @error('preferred_contact_method') is-invalid @enderror"
            id="preferred_contact_method"
            name="preferred_contact_method"
        >
            <option value="">未設定</option>
            @foreach (['phone' => '電話', 'email' => 'メール', 'line' => 'LINE', 'mail' => '郵送'] as $value => $label)
                <option value="{{ $value }}" @selected(old('preferred_contact_method', $customer->preferred_contact_method) === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('preferred_contact_method')
            <p class="ph-field__error">{{ $message }}</p>
        @enderror
    </div>

    <div class="ph-field">
        <label class="ph-field__label" for="status">
            顧客ステータス<span class="is-required">必須</span>
        </label>
        <select
            class="form-select @error('status') is-invalid @enderror"
            id="status"
            name="status"
            required
        >
            @foreach (App\Enums\CustomerStatus::cases() as $statusCase)
                <option value="{{ $statusCase->value }}" @selected(old('status', $customer->status?->value ?? 'prospect') === $statusCase->value)>
                    {{ $statusCase->label() }}
                </option>
            @endforeach
        </select>
        @error('status')
            <p class="ph-field__error">{{ $message }}</p>
        @enderror
    </div>

    <div class="ph-field">
        <label class="ph-field__label" for="assigned_user_id">
            担当者<span class="is-required">必須</span>
        </label>
        <select
            class="form-select @error('assigned_user_id') is-invalid @enderror"
            id="assigned_user_id"
            name="assigned_user_id"
            required
        >
            <option value="">選択してください</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected((string) old('assigned_user_id', $customer->assigned_user_id) === (string) $user->id)>
                    {{ $user->name }}
                </option>
            @endforeach
        </select>
        @error('assigned_user_id')
            <p class="ph-field__error">{{ $message }}</p>
        @enderror
    </div>

    <div class="ph-field">
        <label class="ph-field__label" for="source">流入経路</label>
        <input
            type="text"
            class="form-control @error('source') is-invalid @enderror"
            id="source"
            name="source"
            value="{{ old('source', $customer->source) }}"
            maxlength="50"
        >
        @error('source')
            <p class="ph-field__error">{{ $message }}</p>
        @enderror
    </div>

    <div class="ph-field">
        <label class="ph-field__label" for="next_action_at">次回対応日</label>
        <input
            type="date"
            class="form-control @error('next_action_at') is-invalid @enderror"
            id="next_action_at"
            name="next_action_at"
            value="{{ old('next_action_at', $customer->next_action_at?->format('Y-m-d')) }}"
        >
        @error('next_action_at')
            <p class="ph-field__error">{{ $message }}</p>
        @enderror
    </div>

    <div class="ph-field is-span-2">
        <span class="ph-field__label">タグ</span>
        <div class="ph-row">
            @forelse ($tags as $tag)
                <label class="login-remember">
                    <input
                        type="checkbox"
                        name="tags[]"
                        value="{{ $tag->id }}"
                        @checked(in_array($tag->id, old('tags', $customer->tags->pluck('id')->all())))
                    >
                    {{ $tag->name }}
                </label>
            @empty
                <span class="ph-muted ph-text-sm">タグは登録されていません。</span>
            @endforelse
        </div>
        @error('tags')
            <p class="ph-field__error">{{ $message }}</p>
        @enderror
    </div>

    <div class="ph-field is-span-2">
        <label class="ph-field__label" for="notes">備考</label>
        <textarea
            class="form-control @error('notes') is-invalid @enderror"
            id="notes"
            name="notes"
            rows="4"
            maxlength="2000"
        >{{ old('notes', $customer->notes_encrypted) }}</textarea>
        <p class="ph-field__help">備考は暗号化して保存されます。</p>
        @error('notes')
            <p class="ph-field__error">{{ $message }}</p>
        @enderror
    </div>

</div>
