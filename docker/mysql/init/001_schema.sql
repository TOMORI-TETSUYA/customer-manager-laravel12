-- =====================================================================
-- Patron Hub 顧客管理システム 初期スキーマ
-- docker/mysql/init/001_schema.sql
--
-- 方針(仕様書 §2.3 / §22 / §36):
--   - InnoDB / utf8mb4
--   - 主キーは BIGINT UNSIGNED AUTO_INCREMENT
--   - 外部キー制約を設定する
--   - 金額は DECIMAL
--   - 検索対象カラムへインデックスを設定する
--   - 初期管理者の平文パスワードは記載しない
--     (管理者は `php artisan app:create-admin` で作成する)
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '+09:00';

CREATE DATABASE IF NOT EXISTS `customer_manager`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_0900_ai_ci;

USE `customer_manager`;

-- ---------------------------------------------------------------------
-- ユーザー
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
    `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `login_id`             VARCHAR(50)  NOT NULL,
    `name`                 VARCHAR(100) NOT NULL,
    `password`             VARCHAR(255) NOT NULL,
    `role`                 VARCHAR(20)  NOT NULL DEFAULT 'staff'
        COMMENT 'admin / staff / viewer',
    `is_active`            TINYINT(1)   NOT NULL DEFAULT 1,
    `must_change_password` TINYINT(1)   NOT NULL DEFAULT 1
        COMMENT '初回ログイン時にパスワード変更を強制する',
    `last_login_at`        DATETIME     NULL,
    `remember_token`       VARCHAR(100) NULL,
    `created_at`           DATETIME     NULL,
    `updated_at`           DATETIME     NULL,
    `deleted_at`           DATETIME     NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users_login_id` (`login_id`),
    KEY `idx_users_role` (`role`),
    KEY `idx_users_is_active` (`is_active`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- 顧客
--   電話番号・メールアドレスは暗号化保存し、
--   完全一致検索用の HMAC-SHA256 ハッシュを別カラムへ保存する (§18.2)
-- ---------------------------------------------------------------------
CREATE TABLE `customers` (
    `id`                       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_code`            VARCHAR(20)  NOT NULL COMMENT '自動発行 顧客ID (例: PH-000001)',
    `customer_type`            VARCHAR(20)  NOT NULL COMMENT 'individual / corporate',
    `customer_name`            VARCHAR(100) NULL,
    `customer_name_kana`       VARCHAR(100) NULL,
    `company_name`             VARCHAR(150) NULL,
    `company_name_kana`        VARCHAR(150) NULL,
    `corporate_contact_name`   VARCHAR(100) NULL,
    `phone_encrypted`          LONGTEXT     NOT NULL COMMENT '表示用暗号化データ',
    `phone_hash`               CHAR(64)     NOT NULL COMMENT '完全一致検索用 HMAC-SHA256',
    `phone_last4`              CHAR(4)      NOT NULL COMMENT 'マスク表示用下4桁',
    `email_encrypted`          LONGTEXT     NULL,
    `email_hash`               CHAR(64)     NULL,
    `postal_code`              VARCHAR(8)   NULL,
    `prefecture`               VARCHAR(10)  NULL,
    `city`                     VARCHAR(50)  NULL,
    `address_encrypted`        LONGTEXT     NULL,
    `building_encrypted`       LONGTEXT     NULL,
    `preferred_contact_method` VARCHAR(20)  NULL COMMENT 'phone / email / line / mail',
    `status`                   VARCHAR(20)  NOT NULL DEFAULT 'prospect'
        COMMENT 'prospect / active / dormant / closed',
    `assigned_user_id`         BIGINT UNSIGNED NOT NULL,
    `source`                   VARCHAR(50)  NULL,
    `notes_encrypted`          LONGTEXT     NULL,
    `last_contacted_at`        DATETIME     NULL COMMENT '最終対応日(対応履歴登録時に更新)',
    `next_action_at`           DATETIME     NULL COMMENT '次回対応日',
    `created_by`               BIGINT UNSIGNED NULL,
    `updated_by`               BIGINT UNSIGNED NULL,
    `created_at`               DATETIME     NULL,
    `updated_at`               DATETIME     NULL,
    `deleted_at`               DATETIME     NULL COMMENT '論理削除',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_customers_code` (`customer_code`),
    KEY `idx_customers_type` (`customer_type`),
    KEY `idx_customers_status` (`status`),
    KEY `idx_customers_name` (`customer_name`),
    KEY `idx_customers_name_kana` (`customer_name_kana`),
    KEY `idx_customers_company` (`company_name`),
    KEY `idx_customers_company_kana` (`company_name_kana`),
    KEY `idx_customers_phone_hash` (`phone_hash`),
    KEY `idx_customers_email_hash` (`email_hash`),
    KEY `idx_customers_assigned` (`assigned_user_id`),
    KEY `idx_customers_created_at` (`created_at`),
    KEY `idx_customers_last_contacted` (`last_contacted_at`),
    KEY `idx_customers_next_action` (`next_action_at`),
    KEY `idx_customers_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_customers_assigned_user`
        FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`),
    CONSTRAINT `fk_customers_created_by`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
    CONSTRAINT `fk_customers_updated_by`
        FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- 対応履歴
-- ---------------------------------------------------------------------
CREATE TABLE `customer_contacts` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id`        BIGINT UNSIGNED NOT NULL,
    `contacted_at`       DATETIME    NOT NULL,
    `contact_method`     VARCHAR(20) NOT NULL COMMENT 'phone / email / visit / line / other',
    `subject`            VARCHAR(200) NOT NULL,
    `response_encrypted` LONGTEXT    NULL COMMENT '対応内容(暗号化)',
    `status`             VARCHAR(20) NOT NULL DEFAULT 'done'
        COMMENT 'done / pending / follow_up',
    `next_action_at`     DATETIME    NULL,
    `created_by`         BIGINT UNSIGNED NULL,
    `created_at`         DATETIME    NULL,
    `updated_at`         DATETIME    NULL,
    PRIMARY KEY (`id`),
    KEY `idx_contacts_customer` (`customer_id`),
    KEY `idx_contacts_contacted_at` (`contacted_at`),
    KEY `idx_contacts_next_action` (`next_action_at`),
    CONSTRAINT `fk_contacts_customer`
        FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
    CONSTRAINT `fk_contacts_created_by`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- 契約
-- ---------------------------------------------------------------------
CREATE TABLE `contracts` (
    `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id`          BIGINT UNSIGNED NOT NULL,
    `contract_number`      VARCHAR(30)   NOT NULL,
    `service_name`         VARCHAR(150)  NOT NULL,
    `contract_date`        DATE          NOT NULL,
    `amount`               DECIMAL(12, 0) NOT NULL COMMENT '税込契約金額(円)',
    `status`               VARCHAR(20)   NOT NULL DEFAULT 'active'
        COMMENT 'active / completed / cancelled',
    `end_reason_encrypted` LONGTEXT      NULL COMMENT '契約終了理由(暗号化)',
    `created_by`           BIGINT UNSIGNED NULL,
    `created_at`           DATETIME      NULL,
    `updated_at`           DATETIME      NULL,
    `deleted_at`           DATETIME      NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_contracts_number` (`contract_number`),
    KEY `idx_contracts_customer` (`customer_id`),
    KEY `idx_contracts_status` (`status`),
    KEY `idx_contracts_date` (`contract_date`),
    CONSTRAINT `fk_contracts_customer`
        FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
    CONSTRAINT `fk_contracts_created_by`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- 請求
-- ---------------------------------------------------------------------
CREATE TABLE `invoices` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id`     BIGINT UNSIGNED NOT NULL,
    `contract_id`     BIGINT UNSIGNED NULL,
    `invoice_number`  VARCHAR(30)   NOT NULL,
    `issue_date`      DATE          NOT NULL,
    `due_date`        DATE          NOT NULL,
    `amount`          DECIMAL(12, 0) NOT NULL COMMENT '請求金額(円)',
    `status`          VARCHAR(20)   NOT NULL DEFAULT 'unpaid'
        COMMENT 'unpaid / partial / paid / void',
    `notes_encrypted` LONGTEXT      NULL COMMENT '請求備考(暗号化)',
    `created_by`      BIGINT UNSIGNED NULL,
    `created_at`      DATETIME      NULL,
    `updated_at`      DATETIME      NULL,
    `deleted_at`      DATETIME      NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_invoices_number` (`invoice_number`),
    KEY `idx_invoices_customer` (`customer_id`),
    KEY `idx_invoices_contract` (`contract_id`),
    KEY `idx_invoices_status` (`status`),
    KEY `idx_invoices_due_date` (`due_date`),
    CONSTRAINT `fk_invoices_customer`
        FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
    CONSTRAINT `fk_invoices_contract`
        FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`),
    CONSTRAINT `fk_invoices_created_by`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- 入金(分割入金対応: 1請求に複数入金)
-- ---------------------------------------------------------------------
CREATE TABLE `payments` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id`      BIGINT UNSIGNED NOT NULL,
    `paid_at`         DATE          NOT NULL,
    `amount`          DECIMAL(12, 0) NOT NULL COMMENT '入金額(円)',
    `payment_method`  VARCHAR(20)   NOT NULL COMMENT 'bank / cash / card / other',
    `notes_encrypted` LONGTEXT      NULL COMMENT '入金備考(暗号化)',
    `created_by`      BIGINT UNSIGNED NULL,
    `created_at`      DATETIME      NULL,
    `updated_at`      DATETIME      NULL,
    PRIMARY KEY (`id`),
    KEY `idx_payments_invoice` (`invoice_id`),
    KEY `idx_payments_paid_at` (`paid_at`),
    CONSTRAINT `fk_payments_invoice`
        FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
    CONSTRAINT `fk_payments_created_by`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- タグ
-- ---------------------------------------------------------------------
CREATE TABLE `tags` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(50) NOT NULL,
    `created_at` DATETIME    NULL,
    `updated_at` DATETIME    NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_tags_name` (`name`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `customer_tag` (
    `customer_id` BIGINT UNSIGNED NOT NULL,
    `tag_id`      BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`customer_id`, `tag_id`),
    KEY `idx_customer_tag_tag` (`tag_id`),
    CONSTRAINT `fk_customer_tag_customer`
        FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_customer_tag_tag`
        FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`)
        ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- 操作履歴
--   パスワード・セッションID・暗号化前個人情報は保存しない (§23.3)
-- ---------------------------------------------------------------------
CREATE TABLE `audit_logs` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`        BIGINT UNSIGNED NULL COMMENT 'ログイン失敗時はNULLの場合あり',
    `action`         VARCHAR(50) NOT NULL
        COMMENT 'login_success / login_failed / customer_create など',
    `target_type`    VARCHAR(50) NULL,
    `target_id`      BIGINT UNSIGNED NULL,
    `changed_fields` JSON        NULL COMMENT '変更カラム名のみ。値は保存しない',
    `ip_address`     VARCHAR(45) NULL,
    `created_at`     DATETIME    NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_audit_user` (`user_id`),
    KEY `idx_audit_action` (`action`),
    KEY `idx_audit_target` (`target_type`, `target_id`),
    KEY `idx_audit_created_at` (`created_at`),
    CONSTRAINT `fk_audit_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

-- =====================================================================
-- Laravel 基盤テーブル
-- (SESSION_DRIVER / CACHE_STORE / QUEUE_CONNECTION = database のため必須)
-- =====================================================================

CREATE TABLE `sessions` (
    `id`            VARCHAR(255) NOT NULL,
    `user_id`       BIGINT UNSIGNED NULL,
    `ip_address`    VARCHAR(45)  NULL,
    `user_agent`    TEXT         NULL,
    `payload`       LONGTEXT     NOT NULL,
    `last_activity` INT          NOT NULL,
    PRIMARY KEY (`id`),
    KEY `sessions_user_id_index` (`user_id`),
    KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `cache` (
    `key`        VARCHAR(255) NOT NULL,
    `value`      MEDIUMTEXT   NOT NULL,
    `expiration` INT          NOT NULL,
    PRIMARY KEY (`key`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `cache_locks` (
    `key`        VARCHAR(255) NOT NULL,
    `owner`      VARCHAR(255) NOT NULL,
    `expiration` INT          NOT NULL,
    PRIMARY KEY (`key`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `jobs` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `queue`        VARCHAR(255)     NOT NULL,
    `payload`      LONGTEXT         NOT NULL,
    `attempts`     TINYINT UNSIGNED NOT NULL,
    `reserved_at`  INT UNSIGNED     NULL,
    `available_at` INT UNSIGNED     NOT NULL,
    `created_at`   INT UNSIGNED     NOT NULL,
    PRIMARY KEY (`id`),
    KEY `jobs_queue_index` (`queue`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `job_batches` (
    `id`             VARCHAR(255) NOT NULL,
    `name`           VARCHAR(255) NOT NULL,
    `total_jobs`     INT          NOT NULL,
    `pending_jobs`   INT          NOT NULL,
    `failed_jobs`    INT          NOT NULL,
    `failed_job_ids` LONGTEXT     NOT NULL,
    `options`        MEDIUMTEXT   NULL,
    `cancelled_at`   INT          NULL,
    `created_at`     INT          NOT NULL,
    `finished_at`    INT          NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

CREATE TABLE `failed_jobs` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid`       VARCHAR(255) NOT NULL,
    `connection` TEXT         NOT NULL,
    `queue`      TEXT         NOT NULL,
    `payload`    LONGTEXT     NOT NULL,
    `exception`  LONGTEXT     NOT NULL,
    `failed_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- 初期タグ(個人情報を含まない参考データのみ)
-- ---------------------------------------------------------------------
INSERT INTO `tags` (`name`, `created_at`, `updated_at`) VALUES
    ('VIP',       NOW(), NOW()),
    ('要フォロー', NOW(), NOW()),
    ('紹介',      NOW(), NOW());
