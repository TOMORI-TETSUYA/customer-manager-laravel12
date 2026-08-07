# Patron Hub — 顧客管理システム

Laravel 12 / PHP 8.4 / Nginx + PHP-FPM / MySQL 8.4 / Docker Compose / Bootstrap 5.3.8 で構築した小規模事業者向け顧客管理システムです。

## ディレクトリ構成

```
customer_manager/
├── compose.yaml                  # Docker Compose 定義
├── docker/                       # Nginx / PHP / MySQL 設定・初期化SQL
│   └── mysql/init/001_schema.sql # 全テーブルDDL(初回起動時に自動実行)
├── customer-management/          # 公開ディレクトリ(ドキュメントルート)
│   ├── php/index.php             # 唯一の公開PHPエントリポイント
│   ├── css/ js/                  # 自作アセット
│   └── vendor/bootstrap/5.3.8/   # Bootstrap 同梱(CDN不使用)
└── customer-management_app/      # Laravel 本体(非公開領域)
```

## セットアップ手順

前提: Docker Desktop(または Docker Engine + Compose v2)がインストール済みであること。

### 1. Laravel 雛形の用意と本ファイル群の適用

本リポジトリは「仕様に関わる差分ファイル一式」です。まず Laravel 12 の雛形を作成し、その上に `customer-management_app/` の中身を上書きコピーしてください。

```bash
composer create-project laravel/laravel:^12.0 tmp-laravel
cp -r tmp-laravel/. customer_manager/customer-management_app/   # 既存ファイルは上書きしない(-n 推奨)
# 逆方向(本リポジトリ→雛形)で上書きしても構いません。本リポジトリのファイルが優先です。
```

> 同名ファイル(`bootstrap/app.php`、`routes/web.php` など)は **本リポジトリ側を必ず優先** してください。

### 2. 環境変数の設定

```bash
cp .env.example .env                                  # ルート(Docker用)
cp customer-management_app/.env.example customer-management_app/.env
```

`customer-management_app/.env` の以下を必ず設定します。

| キー | 説明 |
| --- | --- |
| `APP_KEY` | 手順4で `php artisan key:generate` により自動設定 |
| `SEARCH_HASH_KEY` | 電話番号・メールの検索ハッシュ用秘密鍵。`openssl rand -base64 32` などで生成 |
| `DB_*` | ルート `.env` の MySQL 設定と一致させる |

### 3. コンテナ起動

```bash
docker compose up -d --build
```

初回起動時に `docker/mysql/init/001_schema.sql` が実行され、全テーブルが作成されます。

### 4. 依存インストールとキー生成

```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
```

### 5. 初期管理者の作成

初期管理者は SQL では作成せず、専用 artisan コマンドで作成します(パスワードは Argon2id でハッシュ化されます)。

```bash
docker compose exec app php artisan app:create-admin
```

対話形式で `ログインID / 氏名 / 初期パスワード(12文字以上)` を入力してください。初回ログイン時にパスワード変更が強制されます。

### 6. アクセス

```
http://localhost:8080
```

## 主な仕様

- **認証**: ログイン試行制限(5回/1分)、失敗時は統一エラー文言、Argon2id、12文字以上、初回パスワード変更強制
- **個人情報保護**: 氏名・電話・メール・住所等はアプリ層で暗号化して保存。検索は HMAC-SHA256 検索ハッシュ(`phone_hash` / `email_hash`)+下4桁(`phone_last4`)で実現
- **権限**: `admin` / `staff` / `viewer` の3ロール。書込系は Gate `write-data`、ユーザー管理・操作履歴は admin のみ
- **一覧**: 初期25件、25/50/100/200 切替。全件取得は行いません
- **監査**: 主要操作を操作履歴に記録。`changed_fields` にはカラム名のみを保存し、値(機密)は保存しません
- **レスポンシブ**: 768px 未満はテーブル→カード表示+Offcanvas ナビ。横スクロールなし
- **アクセシビリティ**: `prefers-reduced-motion: reduce` 時はログイン演出を含む全アニメーションを停止

## トラブルシューティング

| 症状 | 対処 |
| --- | --- |
| 500 エラー | `docker compose exec app php artisan config:clear` 後、`.env` の `APP_KEY` / `SEARCH_HASH_KEY` を確認 |
| DB 接続エラー | ルート `.env` と `customer-management_app/.env` の DB 設定一致を確認。初回は MySQL 初期化完了まで数十秒待つ |
| スキーマを作り直したい | `docker compose down -v` でボリューム削除後、再度 `up -d --build` |

セキュリティ方針の詳細は [SECURITY.md](SECURITY.md) を参照してください。
