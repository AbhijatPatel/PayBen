-- ==============================================================================
-- Migration: 003_add_google_auth.sql
-- Description: Adds Google OAuth columns and indexes to users table
-- ==============================================================================

ALTER TABLE `users` 
  ADD COLUMN `google_id` VARCHAR(100) NULL AFTER `email`,
  MODIFY COLUMN `mobile` VARCHAR(15) NULL,
  ADD INDEX `idx_users_google_id` (`google_id`);
