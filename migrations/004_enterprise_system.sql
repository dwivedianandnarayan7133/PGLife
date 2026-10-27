-- Migration 004: Enterprise Hostel & PG Booking Management System Schema Upgrade
USE `pglife`;

-- 1. Upgrade `interested_users_properties` table with lead pipeline metadata
ALTER TABLE `interested_users_properties`
  ADD COLUMN `move_in_date` DATE DEFAULT NULL,
  ADD COLUMN `room_preference` VARCHAR(50) DEFAULT 'Single',
  ADD COLUMN `budget` INT(11) DEFAULT 10000,
  ADD COLUMN `lead_status` ENUM('New','Contacted','Seat Reserved','Documents Pending','Booking Confirmed','Booking Cancelled','Not Interested','Closed') NOT NULL DEFAULT 'New',
  ADD COLUMN `notes` TEXT DEFAULT NULL,
  ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 2. Upgrade `properties` table with Seat Capacity & Occupancy Tracking
ALTER TABLE `properties`
  ADD COLUMN `total_rooms` INT(11) NOT NULL DEFAULT 10,
  ADD COLUMN `total_beds` INT(11) NOT NULL DEFAULT 20,
  ADD COLUMN `total_seats` INT(11) NOT NULL DEFAULT 20,
  ADD COLUMN `reserved_seats` INT(11) NOT NULL DEFAULT 2,
  ADD COLUMN `occupied_seats` INT(11) NOT NULL DEFAULT 12,
  ADD COLUMN `available_seats` INT(11) NOT NULL DEFAULT 6,
  ADD COLUMN `monthly_vacancy` JSON DEFAULT NULL,
  ADD COLUMN `waiting_list_enabled` TINYINT(1) NOT NULL DEFAULT 1;

-- 3. Create Waiting List Table
CREATE TABLE IF NOT EXISTS `waiting_list` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `property_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `preferred_move_in` DATE NOT NULL,
  `requested_room_type` VARCHAR(50) NOT NULL DEFAULT 'Single',
  `status` ENUM('waiting','notified','converted','expired','cancelled') NOT NULL DEFAULT 'waiting',
  `notified_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_waitlist_properties` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_waitlist_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Create Support & Complaint Tickets Table
CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `property_id` INT(11) DEFAULT NULL,
  `ticket_type` ENUM('complaint','inquiry','maintenance','refund','other') NOT NULL DEFAULT 'inquiry',
  `subject` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('Open','In Progress','Resolved','Closed') NOT NULL DEFAULT 'Open',
  `admin_notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_tickets_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Expand `bookings` table status lifecycle
ALTER TABLE `bookings`
  MODIFY COLUMN `status` ENUM('Pending','Contacted','Seat Reserved','Documents Submitted','Agreement Pending','Admin Verification','Approved','Checked In','Active','Checked Out','Cancelled','Rejected') NOT NULL DEFAULT 'Pending';

-- 6. Initialize default monthly vacancy JSON for existing properties
UPDATE `properties` 
SET `monthly_vacancy` = '{"January": 6, "February": 0, "March": 3, "April": 8, "May": 5, "June": 4, "July": 2, "August": 0, "September": 7, "October": 5, "November": 6, "December": 4}',
    `available_seats` = 6
WHERE `available_seats` IS NULL OR `available_seats` = 6;
