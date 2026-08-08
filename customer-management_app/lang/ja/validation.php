<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| 入力チェックのエラーメッセージ(日本語)
|--------------------------------------------------------------------------
|
| Laravel の雛形には日本語の翻訳ファイルが含まれていません。
| このファイルが無いと、画面には "validation.required" のような
| 翻訳キーがそのまま表示されてしまいます。
|
| :attribute には項目名が入ります。項目名の日本語は各 FormRequest の
| attributes() メソッドで指定しています(例: login_id → ログインID)。
|
*/

return [

    'accepted'             => ':attributeを承認してください。',
    'accepted_if'          => ':otherが:valueの場合、:attributeを承認してください。',
    'active_url'           => ':attributeは有効なURLではありません。',
    'after'                => ':attributeには:date以降の日付を指定してください。',
    'after_or_equal'       => ':attributeには:date以降の日付を指定してください。',
    'alpha'                => ':attributeは英字のみで入力してください。',
    'alpha_dash'           => ':attributeは英数字とハイフン、アンダースコアのみで入力してください。',
    'alpha_num'            => ':attributeは英数字のみで入力してください。',
    'array'                => ':attributeの形式が正しくありません。',
    'before'               => ':attributeには:date以前の日付を指定してください。',
    'before_or_equal'      => ':attributeには:date以前の日付を指定してください。',
    'boolean'              => ':attributeには true か false を指定してください。',
    'confirmed'            => ':attributeが確認用の入力と一致しません。',
    'current_password'     => 'パスワードが正しくありません。',
    'date'                 => ':attributeは正しい日付ではありません。',
    'date_equals'          => ':attributeには:dateと同じ日付を指定してください。',
    'date_format'          => ':attributeは「:format」の形式で入力してください。',
    'declined'             => ':attributeを拒否してください。',
    'different'            => ':attributeと:otherには異なる値を指定してください。',
    'digits'               => ':attributeは:digits桁で入力してください。',
    'digits_between'       => ':attributeは:min桁から:max桁の間で入力してください。',
    'dimensions'           => ':attributeの画像サイズが正しくありません。',
    'distinct'             => ':attributeに重複した値があります。',
    'doesnt_end_with'      => ':attributeの末尾に次のいずれかを使うことはできません: :values',
    'doesnt_start_with'    => ':attributeの先頭に次のいずれかを使うことはできません: :values',
    'email'                => ':attributeは正しいメールアドレスの形式で入力してください。',
    'ends_with'            => ':attributeの末尾は次のいずれかにしてください: :values',
    'enum'                 => '選択された:attributeは正しくありません。',
    'exists'               => '選択された:attributeは正しくありません。',
    'file'                 => ':attributeはファイルを指定してください。',
    'filled'               => ':attributeを入力してください。',
    'gt'                   => [
        'array'   => ':attributeには:value個より多くの項目を指定してください。',
        'file'    => ':attributeには:value KBより大きいファイルを指定してください。',
        'numeric' => ':attributeには:valueより大きい値を指定してください。',
        'string'  => ':attributeは:value文字より長く入力してください。',
    ],
    'gte'                  => [
        'array'   => ':attributeには:value個以上の項目を指定してください。',
        'file'    => ':attributeには:value KB以上のファイルを指定してください。',
        'numeric' => ':attributeには:value以上の値を指定してください。',
        'string'  => ':attributeは:value文字以上で入力してください。',
    ],
    'image'                => ':attributeには画像ファイルを指定してください。',
    'in'                   => '選択された:attributeは正しくありません。',
    'in_array'             => ':attributeが:otherに存在しません。',
    'integer'              => ':attributeは整数で入力してください。',
    'ip'                   => ':attributeは正しいIPアドレスを入力してください。',
    'ipv4'                 => ':attributeは正しいIPv4アドレスを入力してください。',
    'ipv6'                 => ':attributeは正しいIPv6アドレスを入力してください。',
    'json'                 => ':attributeは正しいJSON形式で入力してください。',
    'lt'                   => [
        'array'   => ':attributeには:value個より少ない項目を指定してください。',
        'file'    => ':attributeには:value KBより小さいファイルを指定してください。',
        'numeric' => ':attributeには:valueより小さい値を指定してください。',
        'string'  => ':attributeは:value文字より短く入力してください。',
    ],
    'lte'                  => [
        'array'   => ':attributeには:value個以下の項目を指定してください。',
        'file'    => ':attributeには:value KB以下のファイルを指定してください。',
        'numeric' => ':attributeには:value以下の値を指定してください。',
        'string'  => ':attributeは:value文字以下で入力してください。',
    ],
    'max'                  => [
        'array'   => ':attributeは:max個以下にしてください。',
        'file'    => ':attributeは:max KB以下のファイルにしてください。',
        'numeric' => ':attributeには:max以下の値を指定してください。',
        'string'  => ':attributeは:max文字以内で入力してください。',
    ],
    'mimes'                => ':attributeには:valuesのファイルを指定してください。',
    'mimetypes'            => ':attributeには:valuesのファイルを指定してください。',
    'min'                  => [
        'array'   => ':attributeは:min個以上にしてください。',
        'file'    => ':attributeは:min KB以上のファイルにしてください。',
        'numeric' => ':attributeには:min以上の値を指定してください。',
        'string'  => ':attributeは:min文字以上で入力してください。',
    ],
    'not_in'               => '選択された:attributeは正しくありません。',
    'not_regex'            => ':attributeの形式が正しくありません。',
    'numeric'              => ':attributeは数値で入力してください。',
    'present'              => ':attributeが存在していません。',
    'prohibited'           => ':attributeは指定できません。',
    'prohibited_if'        => ':otherが:valueの場合、:attributeは指定できません。',
    'prohibited_unless'    => ':otherが:valuesでない場合、:attributeは指定できません。',
    'regex'                => ':attributeの形式が正しくありません。',
    'required'             => ':attributeを入力してください。',
    'required_array_keys'  => ':attributeには:valuesを含めてください。',
    'required_if'          => ':otherが:valueの場合、:attributeを入力してください。',
    'required_if_accepted' => ':otherが承認された場合、:attributeを入力してください。',
    'required_unless'      => ':otherが:valuesでない場合、:attributeを入力してください。',
    'required_with'        => ':valuesを入力した場合、:attributeも入力してください。',
    'required_with_all'    => ':valuesを入力した場合、:attributeも入力してください。',
    'required_without'     => ':valuesを入力しない場合、:attributeを入力してください。',
    'required_without_all' => ':valuesのいずれも入力しない場合、:attributeを入力してください。',
    'same'                 => ':attributeと:otherが一致しません。',
    'size'                 => [
        'array'   => ':attributeは:size個にしてください。',
        'file'    => ':attributeは:size KBのファイルにしてください。',
        'numeric' => ':attributeは:sizeにしてください。',
        'string'  => ':attributeは:size文字にしてください。',
    ],
    'starts_with'          => ':attributeの先頭は次のいずれかにしてください: :values',
    'string'               => ':attributeは文字列で入力してください。',
    'timezone'             => ':attributeは正しいタイムゾーンを指定してください。',
    'unique'               => 'その:attributeは既に使われています。',
    'uploaded'             => ':attributeのアップロードに失敗しました。',
    'url'                  => ':attributeは正しいURLの形式で入力してください。',
    'uuid'                 => ':attributeは正しいUUIDの形式で入力してください。',

    /*
    |--------------------------------------------------------------------------
    | パスワードの強度チェック (Rules\Password)
    |--------------------------------------------------------------------------
    */

    'password' => [
        'letters'       => ':attributeには英字を含めてください。',
        'mixed'         => ':attributeには大文字と小文字を含めてください。',
        'numbers'       => ':attributeには数字を含めてください。',
        'symbols'       => ':attributeには記号を含めてください。',
        'uncompromised' => 'その:attributeは漏えいが確認されています。別のものを指定してください。',
    ],

    /*
    |--------------------------------------------------------------------------
    | 個別のメッセージ
    |--------------------------------------------------------------------------
    |
    | 「項目名.ルール名」の形式で、特定の項目だけ文言を変えられます。
    |
    */

    'custom' => [
        'password' => [
            'min' => ':attributeは:min文字以上で入力してください。',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 項目名の日本語
    |--------------------------------------------------------------------------
    |
    | 各 FormRequest の attributes() で個別に指定していますが、
    | 指定漏れがあってもここで補えるよう、共通の項目名を置いています。
    |
    */

    'attributes' => [
        'login_id'                 => 'ログインID',
        'password'                 => 'パスワード',
        'password_confirmation'    => 'パスワード(確認用)',
        'current_password'         => '現在のパスワード',
        'name'                     => '表示名',
        'role'                     => '権限',
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
        'status'                   => 'ステータス',
        'assigned_user_id'         => '担当者',
        'source'                   => '流入経路',
        'notes'                    => '備考',
        'tags'                     => 'タグ',
        'contacted_at'             => '対応日時',
        'contact_method'           => '対応方法',
        'subject'                  => '件名',
        'response'                 => '対応内容',
        'next_action_at'           => '次回対応日',
        'service_name'             => 'サービス名',
        'contract_date'            => '契約日',
        'amount'                   => '金額',
        'contract_id'              => '契約',
        'issue_date'               => '発行日',
        'due_date'                 => '支払期限',
        'paid_at'                  => '入金日',
        'payment_method'           => '支払方法',
    ],

];
