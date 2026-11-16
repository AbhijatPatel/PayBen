-- ==============================================================================
-- PayBen Super App Upgrade Migration 002
-- Supports: Travel Bookings (Train, Bus, Flight, Hotel), Merchants & Shopping,
-- Food & Groceries, Mobility (Uber & Rapido), and Broadband Bill Payments
-- Target: MySQL 8.0+ / MariaDB 10.4+ / InnoDB
-- ==============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Service Categories Registry
CREATE TABLE IF NOT EXISTS `service_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(50) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(50) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_service_cat_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `service_categories` (`slug`, `name`, `icon`, `description`, `display_order`, `is_active`) VALUES
('recharge', 'Mobile & DTH Recharge', 'Smartphone', 'Instant prepaid top-up & DTH packages', 1, 1),
('utilities', 'Electricity, Water, Gas, Broadband', 'Zap', 'Municipal boards, electricity & high-speed fiber', 2, 1),
('travel', 'Travel & Ticket Booking', 'Plane', 'Train, bus, flight & hotel reservations', 3, 1),
('shopping', 'Online Shopping Platforms', 'ShoppingBag', 'Discover trusted e-commerce platforms', 4, 1),
('food', 'Food & Grocery Delivery', 'Utensils', 'Quick food orders & instant pantry deliveries', 5, 1),
('mobility', 'Cab & Bike Ride Booking', 'Car', 'Uber cabs and Rapido bike taxis', 6, 1);

-- 2. Merchants & Partner Discovery Directory
CREATE TABLE IF NOT EXISTS `merchants` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(50) NOT NULL,
  `category_slug` VARCHAR(50) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `logo_url` VARCHAR(255) DEFAULT NULL,
  `official_url` VARCHAR(255) NOT NULL,
  `integration_type` ENUM('SANDBOX', 'EXTERNAL_REDIRECT', 'OFFICIAL_API') NOT NULL DEFAULT 'EXTERNAL_REDIRECT',
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `display_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `badge_text` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_merchants_slug` (`slug`),
  INDEX `idx_merchants_category` (`category_slug`),
  INDEX `idx_merchants_enabled` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `merchants` (`name`, `slug`, `category_slug`, `description`, `official_url`, `integration_type`, `is_enabled`, `display_order`, `badge_text`) VALUES
('Flipkart', 'flipkart', 'shopping', 'India’s leading online marketplace for electronics, fashion & appliances', 'https://www.flipkart.com', 'EXTERNAL_REDIRECT', 1, 1, 'Mega Deals'),
('Myntra', 'myntra', 'shopping', 'Premier fashion, footwear, beauty & lifestyle destination', 'https://www.myntra.com', 'EXTERNAL_REDIRECT', 1, 2, 'Top Brands'),
('Meesho', 'meesho', 'shopping', 'Lowest prices on everyday home essentials, clothing & accessories', 'https://www.meesho.com', 'EXTERNAL_REDIRECT', 1, 3, 'Zero Commission'),
('Jabong', 'jabong', 'shopping', 'Fashion apparel portal (Legacy service; currently inactive)', 'https://www.jabong.com', 'EXTERNAL_REDIRECT', 0, 4, 'Service Inactive'),
('Swiggy', 'swiggy', 'food', 'Food delivery from favorite restaurants & groceries on Instamart', 'https://www.swiggy.com', 'EXTERNAL_REDIRECT', 1, 1, 'Instant Delivery'),
('Uber', 'uber', 'mobility', 'Global ridesharing leader offering Uber Go, Premier, Auto & Moto', 'https://m.uber.com', 'EXTERNAL_REDIRECT', 1, 1, 'Reliable Rides'),
('Rapido', 'rapido', 'mobility', 'India’s largest bike taxi and auto booking service for quick city commutes', 'https://www.rapido.bike', 'EXTERNAL_REDIRECT', 1, 2, 'Fast & Affordable'),
('IRCTC Rail Connect', 'irctc', 'travel', 'Official Indian Railways train search & authorized reservation redirect', 'https://www.irctc.co.in', 'EXTERNAL_REDIRECT', 1, 1, 'Indian Railways'),
('redBus', 'redbus', 'travel', 'Verified bus tickets for private operators and state transport RTCs', 'https://www.redbus.in', 'EXTERNAL_REDIRECT', 1, 2, '3500+ Operators');

-- 3. Travel & Reservations Ledger
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `booking_reference` VARCHAR(50) NOT NULL,
  `category` ENUM('train', 'bus', 'flight', 'hotel') NOT NULL,
  `provider_name` VARCHAR(100) NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `source_city` VARCHAR(100) NOT NULL,
  `destination_city` VARCHAR(100) NOT NULL,
  `travel_date` DATE NOT NULL,
  `return_date` DATE DEFAULT NULL,
  `passengers_count` INT UNSIGNED NOT NULL DEFAULT 1,
  `class_or_room_type` VARCHAR(50) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `amount_paise` BIGINT NOT NULL,
  `status` ENUM('CONFIRMED', 'PENDING', 'CANCELLED', 'COMPLETED') NOT NULL DEFAULT 'CONFIRMED',
  `is_sandbox` TINYINT(1) NOT NULL DEFAULT 1,
  `pnr_or_ticket_number` VARCHAR(50) NOT NULL,
  `details_json` JSON DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_bookings_reference` (`booking_reference`),
  INDEX `idx_bookings_user_id` (`user_id`),
  INDEX `idx_bookings_category` (`category`),
  INDEX `idx_bookings_status` (`status`),
  INDEX `idx_bookings_date` (`travel_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Booking Passengers
CREATE TABLE IF NOT EXISTS `booking_passengers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `age` INT UNSIGNED NOT NULL,
  `gender` ENUM('MALE', 'FEMALE', 'OTHER') NOT NULL,
  `berth_or_seat_preference` VARCHAR(50) DEFAULT NULL,
  `seat_number` VARCHAR(20) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_bp_booking_id` (`booking_id`),
  CONSTRAINT `fk_bp_booking` FOREIGN KEY (`booking_id`) 
    REFERENCES `bookings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Mobility External Redirect Logs
CREATE TABLE IF NOT EXISTS `mobility_redirect_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `provider` ENUM('uber', 'rapido') NOT NULL,
  `ride_type` VARCHAR(50) NOT NULL,
  `pickup_location` VARCHAR(255) NOT NULL,
  `destination_location` VARCHAR(255) NOT NULL,
  `estimated_fare` DECIMAL(10,2) DEFAULT NULL,
  `official_url` VARCHAR(500) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_mobility_provider` (`provider`),
  INDEX `idx_mobility_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Seed Broadband Providers
INSERT IGNORE INTO `utility_providers` (`name`, `type`, `code`, `state`, `status`) VALUES
('JioFiber Broadband', 'broadband', 'JIO_FIBER', 'National', 'active'),
('Airtel Xstream Fiber', 'broadband', 'AIRTEL_FIBER', 'National', 'active'),
('ACT Fibernet', 'broadband', 'ACT_FIBER', 'National', 'active'),
('BSNL Broadband & FTTH', 'broadband', 'BSNL_BROADBAND', 'National', 'active'),
('Tata Play Fiber', 'broadband', 'TATAPLAY_FIBER', 'National', 'active');

-- Sample Broadband Bills
INSERT IGNORE INTO `sample_bills` (`provider_id`, `consumer_number`, `customer_name`, `bill_amount`, `bill_date`, `due_date`, `status`) 
SELECT id, '1008291038', 'Rahul Sharma', 799.00, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'unpaid'
FROM `utility_providers` WHERE `code` = 'JIO_FIBER' LIMIT 1;

INSERT IGNORE INTO `sample_bills` (`provider_id`, `consumer_number`, `customer_name`, `bill_amount`, `bill_date`, `due_date`, `status`) 
SELECT id, '2004928172', 'Priya Patel', 999.00, DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 12 DAY), 'unpaid'
FROM `utility_providers` WHERE `code` = 'AIRTEL_FIBER' LIMIT 1;

SET FOREIGN_KEY_CHECKS = 1;
