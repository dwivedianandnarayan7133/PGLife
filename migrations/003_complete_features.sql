-- Migration 003: Complete Feature Upgrade for PGLife Platform
USE `pglife`;

-- 1. Upgrade `users` table
ALTER TABLE `users`
  ADD COLUMN `role` ENUM('user','owner','admin') NOT NULL DEFAULT 'user',
  ADD COLUMN `profile_image` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN `address` TEXT DEFAULT NULL,
  ADD COLUMN `id_proof_doc` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN `id_verified` ENUM('unverified','pending','verified','rejected') NOT NULL DEFAULT 'unverified',
  ADD COLUMN `notification_prefs` VARCHAR(255) DEFAULT '{"email":true,"push":true,"sms":false}',
  ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- 2. Upgrade `properties` table
ALTER TABLE `properties`
  ADD COLUMN `owner_id` INT(11) DEFAULT NULL,
  ADD COLUMN `status` ENUM('pending','approved','rejected','suspended') NOT NULL DEFAULT 'approved',
  ADD COLUMN `is_published` TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN `is_pet_friendly` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN `near_college` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN `views_count` INT(11) NOT NULL DEFAULT 0,
  ADD COLUMN `admin_notes` TEXT DEFAULT NULL,
  ADD KEY `owner_id` (`owner_id`);

-- 3. Room Management Table
CREATE TABLE IF NOT EXISTS `property_rooms` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `property_id` INT(11) NOT NULL,
  `room_type` ENUM('Single','Double','Triple','Dormitory') NOT NULL,
  `rent` INT(11) NOT NULL,
  `capacity` INT(11) NOT NULL DEFAULT 1,
  `available_rooms` INT(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`),
  CONSTRAINT `fk_rooms_properties` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Gallery Images Table
CREATE TABLE IF NOT EXISTS `property_images` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `property_id` INT(11) NOT NULL,
  `image_url` VARCHAR(255) NOT NULL,
  `is_cover` TINYINT(1) NOT NULL DEFAULT 0,
  `display_order` INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`),
  CONSTRAINT `fk_images_properties` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Bookings Table
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `property_id` INT(11) NOT NULL,
  `room_type` VARCHAR(50) DEFAULT 'Single',
  `status` ENUM('pending','accepted','rejected','completed','cancelled') NOT NULL DEFAULT 'pending',
  `move_in_date` DATE DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `payment_status` ENUM('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `property_id` (`property_id`),
  CONSTRAINT `fk_bookings_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bookings_properties` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Rental Agreement Verification Table
CREATE TABLE IF NOT EXISTS `agreements` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `booking_id` INT(11) NOT NULL,
  `owner_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `agreement_doc` VARCHAR(255) NOT NULL,
  `rules_doc` VARCHAR(255) DEFAULT NULL,
  `terms_doc` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('pending_review','approved','rejected') NOT NULL DEFAULT 'pending_review',
  `rejection_reason` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `owner_id` (`owner_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_agreements_bookings` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Owner Verifications Table
CREATE TABLE IF NOT EXISTS `owner_verifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `owner_id` INT(11) NOT NULL,
  `govt_id_doc` VARCHAR(255) DEFAULT NULL,
  `business_license_doc` VARCHAR(255) DEFAULT NULL,
  `property_doc` VARCHAR(255) DEFAULT NULL,
  `bank_account` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `admin_notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `owner_id` (`owner_id`),
  CONSTRAINT `fk_verifications_users` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Notifications Table
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `link` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_notifications_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Saved / Bookmarked Properties Table
CREATE TABLE IF NOT EXISTS `saved_properties` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `property_id` INT(11) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_property` (`user_id`, `property_id`),
  CONSTRAINT `fk_saved_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_saved_properties` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. AI Chatbot Sessions & Messages
CREATE TABLE IF NOT EXISTS `chat_sessions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) DEFAULT NULL,
  `session_key` VARCHAR(64) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_key` (`session_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `session_id` INT(11) NOT NULL,
  `sender` ENUM('user','bot') NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  CONSTRAINT `fk_chat_session` FOREIGN KEY (`session_id`) REFERENCES `chat_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Platform Audit Logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `actor_id` INT(11) NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default user roles and accounts (password hash for compatibility: password 'admin123' -> 7c4a8d09ca3762af61e59520943dc26494f8941b)
INSERT INTO `users` (`email`, `password`, `full_name`, `phone`, `gender`, `college_name`, `role`, `id_verified`)
VALUES ('admin@pglife.com', SHA1('admin123'), 'Platform Super Admin', '9000000001', 'male', 'PG Life Admin HQ', 'admin', 'verified')
ON DUPLICATE KEY UPDATE `role` = 'admin';

INSERT INTO `users` (`email`, `password`, `full_name`, `phone`, `gender`, `college_name`, `role`, `id_verified`)
VALUES ('owner@pglife.com', SHA1('owner123'), 'Rajesh Sharma (Owner)', '9876543210', 'male', 'Venkateshwara Stays', 'owner', 'verified')
ON DUPLICATE KEY UPDATE `role` = 'owner';

INSERT INTO `users` (`email`, `password`, `full_name`, `phone`, `gender`, `college_name`, `role`, `id_verified`)
VALUES ('student@pglife.com', SHA1('student123'), 'Anand Narayan', '9123456789', 'male', 'Delhi University', 'user', 'verified')
ON DUPLICATE KEY UPDATE `role` = 'user';

-- Assign existing Delhi properties (1-5) to owner@pglife.com
UPDATE `properties` p
INNER JOIN `users` u ON u.email = 'owner@pglife.com'
SET p.owner_id = u.id, p.near_college = 'North Campus / Delhi University', p.is_pet_friendly = 1
WHERE p.id IN (1, 2, 3, 4, 5);

-- Seed default room types for existing properties
INSERT INTO `property_rooms` (`property_id`, `room_type`, `rent`, `capacity`, `available_rooms`) VALUES
(1, 'Single', 11500, 1, 3),
(1, 'Double', 9500, 2, 5),
(1, 'Triple', 7500, 3, 2),
(2, 'Single', 13000, 1, 2),
(2, 'Double', 11000, 2, 4),
(3, 'Single', 10000, 1, 1),
(3, 'Double', 8500, 2, 3)
ON DUPLICATE KEY UPDATE `rent` = VALUES(`rent`);

-- Seed sample notifications
INSERT INTO `notifications` (`user_id`, `type`, `title`, `message`, `link`) VALUES
((SELECT `id` FROM `users` WHERE `email` = 'owner@pglife.com'), 'interested', 'New Interested Student!', 'Anand Narayan expressed interest in Saxena PG.', 'owner_dashboard.php?tab=interested'),
((SELECT `id` FROM `users` WHERE `email` = 'student@pglife.com'), 'booking', 'Welcome to PGLife!', 'Explore top PG accommodations near your college.', 'index.php');
