-- ==============================================================================
-- PayBen Fintech Sandbox Upgrade Migration 001
-- Target: MySQL 8.0+ / MariaDB 10.4+
-- Engine: InnoDB, Strict BigInt paise currency representation, Immutable Ledger
-- ==============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Ensure `wallets` has `balance_paise` column
ALTER TABLE `wallets` 
  ADD COLUMN IF NOT EXISTS `balance_paise` BIGINT NOT NULL DEFAULT 0 AFTER `balance`;

-- Update balance_paise from balance if 0
UPDATE `wallets` 
SET `balance_paise` = CAST(ROUND(`balance` * 100) AS SIGNED) 
WHERE `balance_paise` = 0 AND `balance` > 0;

-- 2. Immutable double-entry wallet ledger
CREATE TABLE IF NOT EXISTS `wallet_ledger` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `transaction_id` VARCHAR(50) NOT NULL,
  `wallet_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `entry_type` ENUM('DEBIT', 'CREDIT') NOT NULL,
  `category` VARCHAR(50) NOT NULL COMMENT 'TOPUP, TRANSFER_SENT, TRANSFER_RECEIVED, RECHARGE, BILL_PAY, CASHBACK, REFUND',
  `amount_paise` BIGINT NOT NULL,
  `balance_after_paise` BIGINT NOT NULL,
  `counterparty_id` INT UNSIGNED DEFAULT NULL,
  `counterparty_vpa` VARCHAR(100) DEFAULT NULL,
  `idempotency_key` VARCHAR(100) DEFAULT NULL,
  `description` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ledger_idempotency` (`idempotency_key`),
  INDEX `idx_ledger_tx_id` (`transaction_id`),
  INDEX `idx_ledger_wallet_user` (`wallet_id`, `user_id`),
  INDEX `idx_ledger_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Sandbox payment orders (simulated checkout / optional test mode)
CREATE TABLE IF NOT EXISTS `payment_orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` VARCHAR(50) NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `amount_paise` BIGINT NOT NULL,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
  `provider` VARCHAR(50) NOT NULL DEFAULT 'mock',
  `status` ENUM('PENDING', 'SUCCESS', 'FAILED', 'CANCELLED') NOT NULL DEFAULT 'PENDING',
  `payment_id` VARCHAR(100) DEFAULT NULL,
  `signature` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_payment_order_id` (`order_id`),
  INDEX `idx_payment_orders_user` (`user_id`),
  INDEX `idx_payment_orders_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. OTP Challenges (free local hashed OTP with cooldown & attempts)
CREATE TABLE IF NOT EXISTS `otp_challenges` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `identifier` VARCHAR(100) NOT NULL COMMENT 'email or mobile',
  `purpose` VARCHAR(50) NOT NULL COMMENT 'LOGIN, REGISTER, TRANSFER, BANK_LINK',
  `otp_hash` VARCHAR(255) NOT NULL,
  `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `max_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `status` ENUM('PENDING', 'VERIFIED', 'EXPIRED', 'FAILED') NOT NULL DEFAULT 'PENDING',
  `resend_after` TIMESTAMP NULL DEFAULT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_otp_identifier_purpose` (`identifier`, `purpose`),
  INDEX `idx_otp_status_expires` (`status`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Rule-based Fraud Detection alerts
CREATE TABLE IF NOT EXISTS `fraud_alerts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `transaction_id` VARCHAR(50) DEFAULT NULL,
  `risk_score` INT UNSIGNED NOT NULL DEFAULT 0,
  `risk_level` ENUM('LOW', 'MEDIUM', 'HIGH') NOT NULL DEFAULT 'LOW',
  `triggered_rules` TEXT NOT NULL,
  `status` ENUM('PENDING', 'REVIEWED', 'DISMISSED') NOT NULL DEFAULT 'PENDING',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_fraud_user_id` (`user_id`),
  INDEX `idx_fraud_risk_level` (`risk_level`),
  INDEX `idx_fraud_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. In-App Notifications
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `type` VARCHAR(50) NOT NULL DEFAULT 'info',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `metadata` JSON DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_notifications_user_read` (`user_id`, `is_read`),
  INDEX `idx_notifications_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Rewards and scratch card claims
CREATE TABLE IF NOT EXISTS `reward_claims` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `reward_code` VARCHAR(50) NOT NULL,
  `cashback_paise` BIGINT NOT NULL,
  `status` ENUM('CLAIMED') NOT NULL DEFAULT 'CLAIMED',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reward_claims_code` (`reward_code`),
  INDEX `idx_reward_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
