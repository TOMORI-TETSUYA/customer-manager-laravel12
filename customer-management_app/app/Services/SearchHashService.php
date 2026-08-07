<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * 電話番号・メールアドレスの正規化と検索用ハッシュ生成 (§18.2)
 *
 * 暗号化カラムはランダムIVを含むため直接検索できない。
 * 完全一致検索は HMAC-SHA256 の決定的ハッシュで行う。
 */
class SearchHashService
{
    /**
     * 電話番号を正規化する。
     * 入力値: 090-1234-5678 → 正規化: 09012345678
     */
    public function normalizePhone(string $phone): string
    {
        $normalized = mb_convert_kana($phone, 'n');

        return preg_replace('/[^0-9]/', '', $normalized) ?? '';
    }

    /** メールアドレスを正規化する(小文字化・前後空白除去)。 */
    public function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /** 正規化済みの値から検索用ハッシュ(64桁hex)を生成する。 */
    public function hash(string $normalizedValue): string
    {
        $key = (string) config('search.hash_key');

        if ($key === '' || $key === 'change_this_search_hash_key') {
            throw new RuntimeException(
                'SEARCH_HASH_KEY が未設定です。.env を確認してください。'
            );
        }

        return hash_hmac('sha256', $normalizedValue, $key);
    }

    /** 電話番号の下4桁を返す(マスク表示用)。 */
    public function phoneLast4(string $normalizedPhone): string
    {
        return substr($normalizedPhone, -4);
    }
}
