-- PGLife feature upgrade: roles, owner panel, admin panel, bookings, notifications, chatbot

USE `pglife`;

ALTER TABLE `users`
  ADD COLUMN `role` ENUM('user','owner','admin') NOT NULL DEFAULT 'user' AFTER `college_name`,
  ADD COLUMN `profile_image` VARCHAR(255) DEFAULT NULL AFTER `role`,
  ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `profile_image`;

ALTER TABLE `properties`
  ADD COLUMN `owner_id` INT(11) DEFAULT NULL AFTER `city_id`,
  ADD COLUMN `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved' AFTER `rating_safety`,
  ADD COLUMN `admin_notes` TEXT DEFAULT NULL AFTER `status`,
  ADD KEY `owner_id` (`owner_id`),
  ADD CONSTRAINT `properties_owner_fk` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `property_id` INT(11) NOT NULL,
  `status` ENUM('pending','owner_approved','admin_review','confirmed','rejected') NOT NULL DEFAULT 'pending',
  `move_in_date` DATE DEFAULT NULL,
  `agreement_notes` TEXT DEFAULT NULL,
  `facility_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `admin_notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `property_id` (`property_id`),
  CONSTRAINT `bookings_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_property_fk` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  CONSTRAINT `notifications_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  CONSTRAINT `chat_messages_session_fk` FOREIGN KEY (`session_id`) REFERENCES `chat_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed admin and owner accounts (password: admin123 / owner123 via SHA1 for compatibility)
INSERT INTO `users` (`email`, `password`, `full_name`, `phone`, `gender`, `college_name`, `role`)
VALUES ('admin@pglife.com', SHA1('admin123'), 'Platform Admin', '9000000001', 'male', 'PG Life HQ', 'admin')
ON DUPLICATE KEY UPDATE `role` = 'admin';

INSERT INTO `users` (`email`, `password`, `full_name`, `phone`, `gender`, `college_name`, `role`)
VALUES ('owner@pglife.com', SHA1('owner123'), 'Property Owner', '9000000002', 'male', 'PG Life Owner', 'owner')
ON DUPLICATE KEY UPDATE `role` = 'owner';

UPDATE `users` SET `role` = 'admin' WHERE `email` = 'admin@pglife.com';
UPDATE `users` SET `role` = 'owner' WHERE `email` = 'owner@pglife.com';

-- Assign first 5 Delhi properties to demo owner
UPDATE `properties` p
INNER JOIN `users` u ON u.email = 'owner@pglife.com'
SET p.owner_id = u.id
WHERE p.id IN (1, 2, 3, 4, 5);
