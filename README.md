# Patron Hub — 顧客管理システム

Laravel 12 / PHP 8.4 / Nginx + PHP-FPM / MySQL 8.4 / Docker Compose / Bootstrap 5.3.8 で構築した小規模事業者向け顧客管理システムです。

## ディレクトリ構成

```
customer_manager/
├── compose.yaml                  # Docker Compose 定義
├── Commando.md                   # 復旧・構築コマンド全集(証明書取得・Docker含む)
├── docker/                       # Nginx / PHP / MySQL 設定・初期化SQL
│   └── mysql/init/001_schema.sql # 全テーブルDDL(初回起動時に自動実行)
├── customer-management/          # 公開ディレクトリ(ドキュメントルート)
│   ├── php/index.php             # 唯一の公開PHPエントリポイント
│   ├── css/ js/                  # 自作アセット
│   └── vendor/bootstrap/5.3.8/   # Bootstrap 同梱(CDN不使用)
└── customer-management_app/      # Laravel 本体(非公開領域)
```

> 実際に使ったコマンドは全て [Commando.md](Commando.md) にまとめてあります。
> 証明書の取得方式、Docker コマンド、障害時の対処まで、コマンドごとに
> 「何をするか / なぜ必要か / どうなれば成功か」を記載しています。

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

`ログインID` と `表示名` の2つを引数で渡します(対話入力ではありません)。

```bash
docker compose exec app php artisan app:create-admin admin "システム管理者"
```

初期パスワードは 16 文字でランダム生成され、**実行時に一度だけ表示されます**。控えてから閉じてください。初回ログイン時にパスワード変更が強制されます。

### 6. アクセス

```
http://localhost:8090
```

#### 初期管理者アカウント

ログインIDは手順5で指定した値、パスワードは同手順の実行時に表示されたランダム値です。
初回ログイン時にパスワード変更が強制されます。

> **注意**: 初期パスワードを README やチケットに書き残さないでください。README.md は
> Git 管理下にあるため、コミットすると履歴に残り続けます。受け渡しが必要な場合は
> パスワードマネージャ等の別経路を使ってください。

## 本番構成: リバースプロキシ配下での運用

本番では、ホスト側の nginx をリバースプロキシとして手前に配置し、Let's Encrypt 証明書で TLS 終端した上で、
Docker Compose の各コンテナ（ループバック限定で公開）へ転送しています。

```
[インターネット] ── HTTPS:443 ──▶ ホストの nginx (リバースプロキシ / TLS終端)
                                        │
                                        ├─▶ 127.0.0.1:8090 ─▶ [Docker] nginx ─▶ app(PHP-FPM) ─▶ db
                                        │     customer-manager.post-house-system.com
                                        │
                                        └─▶ 127.0.0.1:8091 ─▶ [Docker] phpmyadmin ─▶ db
                                              phpmyadmin-customer-manager.post-house-system.com
```

### リバースプロキシ設定（ホスト側 nginx。このリポジトリの管理外）

ドメインごとに TLS 終端する `server` ブロックが2つと、80番から443番へリダイレクトするブロックが1つです。

```nginx
server {
        listen  443 ssl;
        server_name  customer-manager.post-house-system.com;
        ssl_certificate     /etc/letsencrypt/live/customer-manager.post-house-system.com/fullchain.pem;
        ssl_certificate_key /etc/letsencrypt/live/customer-manager.post-house-system.com/privkey.pem;

        location / {
                proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
                proxy_set_header X-Forwarded-Host $http_host;
                proxy_set_header X-Forwarded-Proto $scheme;   # アプリ側にHTTPSであることを伝える
                proxy_set_header Host $host;
                proxy_pass http://localhost:8090/;             # ← compose.yaml の nginx (.env の APP_PORT) と一致させる
        }
}
server {
        listen  443 ssl;
        server_name  phpmyadmin-customer-manager.post-house-system.com;
        ssl_certificate     /etc/letsencrypt/live/phpmyadmin-customer-manager.post-house-system.com/fullchain.pem;
        ssl_certificate_key /etc/letsencrypt/live/phpmyadmin-customer-manager.post-house-system.com/privkey.pem;

        location / {
                proxy_set_header X-Forwarded-Proto $scheme;
                proxy_set_header Host $host;
                proxy_pass http://127.0.0.1:8091/;             # ← compose.yaml の phpmyadmin (.env の PMA_PORT) と一致させる
        }
}
server {
        listen 80;
        server_name customer-manager.post-house-system.com phpmyadmin-customer-manager.post-house-system.com;
        location / {
                return 301 https://$host$request_uri;          # 平文HTTPは常にHTTPSへリダイレクト
        }
}
```

> **重要**: `proxy_pass` のポート番号（8090 / 8091）は、`.env` の `APP_PORT` / `PMA_PORT` の値と必ず一致させてください。
> どちらか一方だけを変更すると、リバースプロキシが Docker コンテナに到達できなくなります。

### compose.yaml の変更点（リバースプロキシ導入に伴う）

| 項目 | 変更前 | 変更後 | 理由 |
| --- | --- | --- | --- |
| `nginx.ports` | `"${APP_PORT:-8090}:80"` | `"127.0.0.1:${APP_PORT:-8090}:80"` | リバースプロキシを経由しない直接アクセス（TLSなし）を遮断するため、`db` / `phpmyadmin` と同じくループバック限定に変更 |
| 各所のコメント | ポート `8080`（旧既定値）を前提とした記述 | 実際の既定値（`APP_PORT=8090` / `PMA_PORT=8091`）に整合するよう更新 | ドキュメントとコードの不一致を解消 |

`APP_PORT`(既定 8090) と `PMA_PORT`(既定 8091) の**値自体**はリバースプロキシ導入前から変わっていません。変わったのは、
コンテナの公開範囲を「ホスト全体」から「ホストのループバックのみ」に絞った点です。これにより、リバースプロキシ（HTTPS・
ドメイン確認・ヘッダー整形を行う唯一の窓口）を経由しないアクセスができなくなります。

### .env の構成（本番）

`.env`（ルート）と `customer-management_app/.env` はどちらも Git 管理外（`.gitignore` 済み）です。実際のシークレット値は
サーバー上のファイルを直接参照してください。ここでは値の**意味と、リバースプロキシとの対応関係**のみ記載します。

**ルート `.env`（Docker用。実際に使われるのは7項目のみ）**
```bash
APP_PORT=8090             # リバースプロキシの proxy_pass http://localhost:8090/ と一致させる
PMA_PORT=8091              # リバースプロキシの proxy_pass http://127.0.0.1:8091/ と一致させる
DB_DATABASE=customer_manager
DB_USERNAME=customer_user
DB_PASSWORD=<openssl rand -hex 16 で生成。customer-management_app/.env と同じ値>
DB_ROOT_PASSWORD=<openssl rand -hex 16 で生成。customer-management_app/.env と同じ値>
DB_PORT_HOST=3307          # 127.0.0.1限定。DBクライアント接続専用、リバースプロキシとは無関係
```

**`customer-management_app/.env`（Laravel本体。実際にアプリが読む設定）**
```bash
APP_ENV=production                                        # リバースプロキシ配下での一般公開のため
APP_DEBUG=false
APP_URL=https://customer-manager.post-house-system.com    # http://localhost:8090 ではなく公開URLを指定
APP_KEY=<openssl rand -base64 32 を base64: 接頭辞つきで。php artisan key:generate と同等>
SESSION_SECURE_COOKIE=true                                # HTTPS運用のため true（ローカル開発時は false）
DB_DATABASE / DB_USERNAME / DB_PASSWORD                    # ルート .env と同じ値にする
DB_ROOT_PASSWORD                                            # ルート .env と同じ値にする
SEARCH_HASH_KEY=<openssl rand -base64 32 で生成>
```

> **`APP_URL` に注意**: `http://localhost:8090` のままにすると、キューワーカーや artisan コマンドなど
> HTTP リクエストの外側で URL を生成する場面（通知メールのリンクなど）で誤った URL が使われます。
> 本番では必ずリバースプロキシが応答する公開 URL（`https://customer-manager.post-house-system.com`）を
> 指定してください。

### アプリ側の対応: リバースプロキシの信頼設定

`customer-management_app/bootstrap/app.php` で `trustProxies` を設定しています。これが無いと、HTTPリクエスト
処理中に生成されるURL（フォームの action 属性、リダイレクト先など）がリバースプロキシ越しでも `http://` のまま
判定されてしまいます（`APP_URL` はコンソール経由で生成されるURL専用の設定のため、この問題までは解決しません）。

```php
$middleware->trustProxies(
    at: '*',   // app コンテナはループバック限定公開でリバースプロキシからしか到達できないため安全
    headers: Request::HEADER_X_FORWARDED_FOR
        | Request::HEADER_X_FORWARDED_HOST
        | Request::HEADER_X_FORWARDED_PORT
        | Request::HEADER_X_FORWARDED_PROTO,
);
```

## 公開時に発生した障害と対策(2026-08-09)

初回公開時、ブラウザに `net::ERR_CERT_COMMON_NAME_INVALID`(この接続ではプライバシーが保護されません)が
表示され画面が開けませんでした。原因は **3つの問題が積み重なっていた**ためで、1つ直しても次が出てくる
構造でした。同じ状況を繰り返さないよう、経緯と対策を記録します。

コマンドの詳細は [Commando.md](Commando.md) を参照してください。

### 症状と真因

一見「証明書が間違っている」ように見えましたが、真因は「**設定が nginx に読み込まれていなかった**」ことでした。

`openssl` で実際に返ってくる証明書を確認したところ、全く別サイトの証明書が返っていました。

```
$ openssl s_client -connect 127.0.0.1:443 \
    -servername customer-manager.post-house-system.com </dev/null 2>/dev/null \
  | openssl x509 -noout -subject

subject=CN=rtc.post-house-system.com     ← 同居する別サイトの証明書
```

これは nginx が「該当する `server` ブロックが無い」と判断し、**443番のデフォルト server**
(同居サイト `rtc`)にフォールバックしていたためです。

### 重なっていた3つの問題

| # | 問題 | 症状 | 影響範囲 |
| --- | --- | --- | --- |
| 1 | `nginx.conf` の構文エラー | 設定が一度も反映されない | **同居する全9サイト**(再起動で起動不能) |
| 2 | TLS 証明書が未取得 | 構文を直すと `cannot load certificate` で失敗 | customer-manager のみ |
| 3 | Docker コンテナが未起動 | 1と2を直しても 502 Bad Gateway | customer-manager のみ |

#### 問題1: `server` ブロックが `http{}` の外側にあった(根本原因)

追記された3つの `server` ブロックが `http{}` の外に置かれていました。

```nginx
 629:        }
 630:}            ← この } が http{} を閉じてしまっていた
 631:
 632:server {     ← main コンテキスト直下に落ちている(nginx の文法違反)
 ...
 716:}            ← 余剰の閉じ括弧
```

`nginx -t` が `"server" directive is not allowed here in /etc/nginx/nginx.conf:632` で失敗するため、
**リロードが拒否され続けていました**。設定ファイルの更新時刻(8/9 04:51)より、nginx の最終リロード時刻
(8/7 12:03)の方が古いことから判明しました。

> **最も危険だった点**: `nginx.service` は `enabled` かつ
> `ExecStartPre=/usr/sbin/nginx -t -q` を持つため、**この状態でサーバが再起動すると nginx が
> 起動できず、同居する全9サイトが恒久停止する**ところでした。customer-manager の公開より
> 緊急度の高い障害でした。

#### 問題2: 証明書が未取得

`/etc/letsencrypt/live/` に対象2ドメインのディレクトリが存在しませんでした。
問題1を直しても、次は証明書ファイルが見つからず nginx が起動できない状態でした。

#### 問題3: コンテナが未起動

`docker compose ps -a` で全コンテナが `Created`(作られただけで一度も起動していない)状態でした。
`127.0.0.1:8090` / `8091` は待ち受けておらず、1と2を直しても 502 になる状態でした。

### 実施した対策

依存関係があるため、**この順序でしか解決できません**。

| 順 | 対策 | 理由 |
| --- | --- | --- |
| 1 | `nginx.conf` を編集前のバックアップへ戻す | 文法が通らないとリロードできない。再起動事故も解除 |
| 2 | customer-manager の設定を `/etc/nginx/conf.d/zz-customer-manager.conf` へ分離 | 24KB の共有設定を触らずに済み、切り戻しがファイル削除だけで完結する |
| 3 | まず `:80`(ACME用)だけ適用 → 証明書取得 → `:443` を追加 | 証明書が無い状態で `ssl_certificate` を書くと nginx が落ちるため段階適用 |
| 4 | `certbot certonly --webroot` で取得 | 既存9サイトと同じ方式。無停止で取得でき、自動更新にも乗る |
| 5 | ルート `.env` を配置してコンテナ起動 | `.env` が無いと `MYSQL_DATABASE` 等が空になり DB 初期化に失敗する |
| 6 | 最後に `:443` を有効化 | コンテナ起動前に有効化すると 502 になる |

### 恒久的な改善(コードへ反映済み)

| 対象ファイル | 変更内容 | 目的 |
| --- | --- | --- |
| `compose.yaml` | nginx の公開を `"127.0.0.1:${APP_PORT}:80"` に変更 | リバースプロキシを経由しない直接アクセス(TLSなし)を遮断 |
| `bootstrap/app.php` | `trustProxies` を追加 | `X-Forwarded-Proto` を信頼し、リクエスト中に生成される URL を `https://` にする |
| `docker/php/www.conf` | `user = 1000` / `group = 1000` を追加 | バインドマウント環境で `.env` 読み取りと `storage/` 書き込みを可能にする |
| `docker/php/www.conf` | `catch_workers_output = yes` を追加 | 既定では破棄される PHP の致命的エラーを `docker compose logs` に出す |
| `.env.example` (両方) | `APP_PORT` / `APP_URL` を実構成に合わせて修正 | ドキュメントと実態の乖離を解消 |

### 再発防止のチェックリスト

nginx の設定を変更したら、**必ず**以下を確認してください。

```bash
# ① 文法チェック。失敗したまま絶対に放置しない(再起動で全サイトが落ちる)
sudo nginx -t

# ② リロードが実際に反映されたか(ワーカーの起動時刻が更新されているか)
ps -o lstart= --ppid $(pgrep -f 'nginx: master process /usr/sbin/nginx' | head -1) | head -1

# ③ 意図した証明書が返っているか
openssl s_client -connect 127.0.0.1:443 -servername <ドメイン> </dev/null 2>/dev/null \
  | openssl x509 -noout -subject
```

- **`sudo systemctl restart nginx` は使わない。** 必ず `sudo nginx -t && sudo systemctl reload nginx`。
  `reload` は設定不正でも旧プロセスが生き残りますが、`restart` は全サイトを巻き込んで停止します
- **`nginx -t` が失敗した状態で作業を終えない。** サーバ再起動時に全サイトが起動不能になります
- **設定変更後はワーカーの起動時刻を確認する。** 「編集したが反映されていない」を見逃さないため

### 途中で判明した既存の問題(未対応)

本件とは別に、調査中に見つかった既存の問題です。対応は別途判断が必要です。

| 問題 | 内容 | 影響 |
| --- | --- | --- |
| TLS 秘密鍵の権限 | `/etc/letsencrypt` が `755`、既存9サイトの `privkey.pem` が `755` | サーバ上の全ユーザーが秘密鍵を読める(今回取得した2枚は `600` で正常) |
| `server_name` のタイポ | `nginx.conf:510` が `phpmyadmin-rayla.post-house-system.com**a**` | 該当ドメインが名前解決されない |
| `rtc` の証明書更新 | `authenticator=standalone` だが 80番を nginx が占有中 | 次回更新(9月上旬)で失敗する見込み |
| phpMyAdmin の公開範囲 | インターネットから誰でも到達可能 | `compose.yaml` 自身が「本番では削除せよ」と注記。IP制限等の検討が必要 |

## 主な仕様

- **認証**: ログイン試行制限(5回/1分)、失敗時は統一エラー文言、Argon2id、12文字以上、初回パスワード変更強制
- **個人情報保護**: 氏名・電話・メール・住所等はアプリ層で暗号化して保存。検索は HMAC-SHA256 検索ハッシュ(`phone_hash` / `email_hash`)+下4桁(`phone_last4`)で実現
- **権限**: 管理者(`admin`) / 職員(`staff`) / メンバー(`member`) の3ロール。管理者と職員は全画面にアクセス可。メンバーは顧客管理・契約管理・ユーザー管理のみ。ユーザー管理に表示される範囲はロールごとに異なります(詳細は [SECURITY.md](SECURITY.md) §4)
- **URL**: 対応履歴 `/dialog`、契約管理 `/contract`、請求・入金 `/payment`。いずれも顧客一覧を既定の絞り込み・並び順で開きます
- **一覧**: 初期25件、25/50/100/200 切替。全件取得は行いません
- **監査**: 主要操作を操作履歴に記録。`changed_fields` にはカラム名のみを保存し、値(機密)は保存しません
- **レスポンシブ**: 768px 未満はテーブル→カード表示+Offcanvas ナビ。横スクロールなし
- **アクセシビリティ**: `prefers-reduced-motion: reduce` 時はログイン演出を含む全アニメーションを停止

## トラブルシューティング

| 症状 | 対処 |
| --- | --- |
| 証明書エラー(`ERR_CERT_COMMON_NAME_INVALID`) | 別サイトの証明書が返っていないか `openssl s_client -servername <ドメイン>` で確認。別サイトの CN が返るなら設定が未反映。[Commando.md 4.1](Commando.md#41-tls-証明書を確認する) |
| 502 Bad Gateway | 転送先に繋がっていない。コンテナ再作成で IP が変わった場合は `docker compose restart nginx`。[Commando.md 6.2](Commando.md#62-502-bad-gateway) |
| 500 エラー | まず `.env` をコンテナが読めるか確認: `docker compose exec -T -u www-data app php artisan about \| grep "Application Name"`。`Laravel` と出たら `.env` が読めていない。[Commando.md 6.3](Commando.md#63-500-internal-server-error) |
| 500 エラー(上記以外) | `docker compose exec app php artisan config:clear` 後、`.env` の `APP_KEY` / `SEARCH_HASH_KEY` を確認 |
| DB が起動しない(`port is already allocated`) | ホスト側ポートの競合。`.env` の `DB_PORT_HOST` を空き番号に変更。[Commando.md 6.1](Commando.md#61-ポート競合で-db-が起動しない) |
| DB 接続エラー | ルート `.env` と `customer-management_app/.env` の DB 設定一致を確認。初回は MySQL 初期化完了まで数十秒待つ |
| nginx 設定を変えたのに反映されない | リロードが失敗している可能性。`sudo nginx -t` で文法を確認。[Commando.md 4.3](Commando.md#43-nginx-の状態を確認する) |
| スキーマを作り直したい | `docker compose down -v` でボリューム削除後、再度 `up -d --build`(**既存データは全消去**) |

- コマンドの全一覧: [Commando.md](Commando.md)
- セキュリティ方針の詳細: [SECURITY.md](SECURITY.md)
