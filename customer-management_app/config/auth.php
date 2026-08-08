<?php

use App\Models\User;

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option defines the default authentication "guard" and password
    | reset "broker" for your application. You may change these values
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | which utilizes session storage plus the Eloquent user provider.
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | Supported: "session"
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | If you have multiple user tables or models you may configure multiple
    | providers to represent the model / table. These providers may then
    | be assigned to any extra authentication guards you have defined.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', User::class),
        ],

        // 'users' => [
        //     'driver' => 'database',
        //     'table' => 'users',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | These configuration options specify the behavior of Laravel's password
    | reset functionality, including the table utilized for token storage
    | and the user provider that is invoked to actually retrieve users.
    |
    | The expiry time is the number of minutes that each reset token will be
    | considered valid. This security feature keeps tokens short-lived so
    | they have less time to be guessed. You may change this as needed.
    |
    | The throttle setting is the number of seconds a user must wait before
    | generating more password reset tokens. This prevents the user from
    | quickly generating a very large amount of password reset tokens.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Here you may define the number of seconds before a password confirmation
    | window expires and users are asked to re-enter their password via the
    | confirmation screen. By default, the timeout lasts for three hours.
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

    /*
    |--------------------------------------------------------------------------
    | ログインの有効日数 (§15)
    |--------------------------------------------------------------------------
    |
    | 最後にログインしてからこの日数が過ぎると、操作中であっても、
    | また「ログイン状態を保持する」を使っていても、強制的にログアウトして
    | 再ログインを求めます。
    |
    | 次の2か所で使っています。
    |   ・LoginController        … 「ログイン状態を保持する」のクッキー有効期限
    |   ・EnsureLoginNotExpired  … 毎リクエストでの経過日数チェック
    |
    | 0 を指定すると期限なし(この機能を無効化)になります。
    |
    */

    'login_max_days' => (int) env('LOGIN_MAX_DAYS', 3),

    /*
    |--------------------------------------------------------------------------
    | ログイン試行の制限と応答遅延 (§15.2)
    |--------------------------------------------------------------------------
    |
    | 総当たり攻撃を遅らせるための設定です。
    | 判定は「ログインID + IPアドレス」の組み合わせごとに行います。
    |
    |   max_attempts  : 許可する試行回数。これを超えるとブロックします。
    |   decay_minutes : ブロックが解除されるまでの時間(分)。
    |   failed_delay  : ログイン失敗時、応答を返すまで待つ秒数。
    |   blocked_delay : ブロック中の要求に対し、応答を返すまで待つ秒数。
    |
    | 【注意】遅延している間、PHP-FPM のプロセスを1つ占有し続けます。
    | 同時に遅延できる数は docker/php/www.conf の pm.max_children が上限で、
    | それを超えると他の利用者まで待たされます。あわせて次の値が
    | 遅延秒数より長いことを確認してください。
    |   ・docker/php/php.ini      max_execution_time
    |   ・docker/nginx/default.conf  fastcgi_read_timeout
    |
    | 遅延を無効にするには 0 を指定します。
    |
    */

    'login_throttle' => [
        'max_attempts'  => (int) env('LOGIN_MAX_ATTEMPTS', 3),
        'decay_minutes' => (int) env('LOGIN_DECAY_MINUTES', 60),
        'failed_delay'  => (int) env('LOGIN_FAILED_DELAY', 10),
        'blocked_delay' => (int) env('LOGIN_BLOCKED_DELAY', 120),
    ],

    /*
    |--------------------------------------------------------------------------
    | 顧客の担当者に指定できないログインID
    |--------------------------------------------------------------------------
    |
    | システムの運用管理用アカウントを、顧客の「担当者」の選択肢から外すための
    | 設定です。ここに挙げたログインIDは
    |   ・顧客の登録／編集画面のプルダウンに表示されない
    |   ・顧客一覧の担当者フィルターにも表示されない
    |   ・フォームを改ざんして直接指定してもサーバー側で弾かれる
    | という扱いになります。
    |
    | 複数指定する場合はカンマ区切りにします(例: admin,system)。
    | 空にすると全ユーザーが担当者として選べるようになります。
    |
    | ※ 既にその人が担当になっている顧客の編集だけは例外で、担当者を
    |   変更せずに保存できます(変更しないと保存できなくなるのを防ぐため)。
    |
    */

    'non_assignable_login_ids' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('NON_ASSIGNABLE_LOGIN_IDS', 'admin'))
    ))),

];
