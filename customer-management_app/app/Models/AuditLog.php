<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'target_type',
        'target_id',
        'changed_fields',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'changed_fields' => 'array',
            'created_at'     => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 記録される操作の一覧と表示名 (§23.3)
     *
     * 一覧の表示と絞り込みの選択肢の両方でこれを使う。
     * 新しい操作を record() で記録するようにしたら、必ずここへ追加すること。
     * 追加を忘れると、画面には英語の識別子がそのまま出て、
     * 絞り込みの選択肢にも現れなくなる。
     */
    public const ACTIONS = [
        // 認証
        'login_success'       => 'ログイン成功',
        'login_failed'        => 'ログイン失敗',
        'login_blocked'       => 'ログイン制限',
        'logout'              => 'ログアウト',
        'password_changed'    => 'パスワード変更',
        // 顧客
        'customer_create'     => '顧客登録',
        'customer_update'     => '顧客更新',
        'customer_delete'     => '顧客削除',
        'customer_restore'    => '顧客復元',
        // 顧客に紐づく記録
        'contact_create'      => '対応履歴登録',
        'contract_create'     => '契約登録',
        'invoice_create'      => '請求登録',
        'payment_create'      => '入金登録',
        // ユーザー管理
        'user_create'         => 'ユーザー作成',
        'user_enable'         => 'ユーザー有効化',
        'user_disable'        => 'ユーザー無効化',
        'user_password_reset' => 'パスワード再発行',
        'user_delete'         => 'ユーザー削除',
    ];

    /** 操作対象の種類と表示名 */
    public const TARGET_TYPES = [
        'user'             => 'ユーザー',
        'customer'         => '顧客',
        'customer_contact' => '対応履歴',
        'contract'         => '契約',
        'invoice'          => '請求',
        'payment'          => '入金',
    ];

    /** 操作の表示名。未定義の識別子はそのまま返す。 */
    public function actionLabel(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }

    /** 操作対象の表示名。対象が無い操作(ログインなど)は null。 */
    public function targetLabel(): ?string
    {
        if ($this->target_type === null) {
            return null;
        }

        $type = self::TARGET_TYPES[$this->target_type] ?? $this->target_type;

        return $this->target_id === null
            ? $type
            : "{$type} #{$this->target_id}";
    }
}
