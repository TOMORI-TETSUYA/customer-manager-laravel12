<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

/**
 * 操作履歴の保存 (§23.3)
 *
 * 保存しない情報:
 *   パスワード / セッションID / CSRFトークン / APP_KEY /
 *   DBパスワード / 暗号化前の個人情報 / 顧客備考の全文
 *
 * changed_fields には「変更されたカラム名のみ」を保存し、値は保存しない。
 */
class AuditLogService
{
    /**
     * @param list<string>|null $changedFields 変更カラム名のみ
     */
    public function record(
        string $action,
        ?string $targetType = null,
        ?int $targetId = null,
        ?array $changedFields = null,
        ?int $userId = null,
    ): void {
        AuditLog::query()->create([
            'user_id'        => $userId ?? Auth::id(),
            'action'         => $action,
            'target_type'    => $targetType,
            'target_id'      => $targetId,
            'changed_fields' => $changedFields,
            'ip_address'     => request()->ip(),
            'created_at'     => now(),
        ]);
    }
}
