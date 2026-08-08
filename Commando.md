# Commando.md — 復旧・構築コマンド全集

Patron Hub をリバースプロキシ配下で公開するまでに実際に使ったコマンドを、
**何をするコマンドなのか / なぜ必要なのか / どうなれば成功なのか** まで含めて全て記録したものです。

同じ作業を再現するとき、同じ障害が起きたとき、別プロジェクトを同じ構成で公開するときに参照してください。

---

## 目次

- [1. このドキュメントについて](#1-このドキュメントについて)
- [2. システム構成](#2-システム構成)
- [3. 何が起きていたか](#3-何が起きていたか)
- [4. 調査に使ったコマンド](#4-調査に使ったコマンド)
  - [4.1 TLS 証明書を確認する](#41-tls-証明書を確認する)
  - [4.2 DNS を確認する](#42-dns-を確認する)
  - [4.3 nginx の状態を確認する](#43-nginx-の状態を確認する)
  - [4.4 Docker の状態を確認する](#44-docker-の状態を確認する)
- [5. 復旧に使ったコマンド](#5-復旧に使ったコマンド)
  - [5.1 nginx 設定を修正する](#51-nginx-設定を修正する)
  - [5.2 証明書を取得する](#52-証明書を取得する)
  - [5.3 コンテナを起動する](#53-コンテナを起動する)
  - [5.4 HTTPS を有効化する](#54-https-を有効化する)
- [6. 途中で起きた障害と対処](#6-途中で起きた障害と対処)
  - [6.1 ポート競合で DB が起動しない](#61-ポート競合で-db-が起動しない)
  - [6.2 502 Bad Gateway](#62-502-bad-gateway)
  - [6.3 500 Internal Server Error](#63-500-internal-server-error)
- [7. 管理者アカウントを作成する](#7-管理者アカウントを作成する)
- [8. 動作確認コマンド](#8-動作確認コマンド)
- [9. 切り戻し手順](#9-切り戻し手順)
- [10. やってはいけないこと](#10-やってはいけないこと)
- [11. よく使う Docker コマンド](#11-よく使う-docker-コマンド)
- [12. 用語集](#12-用語集)

---

## 1. このドキュメントについて

### 読み方

コマンドは全て次の形式で書いています。

```bash
# 何をするコマンドか（1行説明）
実際のコマンド
```

各コマンドには「期待される出力」を添えています。**出力が違ったら、そこで止まって原因を調べてください。**
失敗したまま次に進むと、後の手順が全て無意味になるか、状況が悪化します。

### 実行環境

このドキュメントのコマンドは、下記の環境で実際に実行し、動作を確認したものです。

| 対象 | バージョン |
| --- | --- |
| OS | Ubuntu (Linux 6.14) |
| nginx（ホスト側・リバースプロキシ） | 1.26.3 |
| certbot | 2.11.0 |
| Docker | 29.1.2 |
| Docker Compose | 5.0.0 |
| PHP（コンテナ内） | 8.4.24 |
| Laravel | 12.65.0 |
| MySQL（コンテナ内） | 8.4.8 |

### `sudo` が必要なコマンドについて

`sudo` が付いているコマンドは **root 権限が必要**です。付いていないものは一般ユーザーで実行できます。

このサーバは **他に9サイトが同居している共有本番サーバ**です。
`sudo` の付くコマンドは他サイトを巻き込む可能性があるため、実行前に必ず
[10. やってはいけないこと](#10-やってはいけないこと) に目を通してください。

---

## 2. システム構成

```
[インターネット]
      │ HTTPS :443
      ▼
┌─────────────────────────────────────────────┐
│ ホストの nginx（リバースプロキシ / TLS終端） │  ← Ubuntu 上で直接動作
│ 設定: /etc/nginx/nginx.conf                 │     他9サイトもここが処理
│       /etc/nginx/conf.d/*.conf              │
└──────────┬──────────────────┬───────────────┘
           │ 127.0.0.1:8090   │ 127.0.0.1:8091
           ▼                  ▼
┌──────────────────┐  ┌──────────────────┐
│ nginx コンテナ    │  │ phpmyadmin       │   ← ここから下は Docker
│ (静的ファイル)    │  │ コンテナ          │      /var/customer-manager
└────────┬─────────┘  └────────┬─────────┘
         │ app:9000            │
         ▼                     │
┌──────────────────┐           │
│ app コンテナ      │           │
│ (PHP-FPM/Laravel)│           │
└────────┬─────────┘           │
         │ db:3306             │ db:3306
         ▼                     ▼
      ┌──────────────────────────┐
      │ db コンテナ (MySQL 8.4)   │
      └──────────────────────────┘
```

### 重要な考え方

**コンテナのポートは全て `127.0.0.1` にだけ公開しています。**
`0.0.0.0`（全インターフェース）ではありません。

これは「**インターネットからの入口はホストの nginx ただ1つ**」にするためです。
もし `0.0.0.0:8090` で公開すると、`http://サーバのIP:8090` に直接アクセスされ、
TLS もドメイン確認も経由せずアプリに到達できてしまいます。

| 公開ポート | 中身 | 誰がアクセスできるか |
| --- | --- | --- |
| `127.0.0.1:8090` | アプリ本体 | ホスト内のプロセスのみ（＝ホストの nginx） |
| `127.0.0.1:8091` | phpMyAdmin | 同上 |
| `127.0.0.1:3308` | MySQL | 同上（DBクライアント接続用） |

---

## 3. 何が起きていたか

ブラウザに `net::ERR_CERT_COMMON_NAME_INVALID`（この接続ではプライバシーが保護されません）が表示され、
画面が開けませんでした。

原因は**3つの問題が積み重なっていた**ためです。1つ直しても次が出てくる構造でした。

| # | 問題 | 直さないとどうなるか |
| --- | --- | --- |
| 1 | `nginx.conf` の構文エラー（`server` ブロックが `http{}` の外側にあった） | 設定が一度も反映されない。**サーバ再起動で全9サイトが起動不能になる** |
| 2 | TLS 証明書が未取得 | 構文を直しても `cannot load certificate` で nginx が落ちる |
| 3 | Docker コンテナが未起動 | 1と2を直しても転送先が無く 502 Bad Gateway になる |

### 問題1の詳細（これが根本原因）

追記された3つの `server` ブロックが、`http{}` ブロックの**外側**に置かれていました。

```nginx
 629:        }
 630:}            ← この } が http{} を閉じてしまっている
 631:
 632:server {     ← main コンテキスト直下に落ちている（nginx の文法違反）
 ...
 716:}            ← 余剰の閉じ括弧
```

nginx では `server` ブロックは必ず `http{}` の中に書く必要があります。この状態で `nginx -t` を実行すると
`"server" directive is not allowed here` で失敗し、**リロードが拒否されます**。

その結果どうなったか:

1. 稼働中の nginx には新しい設定が入らない
2. `customer-manager.post-house-system.com` に対応する `server` ブロックが存在しない
3. nginx は **443番の「デフォルト server」** にフォールバックする
4. デフォルト server は別サイト `rtc.post-house-system.com` だった
5. rtc の証明書が返される → **ドメイン名が一致せず証明書エラー**

「証明書が間違っている」ように見えて、実際は「**設定が読み込まれていなかった**」のが真因でした。

---

## 4. 調査に使ったコマンド

### 4.1 TLS 証明書を確認する

ブラウザの証明書エラーは、**実際にどの証明書が返っているか**を見れば原因の切り分けができます。

```bash
# 指定したドメイン名(SNI)でTLS接続し、返ってきた証明書の中身を表示する
#   -servername : ブラウザが送る「アクセスしたいドメイン名」を指定する（これが重要）
#   </dev/null  : 入力待ちで固まらないようにする
openssl s_client -connect 127.0.0.1:443 -servername customer-manager.post-house-system.com </dev/null 2>/dev/null \
  | openssl x509 -noout -subject -issuer -dates -ext subjectAltName
```

**期待される出力（正常時）**

```
subject=CN=customer-manager.post-house-system.com    ← アクセスしたドメインと一致
issuer=C=US, O=Let's Encrypt, CN=E5
notBefore=Aug  9 06:08:04 2026 GMT
notAfter=Nov  6 21:08:04 2026 GMT
```

**異常時の例（今回の症状）**

```
subject=CN=rtc.post-house-system.com    ← 全く別のサイトの証明書が返っている
```

`subject=CN=` がアクセスしたドメインと違う場合、**そのドメインの設定が nginx に読み込まれていません**。
証明書の問題ではなく、設定が反映されていないことを疑ってください。

```bash
# 2つのドメインをまとめて確認する（1つずつ打つ手間を省く書き方）
for h in customer-manager phpmyadmin-customer-manager; do
  printf '%-32s ' "$h"
  openssl s_client -connect 127.0.0.1:443 -servername $h.post-house-system.com </dev/null 2>/dev/null \
    | openssl x509 -noout -subject 2>/dev/null | sed 's/subject=//'
done
```

```bash
# 取得済みの証明書の一覧を見る
ls -1 /etc/letsencrypt/live/
```

ここに目的のドメイン名のディレクトリが**無ければ、証明書はまだ取得できていません**。

### 4.2 DNS を確認する

「そもそもこのサーバに来ているのか」を確認します。DNS が別のサーバを指していたら、
このサーバをいくら直しても直りません。

```bash
# ドメイン名がどのIPアドレスに向いているかを調べる
getent ahostsv4 customer-manager.post-house-system.com | awk '{print $1}' | sort -u
```

```bash
# このサーバ自身のグローバルIPを調べる
curl -s https://ifconfig.me
```

上の2つが**一致していれば OK** です。一致しなければ DNS の設定を見直してください。

### 4.3 nginx の状態を確認する

```bash
# 設定ファイルの文法チェック（実際の動作には影響しない、安全な読み取り専用コマンド）
sudo nginx -t
```

**成功時**

```
nginx: the configuration file /etc/nginx/nginx.conf syntax is ok
nginx: configuration file /etc/nginx/nginx.conf test is successful
```

**今回出ていたエラー**

```
[emerg] "server" directive is not allowed here in /etc/nginx/nginx.conf:632
nginx: configuration file /etc/nginx/nginx.conf test failed
```

> `conflicting server name "..." ignored` という **warn** は、同じドメイン名が2箇所で定義されているという
> 警告です。今回の作業とは無関係な既存の状態なので、無視して構いません。
> **`emerg` だけが致命的**です。

```bash
# 「設定ファイルを最後に編集した時刻」と「nginx が最後にリロードした時刻」を比べる
#
# nginx のワーカープロセスはリロードのたびに作り直されるため、
# ワーカーの起動時刻 ＝ 最後にリロードが成功した時刻 になる。
# 設定ファイルの方が新しければ「編集したのに反映されていない」と分かる。

# 設定ファイルの更新時刻
ls -l --time-style=+'%m/%d %H:%M' /etc/nginx/nginx.conf

# 最後にリロードが成功した時刻
ps -o lstart= --ppid $(pgrep -f 'nginx: master process /usr/sbin/nginx' | head -1) | head -1
```

今回はこうなっていました。

```
設定ファイル : 08/09 04:51   ← 編集した
最終リロード : 08/07 12:03   ← 2日前。編集が反映されていない
```

```bash
# サーバ再起動時に nginx が起動できるかを確認する（重要）
systemctl is-enabled nginx
systemctl cat nginx | grep ExecStartPre
```

**なぜ重要か**: `ExecStartPre=/usr/sbin/nginx -t -q` が設定されていると、
systemd は**起動前に文法チェックを行い、失敗したら nginx を起動しません**。

つまり `nginx -t` が失敗する状態でサーバを再起動すると、**nginx が二度と起動せず、
同居している全サイトが停止します**。文法エラーは「今は動いているから後で」ではなく、
**即座に直すべき緊急事態**です。

```bash
# 設定ファイルの読み込み順を確認する（どれがデフォルト server になるか）
ls -1 /etc/nginx/conf.d/
grep -n "include.*conf\.d" /etc/nginx/nginx.conf
```

nginx は `conf.d/*.conf` を**ファイル名のアルファベット順**に読み込みます。
`default_server` の明示指定が無い場合、**最初に登録された `server` ブロックがデフォルト**になります。

今回 `zz-` で始まるファイル名にしたのは、既存の `rayla-rtc.conf` より**後**に読ませ、
443番のデフォルト server を rtc のまま変えないためです。

```bash
# 稼働中の nginx に触れずに、変更後の設定が通るかを事前検証する
#
#   -c : 検証したい設定ファイルを指定
#   -p : プレフィックス（相対パスの include を解決する基準ディレクトリ）
#        これが無いと fastcgi_params などの相対 include が見つからずエラーになる
nginx -p /etc/nginx/ -t -c /path/to/test-nginx.conf
```

`syntax is ok` が出れば文法は正しいです。非 root で実行すると最後に
`open() "/run/nginx.pid" failed (13: Permission denied)` が出ますが、
これは**権限の問題であって設定の問題ではありません**。`syntax is ok` が出ていれば合格です。

### 4.4 Docker の状態を確認する

```bash
cd /var/customer-manager

# コンテナの状態を一覧表示する
docker compose ps
```

`Status` 列の意味:

| 表示 | 意味 |
| --- | --- |
| `Up X minutes` | 正常稼働中 |
| `Up X minutes (healthy)` | ヘルスチェックにも合格している |
| `Created` | **作られただけで一度も起動していない** |
| `Exited (1)` | 起動したが異常終了した |

```bash
# 停止中も含めた全コンテナを見る（Created や Exited を見落とさないため）
docker compose ps -a
```

```bash
# ホスト側でポートが待ち受け状態になっているか確認する
ss -ltn | grep -E ':8090|:8091'
```

何も表示されなければ、コンテナが起動していないか、ポート公開に失敗しています。

```bash
# .env の値が compose.yaml にどう反映されるかを確認する（実行はしない）
docker compose config
```

`.env` が無い状態で実行すると、次のような警告と空文字が出ます。

```
warning: The "DB_DATABASE" variable is not set. Defaulting to a blank string.
MYSQL_DATABASE: ""
```

この状態で起動すると DB の初期化に失敗します。

---

## 5. 復旧に使ったコマンド

**必ずこの順番で実行してください。** 順序には理由があります。

```
5.1 nginx の文法を直す
      ↓  文法が通らないとリロードできない
5.2 証明書を取得する
      ↓  証明書が無いと 443 の設定を書けない
5.3 コンテナを起動する
      ↓  転送先が無いと 502 になる
5.4 HTTPS を有効化する
```

### 5.1 nginx 設定を修正する

```bash
# ① 壊れている現物を退避する（原因調査用に必ず残す）
#    -a は「タイムスタンプや権限もそのままコピー」の意味
sudo cp -a /etc/nginx/nginx.conf /etc/nginx/nginx.conf.broken.20260809-0451
```

```bash
# ② 何が変わったのかを目で確認する
#    .8.9 は編集前(04:38)のバックアップ。差分が想定どおりかを確認してから戻す
diff -u /etc/nginx/nginx.conf.8.9 /etc/nginx/nginx.conf
```

**他サイトの設定に触れる差分が1行でもあれば、そこで中断して精査してください。**

```bash
# ③ 正常だった状態に戻す
sudo cp -a /etc/nginx/nginx.conf.8.9 /etc/nginx/nginx.conf

# ④ 文法チェック。ここで syntax is ok になるはず
sudo nginx -t
```

このあと customer-manager の設定は `nginx.conf` に直接書かず、
**`/etc/nginx/conf.d/` に独立したファイルとして置きます**。

理由は3つあります。

1. `nginx.conf`（24KB・他9サイト分）を編集せずに済むので、他サイトを壊すリスクが無い
2. 切り戻しが「ファイルを消すだけ」で完結する
3. 証明書取得のために2段階（`:80` だけ → `:443` も）で適用する作業がやりやすい

```bash
# ⑤ 【第1段階】証明書をまだ持っていないので、ssl を含まない :80 のブロックだけを置く
#
#    tee は「標準入力の内容をファイルに書き込む」コマンド。
#    sudo でファイルを作りたいときに使う（sudo echo > file は動かないため）。
#    <<'EOF' ... EOF の間がファイルの中身になる。
#    'EOF' をシングルクォートで囲むと $host などが展開されずそのまま書き込まれる。
sudo tee /etc/nginx/conf.d/zz-customer-manager.conf > /dev/null <<'EOF'
server {
        listen 80;
        listen [::]:80;
        server_name customer-manager.post-house-system.com phpmyadmin-customer-manager.post-house-system.com;

        # Let's Encrypt がドメイン所有を確認するためのパス。
        # ここだけは HTTPS へリダイレクトせず、実ファイルを返す必要がある。
        location ^~ /.well-known/acme-challenge/ {
                root /var/www/html;
        }

        location / {
                return 301 https://$host$request_uri;
        }
}
EOF
```

```bash
# ⑥ 文法チェックが通ったらリロードする
#    && で繋いでいるので、nginx -t が失敗した場合 reload は実行されない
sudo nginx -t && sudo systemctl reload nginx
```

> **`reload` と `restart` の違い（重要）**
>
> - `reload` … 設定を読み直す。**失敗しても古い設定のまま動き続ける**（無停止・安全）
> - `restart` … 一度停止してから起動する。**設定に不備があると起動できず全サイトが落ちる**
>
> 必ず `reload` を使ってください。

### 5.2 証明書を取得する

#### 事前確認（必須）

certbot を実行する前に、**Let's Encrypt がアクセスしてくるパスが正しく通るか**を確認します。

```bash
# ACMEチャレンジ用のパスに、nginx が正しく応答するかテストする
for h in customer-manager phpmyadmin-customer-manager; do
  printf '%-42s ' "$h"
  curl -s -o /dev/null -w '%{http_code} %{content_type}\n' \
    -H "Host: $h.post-house-system.com" \
    http://127.0.0.1/.well-known/acme-challenge/probe
done
```

**期待される出力**

```
customer-manager                           404 text/html
phpmyadmin-customer-manager                404 text/html
```

`404 text/html` が**正解**です。「ファイルは無いが、nginx が `/var/www/html` を見に行った」ことを意味します。

| 出力 | 意味 | 対処 |
| --- | --- | --- |
| `404 text/html` | 正常。certbot に進んでよい | — |
| `301` | HTTPS へリダイレクトされている（設定が未反映） | 5.1 の ⑤⑥ をやり直す |
| `404 text/plain` | 別サイトのバックエンドが応答している | 5.1 の ⑤⑥ をやり直す |

> **なぜ事前確認するのか**
>
> Let's Encrypt には「同じドメインの検証失敗は 1時間に5回まで」という制限があります。
> 上限に達すると**1時間ロックされて作業が止まります**。空振りを避けるため必ず確認してください。

#### 取得方式について

このサーバの既存9サイトは全て **`webroot` 方式**で取得しています。同じ方式に揃えます。

```bash
# 既存サイトがどの方式で取得しているかを確認する
for f in /etc/letsencrypt/renewal/*.conf; do
  printf '%-50s %s\n' "$(basename $f .conf)" "$(grep -E '^authenticator' $f | cut -d= -f2)"
done
```

| 方式 | 仕組み | 使うべきか |
| --- | --- | --- |
| `webroot` | 稼働中の nginx が返すファイルで所有確認する。**無停止** | これを使う |
| `standalone` | certbot が自分で80番を使う。**nginx を止める必要がある** | 使わない（他サイトが落ちる） |
| `--nginx` | certbot が nginx.conf を自動書き換えする | 使わない（24KB の共有設定を触られると危険） |

#### 取得

```bash
# ① まず dry-run（練習実行）。ステージング環境を使うので本番の制限を消費しない
#
#    certonly    : 証明書の取得だけ行う（nginx の設定は書き換えない）
#    --webroot   : 稼働中の nginx 経由で所有確認する方式
#    -w          : 確認用ファイルを置くディレクトリ（webroot）
#    -d          : 取得したいドメイン名
sudo certbot certonly --webroot -w /var/www/html \
  -d customer-manager.post-house-system.com --dry-run

sudo certbot certonly --webroot -w /var/www/html \
  -d phpmyadmin-customer-manager.post-house-system.com --dry-run
```

**`The dry run was successful.` が両方で出ることを確認してから**次に進みます。

```bash
# ② 本番取得（--dry-run を外すだけ）
sudo certbot certonly --webroot -w /var/www/html \
  -d customer-manager.post-house-system.com

sudo certbot certonly --webroot -w /var/www/html \
  -d phpmyadmin-customer-manager.post-house-system.com
```

**成功時の出力**

```
Successfully received certificate.
Certificate is saved at: /etc/letsencrypt/live/customer-manager.post-house-system.com/fullchain.pem
Key is saved at:         /etc/letsencrypt/live/customer-manager.post-house-system.com/privkey.pem
This certificate expires on 2026-11-06.
Certbot has set up a scheduled task to automatically renew this certificate in the background.
```

> ドメインごとに**個別の証明書**を取得しています（1枚にまとめていません）。
> 既存サイト（`rayla` と `phpmyadmin-rayla` など）が個別方式なので、それに合わせています。

```bash
# ③ 自動更新が有効になっているか確認する
systemctl is-active certbot.timer     # → active
sudo certbot certificates             # → 全証明書の期限一覧
```

### 5.3 コンテナを起動する

```bash
# ① 環境変数ファイルを用意する
#    ルート .env は Docker 用、customer-management_app/.env は Laravel 用。役割が違う
cd /var/customer-manager
cp .env.example .env
cp customer-management_app/.env.example customer-management_app/.env
```

`.env` で必ず設定する項目は [README.md](README.md) を参照してください。

> **`.env` の権限に注意**
>
> `chmod 640` にすると、**コンテナ内の PHP-FPM が読めなくなり 500 エラーになります**。
> 詳しくは [6.3 500 Internal Server Error](#63-500-internal-server-error) を参照してください。

```bash
# ② イメージをビルドしてコンテナを起動する
#    -d      : バックグラウンドで起動（ターミナルを占有しない）
#    --build : Dockerfile からイメージを作り直す（初回や Dockerfile 変更時に必要）
docker compose up -d --build
```

初回は PHP の拡張モジュールをコンパイルするため**数分かかります**。

```bash
# ③ 起動状況を確認する
docker compose ps
```

**期待される出力**

```
customer_manager_app          Up 30 seconds
customer_manager_db           Up 1 minute (healthy)
customer_manager_nginx        Up 1 minute      127.0.0.1:8090->80/tcp
customer_manager_phpmyadmin   Up 1 minute      127.0.0.1:8091->80/tcp
```

```bash
# ④ DBのテーブルが作成されたか確認する
#    初回起動時のみ docker/mysql/init/*.sql が自動実行される
docker compose exec -T db sh -c \
  'mysql -u root -p"$MYSQL_ROOT_PASSWORD" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=\"customer_manager\";"'
```

`15` と表示されれば初期化成功です。`0` の場合はボリュームに古いデータが残っています
（[9. 切り戻し手順](#9-切り戻し手順) を参照）。

```bash
# ⑤ PHP の依存パッケージを入れる
#    --no-dev              : 開発用パッケージを除外（本番用）
#    --optimize-autoloader : クラス読み込みを高速化
docker compose exec app composer install --no-dev --optimize-autoloader
```

```bash
# ⑥ アプリが応答するか確認する（リバースプロキシを通さず直接叩く）
curl -s -o /dev/null -w 'HTTP %{http_code}\n' http://127.0.0.1:8090/
```

`302`（ログイン画面へのリダイレクト）が返れば成功です。

### 5.4 HTTPS を有効化する

証明書が取得でき、コンテナが動いてから**最後に**実行します。

```bash
# ① :443 のブロックを含む最終版の設定を配置する
sudo tee /etc/nginx/conf.d/zz-customer-manager.conf > /dev/null <<'EOF'
server {
        listen  443 ssl;
        server_name  customer-manager.post-house-system.com;

        client_max_body_size 2g;

        ssl_certificate     /etc/letsencrypt/live/customer-manager.post-house-system.com/fullchain.pem;
        ssl_certificate_key /etc/letsencrypt/live/customer-manager.post-house-system.com/privkey.pem;
        add_header Strict-Transport-Security "max-age=31536000" always;

        location ^~ /.well-known/acme-challenge/ {
           root /var/www/html;
        }

        location / {
                client_max_body_size 2g;

                # アプリ側に「元のリクエストがどこから来たか」を伝えるヘッダー群。
                # 特に X-Forwarded-Proto が無いと、Laravel が HTTPS だと認識できず
                # フォームの送信先やリダイレクト先が http:// になってしまう。
                proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
                proxy_set_header X-Forwarded-Host $http_host;
                proxy_set_header X-Forwarded-Server $host;
                proxy_set_header X-Forwarded-Proto $scheme;
                proxy_set_header X-Forwarded-Prefix /;
                proxy_set_header Host $host;

                proxy_pass http://localhost:8090/;
        }
}
server {
        listen  443 ssl;
        server_name  phpmyadmin-customer-manager.post-house-system.com;

        client_max_body_size 2g;

        ssl_certificate     /etc/letsencrypt/live/phpmyadmin-customer-manager.post-house-system.com/fullchain.pem;
        ssl_certificate_key /etc/letsencrypt/live/phpmyadmin-customer-manager.post-house-system.com/privkey.pem;
        add_header Strict-Transport-Security "max-age=31536000" always;

        location ^~ /.well-known/acme-challenge/ {
           root /var/www/html;
        }

        # phpMyAdmin 同梱の robots.txt は Disallow: / を返す。
        # それだとクローラーがページを取得しなくなり、phpMyAdmin 自身が付けている
        # X-Robots-Tag: noindex を読めない（＝ URL だけ検索結果に載りうる）。
        # 取得は許可して noindex に判断させる。
        location = /robots.txt {
                default_type text/plain;
                return 200 "User-agent: *\nDisallow:\n";
        }

        location / {
                client_max_body_size 2g;

                proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
                proxy_set_header X-Forwarded-Host $http_host;
                proxy_set_header X-Forwarded-Server $host;
                proxy_set_header X-Forwarded-Proto $scheme;
                proxy_set_header X-Forwarded-Prefix /;
                proxy_set_header Host $host;

                proxy_pass http://127.0.0.1:8091/;
        }
}
server {
        listen 80;
        listen [::]:80;
        server_name customer-manager.post-house-system.com phpmyadmin-customer-manager.post-house-system.com;

        location ^~ /.well-known/acme-challenge/ {
                root /var/www/html;
        }

        location / {
                return 301 https://$host$request_uri;
        }
}
EOF
```

```bash
# ② 文法チェックとリロード
sudo nginx -t && sudo systemctl reload nginx
```

これで公開完了です。[8. 動作確認コマンド](#8-動作確認コマンド) で確認してください。

---

## 6. 途中で起きた障害と対処

### 6.1 ポート競合で DB が起動しない

**症状**

```
Error response from daemon: failed to set up container networking:
Bind for 0.0.0.0:3307 failed: port is already allocated
```

**原因**: 同じサーバの別プロジェクトが既にそのポートを使っていました。

```bash
# ポートを誰が使っているか調べる
ss -ltnp | grep ':3307'

# どのコンテナが使っているか調べる
docker ps -a --format '{{.Names}}\t{{.Ports}}' | grep '3307'
# → museum_3d_view-mysql-1   0.0.0.0:3307->3306/tcp
```

```bash
# 空いているポートを探す
for p in $(seq 3306 3320); do
  ss -ltn | grep -q ":$p " || echo "空き: $p"
done
```

**対処**: ルート `.env` の `DB_PORT_HOST` を空きポート（今回は `3308`）に変更して再起動します。

```bash
# 変更後に反映されているか確認してから起動する
docker compose config | grep -A1 published
docker compose up -d
```

> このポートは**ホストのDBクライアント（TablePlus 等）から接続するためだけ**のものです。
> アプリはコンテナ間ネットワークで `db:3306` に接続するため、番号を変えても動作に影響しません。

### 6.2 502 Bad Gateway

**症状**: nginx は応答するが `502 Bad Gateway` が返る。

**意味**: nginx から転送先（PHP-FPM）に**繋がっていない**ということです。

```bash
# nginx コンテナのエラーログで転送先IPを確認する
docker compose logs --tail=10 nginx
# → connect() failed (111: Connection refused) while connecting to upstream,
#    upstream: "fastcgi://172.31.0.2:9000"     ← nginx が繋ぎに行っているIP
```

```bash
# nginx が認識しているIPと、app の実際のIPを比べる
docker compose exec -T nginx getent hosts app
docker inspect customer_manager_app --format '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}'
```

**原因**: nginx は起動時に転送先のIPを解決してキャッシュします。
app コンテナを作り直すとIPが変わりますが、**nginx は古いIPを掴んだまま**になります。

**対処**

```bash
# nginx コンテナを再起動してIPを解決し直させる
docker compose restart nginx
```

> app や db を `docker compose up -d --build` などで作り直したあとは、
> **nginx も再起動する**のを習慣にしてください。

### 6.3 500 Internal Server Error

**症状**: nginx から PHP には届いているが、Laravel が `500` を返す。

**原因**: `.env` を `chmod 640`（所有者のみ読み取り可）にしたため、
コンテナ内の PHP-FPM 実行ユーザー `www-data`（uid 33）が**`.env` を読めなくなっていました**。

`.env` が読めないと Laravel は全設定が既定値になり、`APP_KEY` が空のまま起動して 500 になります。

```bash
# .env を www-data が読めるか確認する
docker compose exec -T -u www-data app sh -c 'test -r .env && echo "読める" || echo "読めない"'

# Laravel が設定を読めているか確認する
docker compose exec -T -u www-data app php artisan about | grep "Application Name"
```

**判定**: `Application Name` が `Laravel`（フレームワーク既定値）になっていたら、
`.env` が読めていません。正しく読めていれば `Patron Hub` と表示されます。

#### なぜ起きるのか

`compose.yaml` は `customer-management_app/` を**バインドマウント**しています。
バインドマウントは**ホスト側の所有者・権限をそのままコンテナに持ち込む**ため、
`Dockerfile` の `chown -R www-data storage` は実行時に上書きされて無効になります。

#### 対処（採用した方法）

`chmod 644` で読めるようにするのは**危険**です。このサーバは他プロジェクトと同居しており、
DB パスワード・`APP_KEY`・`SEARCH_HASH_KEY` が他ユーザーから読めてしまいます。

代わりに **PHP-FPM をホスト側のファイル所有者と同じ uid で動かします**。
`docker/php/www.conf` に以下を追加しました。

```ini
[www]
; ワーカーをホスト側ファイル所有者と同じ uid 1000 で動かす。
; PHP-FPM は user/group に数値 uid/gid を指定できる。
user = 1000
group = 1000

; ワーカーの標準エラーを FPM のログへ転送する。
; 既定(no)ではワーカーの致命的エラーが docker compose logs に一切出ない。
catch_workers_output = yes
decorate_workers_output = no
```

これで `.env` の読み取りと `storage/` への書き込みが同時に解決します。

```bash
# 設定を反映する
docker compose restart app

# 確認
docker compose exec -T app php artisan about | grep -E "Application Name|Debug Mode"
```

#### エラー内容を調べる方法

`APP_DEBUG=false` のままだと画面には何も出ません。調査時は一時的に有効化します。

```bash
# 一時的にデバッグ表示を有効にする（8090 はループバック限定なので外部露出しない）
cd /var/customer-manager/customer-management_app
sed -i 's/^APP_DEBUG=false$/APP_DEBUG=true/' .env
cd /var/customer-manager && docker compose restart app

curl -s http://127.0.0.1:8090/ | head -50

# 【必ず戻す】調査が終わったら false に戻す
cd /var/customer-manager/customer-management_app
sed -i 's/^APP_DEBUG=true$/APP_DEBUG=false/' .env
cd /var/customer-manager && docker compose restart app
```

> **`APP_DEBUG=true` のまま公開しないでください。** ファイルパス・DB接続情報・
> 環境変数が全てブラウザに表示されます。

---

## 7. 管理者アカウントを作成する

初期管理者は SQL で直接作らず、専用コマンドで作成します（パスワードが Argon2id でハッシュ化されます）。

```bash
cd /var/customer-manager

# 引数は「ログインID」と「表示名」の2つ（対話入力ではありません）
docker compose exec app php artisan app:create-admin admin "システム管理者"
```

**出力**

```
管理者を作成しました。
ログインID    : admin
初期パスワード: (16文字のランダム値)
初期パスワードは今だけ表示されます。初回ログイン時に変更が必要です。
```

> **初期パスワードは一度しか表示されません。** 必ず控えてください。
> 初回ログイン時にパスワード変更が強制されます。

```bash
# 作成されたか確認する
docker compose exec -T db sh -c \
  'mysql -u root -p"$MYSQL_ROOT_PASSWORD" customer_manager -e "SELECT login_id, name, role, is_active FROM users;"'
```

### パスワードを指定して作成する場合

`app:create-admin` はパスワードを常にランダム生成します。値を指定したい場合は次を使います。

```bash
cd /var/customer-manager

# パスワードはシェルの特殊文字を含むことがあるため、環境変数経由で渡す（-e）
docker compose exec -T -e ADMIN_PW='ここにパスワード' app php artisan tinker --execute='
  \App\Models\User::updateOrCreate(
    ["login_id" => "admin"],
    [
      "name"                 => "システム管理者",
      "password"             => getenv("ADMIN_PW"),
      "role"                 => \App\Enums\UserRole::Admin->value,
      "is_active"            => true,
      "must_change_password" => true,
    ]
  );
  echo "作成しました";
'
```

`password` はモデル側で `hashed` にキャストされているため、平文を渡せば自動的に
Argon2id でハッシュ化されます。**平文が保存されることはありません。**

```bash
# 指定したパスワードで照合できるか確認する
docker compose exec -T -e ADMIN_PW='ここにパスワード' app php artisan tinker --execute='
  $u = \App\Models\User::where("login_id","admin")->first();
  echo \Illuminate\Support\Facades\Hash::check(getenv("ADMIN_PW"), $u->password) ? "照合OK" : "照合NG";
'
```

---

## 8. 動作確認コマンド

作業後は必ず以下を全て確認してください。

```bash
# ① ドメインごとに正しい証明書が返るか
for h in customer-manager phpmyadmin-customer-manager; do
  printf '%-32s ' "$h"
  openssl s_client -connect 127.0.0.1:443 -servername $h.post-house-system.com </dev/null 2>/dev/null \
    | openssl x509 -noout -subject | sed 's/subject=//'
done
```

期待: それぞれ `CN=` が自分のドメイン名と一致すること。

```bash
# ② HTTPS で正しく応答するか
#    --resolve でDNSを引かずに指定IPへ接続する（DNS反映待ちでも確認できる）
for h in customer-manager phpmyadmin-customer-manager; do
  printf '%-32s ' "$h"
  curl -sk -o /dev/null -w 'HTTP %{http_code} → %{redirect_url}\n' \
    --resolve $h.post-house-system.com:443:127.0.0.1 https://$h.post-house-system.com/
done
```

期待: 本体は `302 → https://.../login`、phpMyAdmin は `200`。

```bash
# ③ HTTP が HTTPS へリダイレクトされるか
for h in customer-manager phpmyadmin-customer-manager; do
  printf '%-32s ' "$h"
  curl -s -o /dev/null -w 'HTTP %{http_code} → %{redirect_url}\n' \
    -H "Host: $h.post-house-system.com" http://127.0.0.1/
done
```

期待: `301 → https://...`

```bash
# ④ リバースプロキシ経由を再現して、アプリが HTTPS を認識しているか確認する
#    X-Forwarded-Proto: https を付けて、リダイレクト先が https になるかを見る
curl -s -o /dev/null -w 'リダイレクト先: %{redirect_url}\n' \
  -H "Host: customer-manager.post-house-system.com" \
  -H "X-Forwarded-Proto: https" \
  http://127.0.0.1:8090/
```

期待: `https://customer-manager.post-house-system.com/login`

`http://` になる場合は Laravel 側の `trustProxies` 設定を確認してください
（[README.md](README.md) の「アプリ側の対応」節）。

```bash
# ⑤ 同居している他サイトを巻き込んでいないか（共有サーバでは必須）
for h in rtc rayla stock-manager task-manager resume-writing waza \
         phpmyadmin-rayla phpmyadmin-stock phpmyadmin-taskflow; do
  d=$h.post-house-system.com
  cn=$(openssl s_client -connect 127.0.0.1:443 -servername $d </dev/null 2>/dev/null \
       | openssl x509 -noout -subject 2>/dev/null | sed 's/subject=CN=//')
  code=$(curl -sk -o /dev/null -w '%{http_code}' --max-time 8 --resolve $d:443:127.0.0.1 https://$d/)
  printf '  %-45s HTTP %-3s cert=%s\n' "$d" "$code" "$cn"
done
```

期待: 作業前と同じ状態であること。

```bash
# ⑥ 再起動しても nginx が起動できる状態か（最重要）
sudo nginx -t
```

期待: `test is successful`。**これが失敗したまま放置しないでください。**

---

## 9. 切り戻し手順

```bash
# customer-manager の設定だけを取り除く（他サイトには影響しない）
sudo rm -f /etc/nginx/conf.d/zz-customer-manager.conf
sudo nginx -t && sudo systemctl reload nginx
```

```bash
# nginx.conf を編集前に戻す
sudo cp -a /etc/nginx/nginx.conf.broken.20260809-0451 /etc/nginx/nginx.conf
sudo nginx -t && sudo systemctl reload nginx
```

```bash
# コンテナを停止する（データは残る）
cd /var/customer-manager && docker compose down
```

```bash
# 【破壊的】DBを含めて全て削除して作り直す
#    -v はボリューム削除。顧客データが全て消えます。
#    スキーマを作り直したいときだけ使ってください。
cd /var/customer-manager && docker compose down -v
docker compose up -d --build
```

---

## 10. やってはいけないこと

| やってはいけないこと | 何が起きるか | 代わりにすること |
| --- | --- | --- |
| `sudo systemctl restart nginx` | 停止後の起動時チェックで失敗すると**同居する全サイトが停止**する | `sudo nginx -t && sudo systemctl reload nginx` |
| `nginx -t` が失敗したまま放置する | サーバ再起動時に nginx が起動せず**全サイトが停止**する | その場で必ず `syntax is ok` に戻す |
| `certbot --nginx` を使う | 24KB の共有 `nginx.conf` を自動書き換えされ、他サイトが壊れうる | `certbot certonly --webroot` |
| `--dry-run` を省いて certbot を実行 | 失敗を繰り返すと**1時間ロック**され作業が止まる | 必ず `--dry-run` を先に通す |
| `.env` を `chmod 644` にする | 同居する他ユーザーに DB パスワードと暗号鍵が漏れる | `www.conf` の `user = 1000` で対応 |
| `APP_DEBUG=true` のまま公開 | ファイルパス・DB接続情報がブラウザに表示される | 調査後すぐ `false` に戻す |
| コンテナのポートを `0.0.0.0` で公開 | TLS を経由せず直接アクセスされる | `127.0.0.1:` を必ず前置する |
| `docker compose down -v` を安易に実行 | **顧客データが全消去**される | 中身を確認してから。通常は `down` のみ |

---

## 11. よく使う Docker コマンド

全て `/var/customer-manager` で実行します。

```bash
cd /var/customer-manager
```

### 起動・停止

```bash
docker compose up -d              # 起動（バックグラウンド）
docker compose up -d --build      # イメージを作り直して起動
docker compose down               # 停止して削除（データは残る）
docker compose restart            # 全コンテナ再起動
docker compose restart app        # app だけ再起動
```

### 状態確認

```bash
docker compose ps                 # 稼働中のコンテナ一覧
docker compose ps -a              # 停止中も含めた一覧
docker compose config             # .env を反映した最終的な設定を表示
docker compose top                # 各コンテナ内のプロセス一覧
```

### ログ

```bash
docker compose logs               # 全コンテナのログ
docker compose logs app           # app のログだけ
docker compose logs --tail=50 app # 直近50行だけ
docker compose logs -f app        # リアルタイム表示（Ctrl+C で終了）
```

### コンテナ内でコマンドを実行

```bash
# 対話シェルに入る
docker compose exec app bash

# コマンドを1つだけ実行する
docker compose exec app php artisan about

# -T は「疑似端末を割り当てない」オプション。
# スクリプトやパイプで使うときに必要（付けないと出力が乱れることがある）
docker compose exec -T app php artisan about

# 実行ユーザーを指定する（権限問題の切り分けに便利）
docker compose exec -T -u www-data app php artisan about

# 環境変数を渡して実行する（特殊文字を含む値を安全に渡せる）
docker compose exec -T -e MYVAR='値' app php -r 'echo getenv("MYVAR");'
```

### Laravel 関連

```bash
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec app php artisan about              # 設定の確認
docker compose exec app php artisan config:clear       # 設定キャッシュ削除
docker compose exec app php artisan migrate            # マイグレーション
docker compose exec app php artisan app:create-admin admin "システム管理者"
```

### データベース

```bash
# MySQL に入る
docker compose exec db sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" customer_manager'

# SQL を1つだけ実行する
docker compose exec -T db sh -c \
  'mysql -u root -p"$MYSQL_ROOT_PASSWORD" customer_manager -e "SELECT COUNT(*) FROM users;"'

# バックアップを取る
docker compose exec -T db sh -c \
  'mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" customer_manager' > backup_$(date +%Y%m%d).sql
```

> `$MYSQL_ROOT_PASSWORD` はコンテナ内の環境変数です。
> シングルクォートで囲むことで、**ホスト側のシェルではなくコンテナ内で展開**されます。
> パスワードがホストのコマンド履歴に残らないための書き方です。

### 掃除

```bash
docker compose down                  # このプロジェクトのコンテナを削除
docker image prune                   # 未使用イメージを削除
docker system df                     # ディスク使用量を確認
```

> **`docker system prune -a` は使わないでください。** 同居する他プロジェクトの
> イメージまで削除され、再ビルドが必要になります。

---

## 12. 用語集

| 用語 | 意味 |
| --- | --- |
| **リバースプロキシ** | 利用者とアプリの間に立ち、リクエストを転送するサーバ。ここではホストの nginx。TLS 終端とドメイン振り分けを担当する |
| **TLS 終端** | HTTPS の暗号を解く処理。リバースプロキシが行い、そこから先は平文の HTTP で転送する |
| **SNI** | TLS 接続時にクライアントが「どのドメインに繋ぎたいか」を伝える仕組み。1つのIPで複数ドメインの証明書を出し分けられる |
| **デフォルト server** | SNI に一致する `server` ブロックが無いときに使われる既定のブロック。`default_server` 未指定なら最初に登録されたものになる |
| **ACME チャレンジ** | Let's Encrypt がドメイン所有を確認する手順。`/.well-known/acme-challenge/` にファイルを置いて取得しに来る |
| **webroot 方式** | 稼働中の Web サーバのディレクトリに確認用ファイルを置く方式。サーバを止めずに証明書を取得できる |
| **バインドマウント** | ホストのディレクトリをコンテナ内に見せる仕組み。**ホスト側の所有者・権限がそのまま持ち込まれる** |
| **PHP-FPM** | PHP を常駐プロセスとして動かす仕組み。nginx から FastCGI で呼び出される |
| **アップストリーム** | nginx から見た転送先。ここでは PHP-FPM（`app:9000`）やコンテナの nginx（`localhost:8090`） |
| **502 Bad Gateway** | nginx が転送先に繋げなかった。転送先が落ちているか、IP が変わっている |
| **500 Internal Server Error** | 転送先には届いたが、アプリ内部でエラーが起きた |
| **`X-Forwarded-Proto`** | リバースプロキシがアプリに「元は HTTPS だった」と伝えるヘッダー。これが無いとアプリが http:// の URL を生成する |

---

## 関連ドキュメント

- [README.md](README.md) — セットアップ手順・構成・障害の記録
- [SECURITY.md](SECURITY.md) — セキュリティ方針
