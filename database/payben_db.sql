-- ==============================================================================
-- PayBen - Recharge and Bill Payment Platform Database Schema & Seed Data
-- Database Name: payben_db
-- Target: MySQL 8.0+ / MariaDB 10.4+ / phpMyAdmin
-- Engine: InnoDB
-- Charset: utf8mb4
-- Collation: utf8mb4_unicode_ci
-- ==============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ------------------------------------------------------------------------------
-- Database Creation & Selection
-- ------------------------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `payben_db` 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `payben_db`;

-- ------------------------------------------------------------------------------
-- Drop Existing Tables (Child tables first, then parent tables)
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `sample_bills`;
DROP TABLE IF EXISTS `utility_providers`;
DROP TABLE IF EXISTS `recharge_plans`;
DROP TABLE IF EXISTS `operators`;
DROP TABLE IF EXISTS `wallet_transactions`;
DROP TABLE IF EXISTS `wallets`;
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `users`;

-- ==============================================================================
-- 1. Table: users
-- Core customer accounts table for authentication and profile management
-- ==============================================================================
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `mobile` VARCHAR(15) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_email` (`email`),
  UNIQUE KEY `uk_users_mobile` (`mobile`),
  INDEX `idx_users_status` (`status`),
  INDEX `idx_users_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 2. Table: admins
-- Administrative users with role-based platform access
-- ==============================================================================
CREATE TABLE `admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `role` ENUM('super_admin', 'admin') NOT NULL DEFAULT 'admin',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_admins_username` (`username`),
  UNIQUE KEY `uk_admins_email` (`email`),
  INDEX `idx_admins_role` (`role`),
  INDEX `idx_admins_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 3. Table: wallets
-- Customer digital wallet balances (1-to-1 relationship with users)
-- ==============================================================================
CREATE TABLE `wallets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_wallets_user_id` (`user_id`),
  CONSTRAINT `fk_wallets_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 4. Table: wallet_transactions
-- Double-entry audit ledger for every credit and debit movement in wallets
-- ==============================================================================
CREATE TABLE `wallet_transactions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `wallet_id` INT UNSIGNED NOT NULL,
  `type` ENUM('credit', 'debit') NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `reference_id` VARCHAR(50) DEFAULT NULL,
  `balance_after` DECIMAL(12,2) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_wallet_tx_user_id` (`user_id`),
  INDEX `idx_wallet_tx_wallet_id` (`wallet_id`),
  INDEX `idx_wallet_tx_type` (`type`),
  INDEX `idx_wallet_tx_reference_id` (`reference_id`),
  INDEX `idx_wallet_tx_created_at` (`created_at`),
  CONSTRAINT `fk_wallet_tx_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_wallet_tx_wallet` FOREIGN KEY (`wallet_id`) 
    REFERENCES `wallets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 5. Table: operators
-- Telecom and DTH service providers
-- ==============================================================================
CREATE TABLE `operators` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `type` ENUM('mobile', 'dth') NOT NULL,
  `code` VARCHAR(20) NOT NULL,
  `logo` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_operators_code` (`code`),
  INDEX `idx_operators_type` (`type`),
  INDEX `idx_operators_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 6. Table: recharge_plans
-- Tariff plans and packs offered by mobile and DTH operators
-- ==============================================================================
CREATE TABLE `recharge_plans` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `operator_id` INT UNSIGNED NOT NULL,
  `plan_name` VARCHAR(100) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `validity` VARCHAR(50) NOT NULL,
  `data_limit` VARCHAR(50) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `plan_type` ENUM('prepaid', 'postpaid') NOT NULL DEFAULT 'prepaid',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_plans_operator_id` (`operator_id`),
  INDEX `idx_plans_amount` (`amount`),
  INDEX `idx_plans_plan_type` (`plan_type`),
  INDEX `idx_plans_status` (`status`),
  CONSTRAINT `fk_plans_operator` FOREIGN KEY (`operator_id`) 
    REFERENCES `operators` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 7. Table: utility_providers
-- State and national utility boards (Electricity, Water, Piped Gas)
-- ==============================================================================
CREATE TABLE `utility_providers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `type` ENUM('electricity', 'water', 'gas') NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `state` VARCHAR(100) NOT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_utility_code` (`code`),
  INDEX `idx_utility_type` (`type`),
  INDEX `idx_utility_state` (`state`),
  INDEX `idx_utility_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 8. Table: sample_bills
-- Simulated billing ledger for consumer utility bill fetching and settlement
-- ==============================================================================
CREATE TABLE `sample_bills` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_id` INT UNSIGNED NOT NULL,
  `consumer_number` VARCHAR(20) NOT NULL,
  `consumer_name` VARCHAR(100) NOT NULL,
  `bill_amount` DECIMAL(10,2) NOT NULL,
  `due_date` DATE NOT NULL,
  `bill_date` DATE NOT NULL,
  `status` ENUM('unpaid', 'paid') NOT NULL DEFAULT 'unpaid',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_sample_bills_provider` (`provider_id`),
  INDEX `idx_sample_bills_consumer` (`consumer_number`),
  INDEX `idx_sample_bills_status` (`status`),
  INDEX `idx_sample_bills_due_date` (`due_date`),
  CONSTRAINT `fk_bills_provider` FOREIGN KEY (`provider_id`) 
    REFERENCES `utility_providers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 9. Table: transactions
-- Unified payments & services transaction log
-- ==============================================================================
CREATE TABLE `transactions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `transaction_id` VARCHAR(20) NOT NULL,
  `type` ENUM('mobile_recharge', 'dth_recharge', 'electricity', 'water', 'gas', 'wallet_topup') NOT NULL,
  `mobile_or_consumer_number` VARCHAR(50) NOT NULL,
  `operator_or_provider` VARCHAR(100) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `status` ENUM('SUCCESS', 'PENDING', 'FAILED') NOT NULL DEFAULT 'SUCCESS',
  `payment_method` VARCHAR(50) NOT NULL DEFAULT 'Wallet',
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_transactions_tx_id` (`transaction_id`),
  INDEX `idx_transactions_user_id` (`user_id`),
  INDEX `idx_transactions_type` (`type`),
  INDEX `idx_transactions_status` (`status`),
  INDEX `idx_transactions_number` (`mobile_or_consumer_number`),
  INDEX `idx_transactions_created_at` (`created_at`),
  CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ==============================================================================
-- SEED DATA INSERTION
-- ==============================================================================

-- ------------------------------------------------------------------------------
-- 1. Users Seed Data
-- Passwords hashed using PHP's PASSWORD_DEFAULT (bcrypt cost 10)
-- Both user1@test.com & user2@test.com password: "password123"
-- Hash: $2y$10$yXP2s.Cwf4jlYWc9mTM88OGQvnMyZHyAFRa1AX33hT4BqB66rYVGm
-- ------------------------------------------------------------------------------
INSERT INTO `users` (`id`, `full_name`, `email`, `mobile`, `password_hash`, `avatar`, `status`, `created_at`) VALUES
(1, 'Rahul Sharma', 'user1@test.com', '9876543210', '$2y$10$yXP2s.Cwf4jlYWc9mTM88OGQvnMyZHyAFRa1AX33hT4BqB66rYVGm', 'assets/images/avatars/user1.png', 'active', '2026-11-01 10:00:00'),
(2, 'Priya Patel', 'user2@test.com', '9812345678', '$2y$10$yXP2s.Cwf4jlYWc9mTM88OGQvnMyZHyAFRa1AX33hT4BqB66rYVGm', 'assets/images/avatars/user2.png', 'active', '2026-11-02 11:30:00');

-- ------------------------------------------------------------------------------
-- 2. Admins Seed Data
-- Admin credentials: admin@payben.com / admin123
-- Hash: $2y$10$qIRNatg13n2FbfoHp913ROF9N0f2HHQxSugLld5b983mZeru1cVw.
-- ------------------------------------------------------------------------------
INSERT INTO `admins` (`id`, `username`, `email`, `password_hash`, `full_name`, `role`, `status`, `created_at`) VALUES
(1, 'admin', 'admin@payben.com', '$2y$10$qIRNatg13n2FbfoHp913ROF9N0f2HHQxSugLld5b983mZeru1cVw.', 'PayBen System Administrator', 'super_admin', 'active', '2026-11-01 09:00:00');

-- ------------------------------------------------------------------------------
-- 3. Wallets Seed Data
-- Initial starting balance of ₹5000.00 for each user
-- ------------------------------------------------------------------------------
INSERT INTO `wallets` (`id`, `user_id`, `balance`, `created_at`) VALUES
(1, 1, 5000.00, '2026-11-01 10:00:00'),
(2, 2, 5000.00, '2026-11-02 11:30:00');

-- ------------------------------------------------------------------------------
-- 4. Wallet Transactions Seed Data
-- Initial funding records and recent transaction records
-- ------------------------------------------------------------------------------
INSERT INTO `wallet_transactions` (`id`, `user_id`, `wallet_id`, `type`, `amount`, `description`, `reference_id`, `balance_after`, `created_at`) VALUES
(1, 1, 1, 'credit', 5000.00, 'Initial Welcome Balance Credited', 'WTXINIT001', 5000.00, '2026-11-01 10:00:00'),
(2, 2, 2, 'credit', 5000.00, 'Initial Welcome Balance Credited', 'WTXINIT002', 5000.00, '2026-11-02 11:30:00'),
(3, 1, 1, 'debit', 249.00, 'Payment for Jio Prepaid Mobile Recharge', 'TXN20261101001', 4751.00, '2026-11-03 14:15:00'),
(4, 1, 1, 'debit', 499.00, 'Payment for Tata Play DTH Recharge', 'TXN20261102002', 4252.00, '2026-11-04 16:45:00'),
(5, 1, 1, 'credit', 748.00, 'Quick Add Money via UPI', 'WTXADD74801', 5000.00, '2026-11-05 18:00:00'),
(6, 2, 2, 'debit', 980.00, 'Payment for BESCOM Bangalore Electricity Bill', 'TXN20261104004', 4020.00, '2026-11-06 12:20:00'),
(7, 2, 2, 'credit', 980.00, 'Wallet Topup via Net Banking', 'WTXADD98002', 5000.00, '2026-11-07 09:10:00');

-- ------------------------------------------------------------------------------
-- 5. Operators Seed Data
-- 4 Mobile Operators (Jio, Airtel, Vi, BSNL) & 4 DTH Operators (Tata Play, Airtel DTH, Dish TV, Sun Direct)
-- ------------------------------------------------------------------------------
INSERT INTO `operators` (`id`, `name`, `type`, `code`, `logo`, `status`, `created_at`) VALUES
-- Mobile Operators
(1, 'Reliance Jio', 'mobile', 'JIO', 'assets/images/operators/jio.png', 'active', '2026-11-01 00:00:00'),
(2, 'Bharti Airtel', 'mobile', 'AIRTEL', 'assets/images/operators/airtel.png', 'active', '2026-11-01 00:00:00'),
(3, 'Vodafone Idea (Vi)', 'mobile', 'VI', 'assets/images/operators/vi.png', 'active', '2026-11-01 00:00:00'),
(4, 'BSNL Mobile', 'mobile', 'BSNL', 'assets/images/operators/bsnl.png', 'active', '2026-11-01 00:00:00'),
-- DTH Operators
(5, 'Tata Play (Tata Sky)', 'dth', 'TATAPLAY', 'assets/images/operators/tataplay.png', 'active', '2026-11-01 00:00:00'),
(6, 'Airtel Digital TV', 'dth', 'AIRTEL_DTH', 'assets/images/operators/airtel_dth.png', 'active', '2026-11-01 00:00:00'),
(7, 'Dish TV India', 'dth', 'DISHTV', 'assets/images/operators/dishtv.png', 'active', '2026-11-01 00:00:00'),
(8, 'Sun Direct', 'dth', 'SUNDIRECT', 'assets/images/operators/sundirect.png', 'active', '2026-11-01 00:00:00');

-- ------------------------------------------------------------------------------
-- 6. Recharge Plans Seed Data
-- 18 diverse recharge plans spread across mobile & DTH operators (₹149, ₹199, ₹249, ₹299, ₹399, ₹499, ₹599, ₹699, ₹999 etc.)
-- ------------------------------------------------------------------------------
INSERT INTO `recharge_plans` (`id`, `operator_id`, `plan_name`, `amount`, `validity`, `data_limit`, `description`, `plan_type`, `status`, `created_at`) VALUES
-- Jio Mobile Plans (operator_id: 1)
(1, 1, 'Jio Cricket Special', 149.00, '20 Days', '1 GB/Day', 'Unlimited True 5G Voice Calls, 100 SMS/Day, JioCinema & JioTV complimentary access', 'prepaid', 'active', '2026-11-01 00:00:00'),
(2, 1, 'Jio Bestseller Daily', 249.00, '28 Days', '1.5 GB/Day', 'Truly Unlimited 5G Data, Unlimited Local & STD Calls, 100 SMS/Day, JioCloud access', 'prepaid', 'active', '2026-11-01 00:00:00'),
(3, 1, 'Jio Entertainment Plus', 399.00, '28 Days', '3 GB/Day', 'Unlimited Calls + 100 SMS/Day + Free Disney+ Hotstar Mobile 3-Month Subscription', 'prepaid', 'active', '2026-11-01 00:00:00'),
(4, 1, 'Jio 3-Month Saver Pack', 699.00, '84 Days', '2 GB/Day', 'Unlimited Calls to all networks, 100 SMS/Day, High-speed True 5G Unlimited trial', 'prepaid', 'active', '2026-11-01 00:00:00'),

-- Airtel Mobile Plans (operator_id: 2)
(5, 2, 'Airtel Smart Recharge', 199.00, '28 Days', '1 GB/Day', 'Unlimited Voice Calling, 100 SMS/day, Free Hellotunes and Wynk Music access', 'prepaid', 'active', '2026-11-01 00:00:00'),
(6, 2, 'Airtel Truly Unlimited', 299.00, '28 Days', '1.5 GB/Day', 'Unlimited Voice, Unlimited 5G Data, Apollo 24|7 Circle & Free Hellotunes included', 'prepaid', 'active', '2026-11-01 00:00:00'),
(7, 2, 'Airtel Double Data Pack', 499.00, '56 Days', '2 GB/Day', 'Unlimited Voice, 100 SMS/Day, Unlimited 5G Data benefit, Xstream Play access', 'prepaid', 'active', '2026-11-01 00:00:00'),
(8, 2, 'Airtel Premium Entertainment', 999.00, '84 Days', '2.5 GB/Day', 'Unlimited 5G Data + Amazon Prime Membership for 84 Days + Wynk Music Premium', 'prepaid', 'active', '2026-11-01 00:00:00'),

-- Vi Mobile Plans (operator_id: 3)
(9, 3, 'Vi Mini Saver', 199.00, '28 Days', '1 GB/Day', 'Unlimited Voice Calling across India + 100 SMS/Day with Vi Movies & TV Lite', 'prepaid', 'active', '2026-11-01 00:00:00'),
(10, 3, 'Vi Hero Unlimited', 299.00, '28 Days', '1.5 GB/Day', 'Binge All Night (12 AM - 6 AM unlimited data) + Weekend Data Rollover feature', 'prepaid', 'active', '2026-11-01 00:00:00'),
(11, 3, 'Vi Long Validity Bonanza', 599.00, '70 Days', '1.5 GB/Day', 'Unlimited Calling + 100 SMS/Day + Vi Hero Unlimited (Binge All Night & Rollover)', 'prepaid', 'active', '2026-11-01 00:00:00'),

-- BSNL Mobile Plans (operator_id: 4)
(12, 4, 'BSNL Pocket Saver', 149.00, '28 Days', '1 GB/Day', 'Unlimited National Roaming & Local/STD Calling + 100 SMS/Day across India', 'prepaid', 'active', '2026-11-01 00:00:00'),
(13, 4, 'BSNL Power User Pack', 399.00, '70 Days', '2 GB/Day', 'Truly Unlimited Voice Calls, 100 SMS/Day, High Speed 4G/3G Data with FUP', 'prepaid', 'active', '2026-11-01 00:00:00'),

-- Tata Play DTH Plans (operator_id: 5)
(14, 5, 'Tata Play Hindi Super Value', 249.00, '30 Days', 'N/A', '240+ Channels including Star Plus, Sony, Zee TV, Colors, News & Music channels', 'prepaid', 'active', '2026-11-01 00:00:00'),
(15, 5, 'Tata Play Mega HD Pack', 499.00, '30 Days', 'N/A', '320+ Channels with 55+ Crystal Clear HD Channels, Star Sports HD, Sony Sports HD', 'prepaid', 'active', '2026-11-01 00:00:00'),

-- Airtel Digital TV Plans (operator_id: 6)
(16, 6, 'Airtel DTH Dabang HD Pack', 299.00, '30 Days', 'N/A', '215+ Channels with all top Hindi Entertainment, Movies, News and Kids channels', 'prepaid', 'active', '2026-11-01 00:00:00'),

-- Dish TV Plans (operator_id: 7)
(17, 7, 'Dish TV Family HD Pack', 399.00, '30 Days', 'N/A', '280+ SD & HD Channels covering Entertainment, Infotainment, Devotional and Sports', 'prepaid', 'active', '2026-11-01 00:00:00'),

-- Sun Direct Plans (operator_id: 8)
(18, 8, 'Sun Direct South Mega Pack', 199.00, '30 Days', 'N/A', '180+ Channels featuring Sun TV, KTV, Gemini TV, Surya TV and popular regional hits', 'prepaid', 'active', '2026-11-01 00:00:00');

-- ------------------------------------------------------------------------------
-- 7. Utility Providers Seed Data
-- 4 Electricity, 3 Water, 3 Gas Providers across diverse states
-- ------------------------------------------------------------------------------
INSERT INTO `utility_providers` (`id`, `name`, `type`, `code`, `state`, `status`, `created_at`) VALUES
-- Electricity Providers
(1, 'Tata Power Mumbai', 'electricity', 'TATAPOWER_MUM', 'Maharashtra', 'active', '2026-11-01 00:00:00'),
(2, 'BSES Rajdhani Power Limited (BRPL)', 'electricity', 'BSES_DELHI', 'Delhi', 'active', '2026-11-01 00:00:00'),
(3, 'Bangalore Electricity Supply Company (BESCOM)', 'electricity', 'BESCOM_KA', 'Karnataka', 'active', '2026-11-01 00:00:00'),
(4, 'Torrent Power', 'electricity', 'TORRENT_POWER', 'Gujarat', 'active', '2026-11-01 00:00:00'),

-- Water Supply Boards
(5, 'Delhi Jal Board (DJB)', 'water', 'DJB_DELHI', 'Delhi', 'active', '2026-11-01 00:00:00'),
(6, 'Bangalore Water Supply and Sewerage Board (BWSSB)', 'water', 'BWSSB_KA', 'Karnataka', 'active', '2026-11-01 00:00:00'),
(7, 'Municipal Corporation of Greater Mumbai (MCGM Water)', 'water', 'MCGM_MUM', 'Maharashtra', 'active', '2026-11-01 00:00:00'),

-- Piped Natural Gas (PNG) / Gas Providers
(8, 'Indraprastha Gas Limited (IGL PNG)', 'gas', 'IGL_DELHI', 'Delhi', 'active', '2026-11-01 00:00:00'),
(9, 'Mahanagar Gas Limited (MGL PNG)', 'gas', 'MGL_MUM', 'Maharashtra', 'active', '2026-11-01 00:00:00'),
(10, 'Adani Total Gas Limited', 'gas', 'ADANI_GAS', 'Gujarat', 'active', '2026-11-01 00:00:00');

-- ------------------------------------------------------------------------------
-- 8. Sample Bills Seed Data
-- 7 realistic sample bills for testing instant bill fetch & payment flows
-- ------------------------------------------------------------------------------
INSERT INTO `sample_bills` (`id`, `provider_id`, `consumer_number`, `consumer_name`, `bill_amount`, `due_date`, `bill_date`, `status`, `created_at`) VALUES
(1, 1, '1001234567', 'Rahul Sharma', 1850.50, '2026-11-25', '2026-11-05', 'unpaid', '2026-11-05 08:00:00'),
(2, 2, '2008765432', 'Amit Verma', 3420.00, '2026-11-20', '2026-11-01', 'unpaid', '2026-11-01 08:00:00'),
(3, 3, '3004567890', 'Priya Patel', 980.00, '2026-11-18', '2026-11-02', 'paid', '2026-11-02 08:00:00'),
(4, 5, '4001122334', 'Rahul Sharma', 450.00, '2026-11-28', '2026-11-08', 'unpaid', '2026-11-08 09:00:00'),
(5, 6, '5006677889', 'Suresh Raina', 620.50, '2026-11-22', '2026-11-04', 'unpaid', '2026-11-04 10:30:00'),
(6, 8, '6009988771', 'Priya Patel', 1240.00, '2026-11-30', '2026-11-07', 'unpaid', '2026-11-07 11:15:00'),
(7, 9, '7003344552', 'Sunil Joshi', 890.00, '2026-11-15', '2026-11-01', 'paid', '2026-11-01 09:30:00');

-- ------------------------------------------------------------------------------
-- 9. Transactions Seed Data
-- 6 realistic sample transactions demonstrating mobile, DTH, utility, and top-up
-- ------------------------------------------------------------------------------
INSERT INTO `transactions` (`id`, `user_id`, `transaction_id`, `type`, `mobile_or_consumer_number`, `operator_or_provider`, `amount`, `status`, `payment_method`, `description`, `created_at`) VALUES
(1, 1, 'TXN20261101001', 'mobile_recharge', '9876543210', 'Reliance Jio', 249.00, 'SUCCESS', 'Wallet', 'Prepaid Mobile Recharge - 28 Days 1.5GB/Day', '2026-11-03 14:15:00'),
(2, 1, 'TXN20261102002', 'dth_recharge', '1002345678', 'Tata Play (Tata Sky)', 499.00, 'SUCCESS', 'Wallet', 'Monthly Subscription - Mega HD Pack', '2026-11-04 16:45:00'),
(3, 1, 'TXN20261103003', 'wallet_topup', '9876543210', 'PayBen Wallet', 2000.00, 'SUCCESS', 'UPI / Net Banking', 'Instant Wallet Balance Recharge via UPI', '2026-11-05 18:00:00'),
(4, 2, 'TXN20261104004', 'electricity', '3004567890', 'Bangalore Electricity Supply Company (BESCOM)', 980.00, 'SUCCESS', 'Wallet', 'Electricity Bill Settlement for Consumer #3004567890', '2026-11-06 12:20:00'),
(5, 2, 'TXN20261105005', 'gas', '7003344552', 'Mahanagar Gas Limited (MGL PNG)', 890.00, 'SUCCESS', 'Wallet', 'Piped Natural Gas Bill Settlement', '2026-11-07 15:30:00'),
(6, 1, 'TXN20261106006', 'water', '4001122334', 'Delhi Jal Board (DJB)', 450.00, 'PENDING', 'Wallet', 'Water Supply Bill Payment Processing', '2026-11-09 10:15:00');

-- ==============================================================================
-- End of PayBen Database Schema & Seed Script
-- ==============================================================================
