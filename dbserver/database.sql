-- ============================================================================
-- BengalBet Casino & Gaming Platform - Complete Database Schema (MySQL)
-- Server: https://deserver.neexthub.site/dbserver/
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. USERS TABLE
CREATE TABLE IF NOT EXISTS `users` (
  `id` VARCHAR(64) NOT NULL,
  `username` VARCHAR(128) NOT NULL,
  `phone` VARCHAR(32) NOT NULL,
  `email` VARCHAR(128) DEFAULT NULL,
  `password_hash` VARCHAR(255) DEFAULT NULL,
  `transaction_password` VARCHAR(255) DEFAULT NULL,
  `balance` DECIMAL(12,2) NOT NULL DEFAULT 5420.50,
  `vip_level` INT NOT NULL DEFAULT 2,
  `vip_exp` INT NOT NULL DEFAULT 8450,
  `vip_max_exp` INT NOT NULL DEFAULT 15000,
  `free_spins` INT NOT NULL DEFAULT 3,
  `red_packets_available` INT NOT NULL DEFAULT 2,
  `golden_eggs_available` INT NOT NULL DEFAULT 2,
  `unread_notifications` INT NOT NULL DEFAULT 3,
  `referral_code` VARCHAR(32) NOT NULL DEFAULT 'BDT888',
  `total_earnings` DECIMAL(12,2) NOT NULL DEFAULT 3250.00,
  `referred_friends_count` INT NOT NULL DEFAULT 6,
  `avatar` TEXT DEFAULT NULL,
  `real_name` VARCHAR(128) DEFAULT NULL,
  `birthday` VARCHAR(32) DEFAULT NULL,
  `status` ENUM('active','frozen','banned') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_phone` (`phone`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TRANSACTIONS TABLE
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` VARCHAR(64) NOT NULL,
  `user_id` VARCHAR(64) NOT NULL,
  `username` VARCHAR(128) DEFAULT NULL,
  `user_phone` VARCHAR(32) DEFAULT NULL,
  `type` VARCHAR(32) NOT NULL, -- deposit, withdraw, bonus, rebate, bet_payout, adjustment
  `amount` DECIMAL(12,2) NOT NULL,
  `bonus` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `fee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `channel` VARCHAR(32) NOT NULL, -- bkash, nagad, rocket, usdt, bank, system
  `gateway` VARCHAR(64) DEFAULT NULL,
  `account_number` VARCHAR(64) DEFAULT NULL,
  `trx_id` VARCHAR(128) NOT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'pending', -- success, pending, rejected, processing, flagged
  `admin_note` TEXT DEFAULT NULL,
  `rejection_reason` TEXT DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `processed_by` VARCHAR(64) DEFAULT NULL,
  `processed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_trx_id` (`trx_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. WALLETS / PAYMENT CARDS TABLE
CREATE TABLE IF NOT EXISTS `wallets` (
  `id` VARCHAR(64) NOT NULL,
  `user_id` VARCHAR(64) NOT NULL,
  `type` VARCHAR(32) NOT NULL, -- BKash, Nagad, Rocket, USDT
  `account_name` VARCHAR(128) NOT NULL,
  `account_number` VARCHAR(64) NOT NULL,
  `full_number` VARCHAR(64) NOT NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. BETS RECORDS TABLE
CREATE TABLE IF NOT EXISTS `bets` (
  `id` VARCHAR(64) NOT NULL,
  `user_id` VARCHAR(64) NOT NULL,
  `order_no` VARCHAR(64) NOT NULL,
  `category` VARCHAR(32) NOT NULL, -- slot, live, fishing, poker, sports, crash
  `vendor` VARCHAR(64) NOT NULL,
  `game_name` VARCHAR(128) NOT NULL,
  `game_round` VARCHAR(64) NOT NULL,
  `bet_amount` DECIMAL(12,2) NOT NULL,
  `valid_bet` DECIMAL(12,2) NOT NULL,
  `reward` DECIMAL(12,2) NOT NULL,
  `profit` DECIMAL(12,2) NOT NULL,
  `time` DATETIME NOT NULL,
  `date` DATE NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_category` (`category`),
  KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. MESSAGES & NOTIFICATIONS TABLE
CREATE TABLE IF NOT EXISTS `messages` (
  `id` VARCHAR(64) NOT NULL,
  `user_id` VARCHAR(64) NOT NULL DEFAULT 'all',
  `sender` VARCHAR(64) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `display_time` VARCHAR(64) NOT NULL,
  `date_full` VARCHAR(64) NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `category` VARCHAR(32) NOT NULL DEFAULT 'notice',
  `claimable_bonus` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `content_json` LONGTEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. MESSAGE BONUS CLAIMS
CREATE TABLE IF NOT EXISTS `message_claims` (
  `id` VARCHAR(64) NOT NULL,
  `message_id` VARCHAR(64) NOT NULL,
  `user_id` VARCHAR(64) NOT NULL,
  `claimed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_msg_user` (`message_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. FEEDBACKS & SUPPORT TICKETS TABLE
CREATE TABLE IF NOT EXISTS `feedbacks` (
  `id` VARCHAR(64) NOT NULL,
  `ticket_number` VARCHAR(64) DEFAULT NULL,
  `user_id` VARCHAR(64) NOT NULL,
  `username` VARCHAR(128) DEFAULT NULL,
  `type` VARCHAR(64) NOT NULL,
  `subject` VARCHAR(255) DEFAULT NULL,
  `content` TEXT NOT NULL,
  `contact` VARCHAR(128) DEFAULT NULL,
  `priority` VARCHAR(32) NOT NULL DEFAULT 'normal',
  `status` VARCHAR(32) NOT NULL DEFAULT 'pending', -- pending, in_progress, replied, resolved, closed
  `reply` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. FEEDBACK MESSAGES (THREAD) TABLE
CREATE TABLE IF NOT EXISTS `feedback_messages` (
  `id` VARCHAR(64) NOT NULL,
  `feedback_id` VARCHAR(64) NOT NULL,
  `sender` VARCHAR(32) NOT NULL, -- user, admin
  `sender_name` VARCHAR(128) DEFAULT NULL,
  `text` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_feedback_id` (`feedback_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. LIVE CHAT SESSIONS TABLE
CREATE TABLE IF NOT EXISTS `chat_sessions` (
  `id` VARCHAR(64) NOT NULL,
  `user_id` VARCHAR(64) NOT NULL,
  `username` VARCHAR(128) NOT NULL,
  `user_vip` INT NOT NULL DEFAULT 1,
  `user_balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `user_phone` VARCHAR(32) DEFAULT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'active', -- active, waiting, resolved, closed
  `department` VARCHAR(32) NOT NULL DEFAULT 'general',
  `assigned_agent_name` VARCHAR(128) DEFAULT NULL,
  `assigned_agent_role` VARCHAR(64) DEFAULT NULL,
  `assigned_agent_avatar` TEXT DEFAULT NULL,
  `unread_user_count` INT NOT NULL DEFAULT 0,
  `unread_admin_count` INT NOT NULL DEFAULT 0,
  `last_message` TEXT DEFAULT NULL,
  `last_message_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `rating` INT DEFAULT NULL,
  `rating_comment` TEXT DEFAULT NULL,
  `internal_note` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. LIVE CHAT MESSAGES TABLE
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` VARCHAR(64) NOT NULL,
  `session_id` VARCHAR(64) NOT NULL,
  `sender` VARCHAR(32) NOT NULL, -- user, agent, bot, system
  `sender_name` VARCHAR(128) NOT NULL,
  `sender_avatar` TEXT DEFAULT NULL,
  `text` TEXT NOT NULL,
  `attachment_url` TEXT DEFAULT NULL,
  `attachment_type` VARCHAR(32) DEFAULT NULL,
  `quick_action_json` TEXT DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. VIP TIERS TABLE
CREATE TABLE IF NOT EXISTS `vip_tiers` (
  `level` INT NOT NULL,
  `name` VARCHAR(64) NOT NULL,
  `badge_type` VARCHAR(32) NOT NULL,
  `target_bet` DECIMAL(14,2) NOT NULL,
  `target_bet_display` VARCHAR(64) DEFAULT NULL,
  `deposit_target` DECIMAL(14,2) NOT NULL,
  `upgrade_bonus` DECIMAL(12,2) NOT NULL,
  `weekly_bonus` DECIMAL(12,2) NOT NULL,
  `monthly_bonus` DECIMAL(12,2) NOT NULL,
  `birthday_bonus` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `slot_rebate` DECIMAL(5,2) NOT NULL DEFAULT 0.20,
  `live_rebate` DECIMAL(5,2) NOT NULL DEFAULT 0.10,
  `description` TEXT DEFAULT NULL,
  `perks_json` TEXT DEFAULT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'active',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. VIP REWARD CLAIMS TABLE
CREATE TABLE IF NOT EXISTS `vip_claims` (
  `id` VARCHAR(64) NOT NULL,
  `user_id` VARCHAR(64) NOT NULL,
  `username` VARCHAR(128) DEFAULT NULL,
  `level` INT NOT NULL,
  `type` VARCHAR(32) NOT NULL, -- upgrade, weekly, monthly, birthday
  `event_name` VARCHAR(128) NOT NULL,
  `bonus` DECIMAL(12,2) NOT NULL,
  `points` INT NOT NULL DEFAULT 0,
  `ticket_name` VARCHAR(128) NOT NULL,
  `date` VARCHAR(32) NOT NULL,
  `timestamp` BIGINT NOT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'claimed',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. REFERRALS TABLE
CREATE TABLE IF NOT EXISTS `referrals` (
  `id` VARCHAR(64) NOT NULL,
  `referrer_id` VARCHAR(64) NOT NULL,
  `referrer_username` VARCHAR(128) NOT NULL,
  `referrer_code` VARCHAR(32) NOT NULL,
  `referred_user_id` VARCHAR(64) NOT NULL,
  `referred_username` VARCHAR(128) NOT NULL,
  `referred_phone` VARCHAR(32) NOT NULL,
  `level` INT NOT NULL DEFAULT 1,
  `instant_bonus_paid` TINYINT(1) NOT NULL DEFAULT 0,
  `instant_bonus_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_deposit` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_bet` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `deposit_commission` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `bet_commission` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_commission_earned` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` VARCHAR(32) NOT NULL DEFAULT 'active',
  `joined_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_referrer_id` (`referrer_id`),
  KEY `idx_referred_user_id` (`referred_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. REFERRAL REWARD CLAIMS TABLE
CREATE TABLE IF NOT EXISTS `referral_claims` (
  `id` VARCHAR(64) NOT NULL,
  `user_id` VARCHAR(64) NOT NULL,
  `tier_id` INT DEFAULT NULL,
  `level` INT DEFAULT NULL,
  `type` VARCHAR(32) NOT NULL DEFAULT 'tier',
  `required_count` INT NOT NULL,
  `bonus_amount` DECIMAL(12,2) NOT NULL,
  `trx_id` VARCHAR(128) NOT NULL,
  `claimed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. REFERRAL SETTINGS
CREATE TABLE IF NOT EXISTS `referral_settings` (
  `id` INT NOT NULL DEFAULT 1,
  `settings_json` LONGTEXT NOT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. DAILY SIGN-IN CONFIG
CREATE TABLE IF NOT EXISTS `daily_signin_config` (
  `id` INT NOT NULL DEFAULT 1,
  `config_json` LONGTEXT NOT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. DAILY SIGN-IN RECORDS
CREATE TABLE IF NOT EXISTS `daily_signin_records` (
  `id` VARCHAR(64) NOT NULL,
  `user_id` VARCHAR(64) NOT NULL,
  `username` VARCHAR(128) NOT NULL,
  `day` INT NOT NULL,
  `bonus` DECIMAL(12,2) NOT NULL,
  `date` DATE NOT NULL,
  `streak` INT NOT NULL,
  `vip_multiplier` DECIMAL(5,2) DEFAULT 1.00,
  `status` VARCHAR(32) NOT NULL DEFAULT 'claimed',
  `note` TEXT DEFAULT NULL,
  `claimed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_date` (`user_id`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. RESCUE TIERS
CREATE TABLE IF NOT EXISTS `rescue_tiers` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `min_loss` DECIMAL(12,2) NOT NULL,
  `percentage` DECIMAL(5,2) NOT NULL,
  `max_bonus` DECIMAL(12,2) NOT NULL,
  `name_bn` VARCHAR(128) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. RESCUE CLAIMS
CREATE TABLE IF NOT EXISTS `rescue_claims` (
  `id` VARCHAR(64) NOT NULL,
  `user_id` VARCHAR(64) NOT NULL,
  `username` VARCHAR(128) NOT NULL,
  `date` DATE NOT NULL,
  `total_bet` DECIMAL(12,2) NOT NULL,
  `total_win` DECIMAL(12,2) NOT NULL,
  `net_loss` DECIMAL(12,2) NOT NULL,
  `percentage` DECIMAL(5,2) NOT NULL,
  `bonus_amount` DECIMAL(12,2) NOT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'claimed',
  `admin_note` TEXT DEFAULT NULL,
  `claimed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. PROMO CODES
CREATE TABLE IF NOT EXISTS `promo_codes` (
  `id` VARCHAR(64) NOT NULL,
  `code` VARCHAR(64) NOT NULL,
  `title` VARCHAR(128) NOT NULL,
  `type` VARCHAR(32) NOT NULL DEFAULT 'fixed_cash',
  `amount` DECIMAL(12,2) NOT NULL,
  `min_vip_level` INT NOT NULL DEFAULT 0,
  `max_usage_count` INT NOT NULL DEFAULT 1000,
  `current_usage_count` INT NOT NULL DEFAULT 0,
  `expires_at` DATETIME DEFAULT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'active',
  `description` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. PROMO REDEMPTIONS
CREATE TABLE IF NOT EXISTS `promo_redemptions` (
  `id` VARCHAR(64) NOT NULL,
  `promo_id` VARCHAR(64) NOT NULL,
  `code` VARCHAR(64) NOT NULL,
  `user_id` VARCHAR(64) NOT NULL,
  `username` VARCHAR(128) NOT NULL,
  `bonus_amount` DECIMAL(12,2) NOT NULL,
  `redeemed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. USER TICKETS
CREATE TABLE IF NOT EXISTS `user_tickets` (
  `id` VARCHAR(64) NOT NULL,
  `user_id` VARCHAR(64) NOT NULL,
  `username` VARCHAR(128) NOT NULL,
  `name` VARCHAR(128) NOT NULL,
  `type` VARCHAR(64) NOT NULL,
  `used_time` DATETIME DEFAULT NULL,
  `product_name` VARCHAR(128) NOT NULL,
  `redeem_code` VARCHAR(64) NOT NULL,
  `amount` VARCHAR(64) NOT NULL,
  `amount_number` DECIMAL(12,2) DEFAULT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'active',
  `target_action` VARCHAR(64) NOT NULL,
  `date_category` VARCHAR(32) NOT NULL DEFAULT 'today',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. MYSTERY REWARD CONFIG
CREATE TABLE IF NOT EXISTS `mystery_reward_config` (
  `id` INT NOT NULL DEFAULT 1,
  `config_json` LONGTEXT NOT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 24. MYSTERY CASH CARDS
CREATE TABLE IF NOT EXISTS `mystery_cash_cards` (
  `id` VARCHAR(64) NOT NULL,
  `user_id` VARCHAR(64) NOT NULL,
  `username` VARCHAR(128) NOT NULL,
  `month_year` VARCHAR(16) NOT NULL,
  `bonus_amount` DECIMAL(12,2) NOT NULL,
  `promo_code_used` VARCHAR(64) DEFAULT NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'unlocked',
  `eligible` TINYINT(1) NOT NULL DEFAULT 1,
  `user_bet_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `turnover_required` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `turnover_completed` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `ticket_code` VARCHAR(64) NOT NULL,
  `note` TEXT DEFAULT NULL,
  `unlocked_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `claimed_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_month` (`user_id`, `month_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 25. SITE SETTINGS
CREATE TABLE IF NOT EXISTS `site_settings` (
  `id` INT NOT NULL DEFAULT 1,
  `bkash_number` VARCHAR(32) NOT NULL DEFAULT '01712345678',
  `nagad_number` VARCHAR(32) NOT NULL DEFAULT '01912345678',
  `rocket_number` VARCHAR(32) NOT NULL DEFAULT '01812345678',
  `usdt_address` VARCHAR(128) NOT NULL DEFAULT 'TQn9Y2khEsLJW1ChVWFMSMeSTow5KAnsP5',
  `min_deposit` DECIMAL(12,2) NOT NULL DEFAULT 200.00,
  `max_deposit` DECIMAL(12,2) NOT NULL DEFAULT 50000.00,
  `min_withdraw` DECIMAL(12,2) NOT NULL DEFAULT 500.00,
  `max_withdraw` DECIMAL(12,2) NOT NULL DEFAULT 100000.00,
  `site_notice_json` LONGTEXT DEFAULT NULL,
  `maintenance_mode` TINYINT(1) NOT NULL DEFAULT 0,
  `game_rtp_json` LONGTEXT DEFAULT NULL,
  `game_engine_json` LONGTEXT DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SEED INITIAL DATA
-- ============================================================================

-- Primary User
INSERT INTO `users` (`id`, `username`, `phone`, `email`, `password_hash`, `balance`, `vip_level`, `vip_exp`, `vip_max_exp`, `free_spins`, `red_packets_available`, `golden_eggs_available`, `unread_notifications`, `referral_code`, `total_earnings`, `referred_friends_count`, `avatar`, `real_name`, `status`)
VALUES 
('usr-889922', 'BDT_King88', '01615249856', 'player88@bengalbet.vip', '$2y$10$wTqK/gZ65c5hN7p4sK1FmOqI3m0.81lH0V1p9q8p9.00192938472', 5420.50, 2, 8450, 15000, 3, 2, 2, 3, 'BDT888', 3250.00, 6, 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=120&auto=format&fit=crop&q=80', 'আব্দুল করিম', 'active')
ON DUPLICATE KEY UPDATE `username`=VALUES(`username`);

-- Initial Wallets
INSERT INTO `wallets` (`id`, `user_id`, `type`, `account_name`, `account_number`, `full_number`, `is_default`) VALUES
('wal-1', 'usr-889922', 'BKash', 'Md Karim', '016****9856', '01615249856', 1),
('wal-2', 'usr-889922', 'Nagad', 'Md Karim', '017****2234', '01711222234', 0)
ON DUPLICATE KEY UPDATE `account_name`=VALUES(`account_name`);

-- VIP Tiers Setup
INSERT INTO `vip_tiers` (`level`, `name`, `badge_type`, `target_bet`, `target_bet_display`, `deposit_target`, `upgrade_bonus`, `weekly_bonus`, `monthly_bonus`, `birthday_bonus`, `slot_rebate`, `live_rebate`, `description`, `status`) VALUES
(0, 'VIP0', 'bronze', 2000.00, '2,000.00', 500.00, 0.00, 0.00, 0.00, 0.00, 0.20, 0.10, 'নতুন সদস্য লেভেল', 'active'),
(1, 'VIP1', 'silver', 20000.00, '20,000.00', 2000.00, 50.00, 20.00, 50.00, 50.00, 0.40, 0.30, 'সিলভার ভিআইপি সদস্য', 'active'),
(2, 'VIP2', 'silver', 100000.00, '100,000.00', 10000.00, 180.00, 60.00, 150.00, 150.00, 0.60, 0.40, 'সিলভার স্টার সদস্য', 'active'),
(3, 'VIP3', 'gold', 500000.00, '500,000.00', 50000.00, 588.00, 188.00, 500.00, 500.00, 0.80, 0.50, 'গোল্ড ভিআইপি সদস্য', 'active'),
(4, 'VIP4', 'gold', 2000000.00, '2,000,000.00', 200000.00, 1888.00, 588.00, 1500.00, 1500.00, 1.00, 0.70, 'গোল্ড মাস্টার সদস্য', 'active'),
(5, 'VIP5', 'platinum', 5000000.00, '5,000,000.00', 500000.00, 5888.00, 1888.00, 5000.00, 5000.00, 1.20, 0.90, 'প্লাটিনাম এলিট সদস্য', 'active'),
(6, 'VIP6', 'platinum', 10000000.00, '10,000,000.00', 1000000.00, 12888.00, 3888.00, 10000.00, 10000.00, 1.50, 1.10, 'প্লাটিনাম কিং সদস্য', 'active'),
(7, 'VIP7', 'diamond', 25000000.00, '25,000,000.00', 2500000.00, 28888.00, 8888.00, 25000.00, 25000.00, 1.80, 1.30, 'ডায়মন্ড প্রিভিলেজ সদস্য', 'active'),
(8, 'VIP8', 'diamond', 50000000.00, '50,000,000.00', 5000000.00, 58888.00, 18888.00, 50000.00, 50000.00, 2.20, 1.60, 'ডায়মন্ড রয়্যাল সদস্য', 'active'),
(9, 'VIP9', 'crown', 100000000.00, '100,000,000.00', 10000000.00, 128888.00, 38888.00, 100000.00, 100000.00, 2.60, 1.80, 'ক্রাউন সুপার ভিআইপি', 'active'),
(10, 'VIP10', 'crown', 200000000.00, '200,000,000.00', 20000000.00, 300000.00, 88888.00, 200000.00, 200000.00, 3.00, 2.00, 'রয়্যাল অ্যাম্বাসেডর কিং', 'active')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Rescue Tiers Setup
INSERT INTO `rescue_tiers` (`id`, `min_loss`, `percentage`, `max_bonus`, `name_bn`, `description`, `status`) VALUES
(1, 500.00, 3.00, 100.00, 'টিয়ার ১ উদ্ধার তহবিল (৩%)', 'ন্যূনতম লস ৳৫০০', 'active'),
(2, 2000.00, 5.00, 300.00, 'টিয়ার ২ উদ্ধার তহবিল (৫%)', 'ন্যূনতম লস ৳২,০০০', 'active'),
(3, 5000.00, 8.00, 888.00, 'টিয়ার ৩ উদ্ধার তহবিল (৮%)', 'ন্যূনতম লস ৳৫,০০০', 'active'),
(4, 10000.00, 10.00, 2000.00, 'টিয়ার ৪ উদ্ধার তহবিল (১০%)', 'ন্যূনতম লস ৳১০,০০০', 'active'),
(5, 25000.00, 12.00, 5000.00, 'টিয়ার ৫ উদ্ধার তহবিল (১২%)', 'ন্যূনতম লস ৳২৫,০০০', 'active'),
(6, 50000.00, 15.00, 15000.00, 'টিয়ার ৬ সুপার উদ্ধার (১৫%)', 'ন্যূনতম লস ৳৫০,০০০', 'active'),
(7, 100000.00, 20.00, 50000.00, 'টিয়ার ৭ ভিআইপি মেগা উদ্ধার (২০%)', 'ন্যূনতম লস ৳১,০০,০০০', 'active')
ON DUPLICATE KEY UPDATE `name_bn`=VALUES(`name_bn`);

-- Promo Codes Setup
INSERT INTO `promo_codes` (`id`, `code`, `title`, `type`, `amount`, `min_vip_level`, `max_usage_count`, `current_usage_count`, `status`, `description`) VALUES
('pc-1', 'BENGALBETVIP', 'অফিসিয়াল ভিআইপি রিডিম বোনাস', 'fixed_cash', 200.00, 0, 1000, 42, 'active', 'BengalBet অফিসিয়াল ভিআইপি কোড'),
('pc-2', 'TELEGRAM100', 'টেলিগ্রাম কমিউনিটি গিফট ভাউচার', 'fixed_cash', 100.00, 0, 2000, 189, 'active', 'টেলিগ্রাম অফিসিয়াল গ্রুপ মেম্বার গিফট'),
('pc-3', 'WELCOME2026', 'নতুন সদস্য ওয়েলকাম প্রোমো', 'fixed_cash', 150.00, 0, 5000, 650, 'active', 'নতুন রেজিস্ট্রেশন স্পেশাল কোড'),
('pc-4', 'LUCKY888', 'লাকি উইকএন্ড রিডিম কোড', 'fixed_cash', 88.00, 0, 888, 312, 'active', 'উইকএন্ড লাকি ড্রপ ভাউচার'),
('pc-5', 'FESTIVAL500', 'মেগা ফেস্টিভাল ক্যাশ ভাউচার', 'fixed_cash', 500.00, 1, 500, 78, 'active', 'ভিআইপি ফেস্টিভাল উৎসব অফার'),
('pc-6', 'TIGERWIN', 'বেঙ্গল টাইগার সুপার ক্যাশ কোড', 'fixed_cash', 250.00, 0, 1500, 104, 'active', 'বেঙ্গল টাইগারের বিজয়ী কোড')
ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);

-- Initial Site Settings
INSERT INTO `site_settings` (`id`, `bkash_number`, `nagad_number`, `rocket_number`, `usdt_address`, `min_deposit`, `max_deposit`, `min_withdraw`, `max_withdraw`, `site_notice_json`, `maintenance_mode`, `game_rtp_json`, `game_engine_json`)
VALUES (
  1,
  '01712345678',
  '01912345678',
  '01812345678',
  'TQn9Y2khEsLJW1ChVWFMSMeSTow5KAnsP5',
  200.00,
  50000.00,
  500.00,
  100000.00,
  '{"enabled":true,"titleBn":"BengalBet মেগা ক্যাসিনো ও বোনাস ঘোষণা","titleEn":"BengalBet Mega Casino & Bonus Announcement","contentBn":"প্রতিটি ডিপোজিটে ৪৭% পর্যন্ত স্বাগতম বোনাস এবং গোল্ডেন এগে সর্বোচ্চ ৳৩,৮৮৮ পর্যন্ত ক্যাশ রিওয়ার্ড!","contentEn":"Up to 47% welcome bonus on every deposit and win up to ৳3,888 cash reward in Golden Egg!","bannerImage":"https://images.unsplash.com/photo-1518609878373-06d740f60d8b?w=800&auto=format&fit=crop&q=80"}',
  0,
  '{"aviator":97.2,"super-ace":96.8,"fortune-tree":96.5,"alibaba-cave":97.0,"crazy-777":98.0,"sweet-bonanza":96.5,"gates-of-olympus":96.5}',
  '{"globalRtp":96.5,"engineMode":"normal","volatility":"medium","maxMultiplierCap":5000,"nearMissRate":25,"dynamicBalancing":true,"categoryRtp":{"crash":97,"slot":96.5,"table":98,"fishing":96,"arcade":96.8},"forceOutcome":"none","telemetry":{"totalWagered":254000,"totalPayout":245110,"totalRounds":1890,"lastResetTime":"2026-09-01T00:00:00Z"}}'
)
ON DUPLICATE KEY UPDATE `bkash_number`=VALUES(`bkash_number`);

SET FOREIGN_KEY_CHECKS = 1;
