-- ===================================================================
-- PGLife Complete Production Database Dump
-- Compatible with: Railway, Render, TiDB, Aiven, InfinityFree, XAMPP, Docker
-- ===================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Table structure for `amenities`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `amenities`;
CREATE TABLE `amenities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` varchar(100) NOT NULL,
  `icon` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `cities`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `cities`;
CREATE TABLE `cities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `college_name` varchar(255) NOT NULL,
  `role` enum('user','owner','admin') NOT NULL DEFAULT 'user',
  `profile_image` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `id_proof_doc` varchar(255) DEFAULT NULL,
  `id_verified` enum('unverified','pending','verified','rejected','deactivated_archived') NOT NULL DEFAULT 'unverified',
  `notification_prefs` varchar(255) DEFAULT '{"email":true,"push":true,"sms":false}',
  `deletion_status` enum('none','pending_approval','approved','rejected') DEFAULT 'none',
  `deletion_reason` text DEFAULT NULL,
  `deletion_requested_at` datetime DEFAULT NULL,
  `deletion_admin_remark` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `properties`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `properties`;
CREATE TABLE `properties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `city_id` int(11) NOT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `gender` enum('male','female','unisex') NOT NULL,
  `rent` int(11) NOT NULL,
  `rating_clean` float(3,1) NOT NULL DEFAULT 4.0,
  `rating_food` float(3,1) NOT NULL DEFAULT 4.0,
  `rating_safety` float(3,1) NOT NULL DEFAULT 4.0,
  `status` enum('pending','approved','rejected','suspended') NOT NULL DEFAULT 'approved',
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `is_pet_friendly` tinyint(1) NOT NULL DEFAULT 0,
  `near_college` varchar(255) DEFAULT NULL,
  `views_count` int(11) NOT NULL DEFAULT 0,
  `admin_notes` text DEFAULT NULL,
  `total_rooms` int(11) NOT NULL DEFAULT 10,
  `total_beds` int(11) NOT NULL DEFAULT 20,
  `total_seats` int(11) NOT NULL DEFAULT 20,
  `reserved_seats` int(11) NOT NULL DEFAULT 2,
  `occupied_seats` int(11) NOT NULL DEFAULT 12,
  `available_seats` int(11) NOT NULL DEFAULT 6,
  `monthly_vacancy` json DEFAULT NULL,
  `waiting_list_enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `city_id` (`city_id`),
  KEY `owner_id` (`owner_id`),
  CONSTRAINT `fk_properties_cities` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_properties_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `properties_amenities`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `properties_amenities`;
CREATE TABLE `properties_amenities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `amenity_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`),
  KEY `amenity_id` (`amenity_id`),
  CONSTRAINT `fk_pa_amenities` FOREIGN KEY (`amenity_id`) REFERENCES `amenities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pa_properties` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `testimonials`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `testimonials`;
CREATE TABLE `testimonials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`),
  CONSTRAINT `fk_testimonials_properties` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `interested_users_properties`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `interested_users_properties`;
CREATE TABLE `interested_users_properties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `move_in_date` date DEFAULT NULL,
  `room_preference` varchar(50) DEFAULT 'Single',
  `budget` int(11) DEFAULT 10000,
  `lead_status` enum('New','Contacted','Seat Reserved','Documents Pending','Booking Confirmed','Booking Cancelled','Not Interested','Closed') NOT NULL DEFAULT 'New',
  `notes` text DEFAULT NULL,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `property_id` (`property_id`),
  CONSTRAINT `fk_iup_properties` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_iup_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `property_rooms`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `property_rooms`;
CREATE TABLE `property_rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `room_type` enum('Single','Double','Triple','Dormitory') NOT NULL,
  `rent` int(11) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 1,
  `available_rooms` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`),
  CONSTRAINT `fk_rooms_properties` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `property_images`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `property_images`;
CREATE TABLE `property_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `is_cover` tinyint(1) NOT NULL DEFAULT 0,
  `display_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`),
  CONSTRAINT `fk_images_properties` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `bookings`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `room_type` varchar(50) DEFAULT 'Single',
  `status` enum('Pending','Contacted','Seat Reserved','Documents Submitted','Agreement Pending','Admin Verification','Approved','Checked In','Active','Checked Out','Cancelled','Rejected') NOT NULL DEFAULT 'Pending',
  `move_in_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `agreement_notes` text DEFAULT NULL,
  `facility_verified` tinyint(1) NOT NULL DEFAULT 0,
  `payment_status` enum('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `property_id` (`property_id`),
  CONSTRAINT `fk_bookings_properties` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bookings_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `agreements`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `agreements`;
CREATE TABLE `agreements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `agreement_doc` varchar(255) NOT NULL,
  `rules_doc` varchar(255) DEFAULT NULL,
  `terms_doc` varchar(255) DEFAULT NULL,
  `status` enum('pending_review','approved','rejected') NOT NULL DEFAULT 'pending_review',
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `owner_id` (`owner_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_agreements_bookings` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `owner_verifications`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `owner_verifications`;
CREATE TABLE `owner_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) NOT NULL,
  `govt_id_doc` varchar(255) DEFAULT NULL,
  `business_license_doc` varchar(255) DEFAULT NULL,
  `property_doc` varchar(255) DEFAULT NULL,
  `bank_account` varchar(255) DEFAULT NULL,
  `status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `owner_id` (`owner_id`),
  CONSTRAINT `fk_verifications_users` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `notifications`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_notifications_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `saved_properties`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `saved_properties`;
CREATE TABLE `saved_properties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_property` (`user_id`, `property_id`),
  CONSTRAINT `fk_saved_properties` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_saved_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `chat_sessions`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `chat_sessions`;
CREATE TABLE `chat_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `session_key` varchar(64) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_key` (`session_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `chat_messages`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `chat_messages`;
CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `sender` enum('user','bot') NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  CONSTRAINT `fk_chat_session` FOREIGN KEY (`session_id`) REFERENCES `chat_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `audit_logs`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `actor_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `waiting_list`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `waiting_list`;
CREATE TABLE `waiting_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `preferred_move_in` date NOT NULL,
  `requested_room_type` varchar(50) NOT NULL DEFAULT 'Single',
  `status` enum('waiting','notified','converted','expired','cancelled') NOT NULL DEFAULT 'waiting',
  `notified_at` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_waitlist_properties` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_waitlist_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `support_tickets`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `support_tickets`;
CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `property_id` int(11) DEFAULT NULL,
  `ticket_type` enum('complaint','inquiry','maintenance','refund','other') NOT NULL DEFAULT 'inquiry',
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('Open','In Progress','Resolved','Closed') NOT NULL DEFAULT 'Open',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_tickets_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table structure for `admin_otps`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `admin_otps`;
CREATE TABLE `admin_otps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_otps_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- DATA DUMP
-- --------------------------------------------------------

INSERT INTO `amenities` (`id`, `name`, `type`, `icon`) VALUES ('1', 'Wifi', 'Common Area', 'wifi');
INSERT INTO `amenities` (`id`, `name`, `type`, `icon`) VALUES ('2', 'Air Conditioner', 'Bedroom', 'ac');
INSERT INTO `amenities` (`id`, `name`, `type`, `icon`) VALUES ('3', 'TV', 'Common Area', 'tv');
INSERT INTO `amenities` (`id`, `name`, `type`, `icon`) VALUES ('4', 'Bed with Mattress', 'Bedroom', 'bed');
INSERT INTO `amenities` (`id`, `name`, `type`, `icon`) VALUES ('5', 'CCTV Security', 'Building', 'cctv');
INSERT INTO `amenities` (`id`, `name`, `type`, `icon`) VALUES ('6', 'Dining Area', 'Common Area', 'dining');
INSERT INTO `amenities` (`id`, `name`, `type`, `icon`) VALUES ('7', 'Fire Extinguisher', 'Building', 'fireext');
INSERT INTO `amenities` (`id`, `name`, `type`, `icon`) VALUES ('8', 'Geyser', 'Washroom', 'geyser');
INSERT INTO `amenities` (`id`, `name`, `type`, `icon`) VALUES ('9', 'Elevator / Lift', 'Building', 'lift');
INSERT INTO `amenities` (`id`, `name`, `type`, `icon`) VALUES ('10', 'Parking Space', 'Building', 'parking');
INSERT INTO `amenities` (`id`, `name`, `type`, `icon`) VALUES ('11', 'Power Backup', 'Building', 'powerbackup');
INSERT INTO `amenities` (`id`, `name`, `type`, `icon`) VALUES ('12', 'RO Water Purifier', 'Common Area', 'rowater');
INSERT INTO `amenities` (`id`, `name`, `type`, `icon`) VALUES ('13', 'Washing Machine', 'Common Area', 'washingmachine');
INSERT INTO `cities` (`id`, `name`) VALUES ('1', 'Delhi');
INSERT INTO `cities` (`id`, `name`) VALUES ('2', 'Mumbai');
INSERT INTO `cities` (`id`, `name`) VALUES ('3', 'Bengaluru');
INSERT INTO `cities` (`id`, `name`) VALUES ('4', 'Hyderabad');
INSERT INTO `interested_users_properties` (`id`, `user_id`, `property_id`) VALUES ('1', '1', '1');
INSERT INTO `interested_users_properties` (`id`, `user_id`, `property_id`) VALUES ('2', '1', '2');
INSERT INTO `interested_users_properties` (`id`, `user_id`, `property_id`) VALUES ('3', '2', '3');
INSERT INTO `interested_users_properties` (`id`, `user_id`, `property_id`) VALUES ('4', '2', '10');
INSERT INTO `interested_users_properties` (`id`, `user_id`, `property_id`) VALUES ('7', '5', '1');
INSERT INTO `interested_users_properties` (`id`, `user_id`, `property_id`) VALUES ('13', '3', '5');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('1', '1', 'Saxena PG', 'H.No. 395, Mukherjee Nagar, Near Batra Cinema, Delhi', 'Furnished PG with high-speed internet, delicious food, daily cleaning service, and security camera monitoring.', 'unisex', '9500', '4.5', '4.2', '4.8');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('2', '1', 'Navkar Residency', 'Plot No. 12, Hudson Lane, GTB Nagar, Delhi', 'Premium student living with spacious rooms, attached washrooms, biometric entry, and full power backup.', 'male', '11000', '4.8', '4.5', '4.7');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('3', '1', 'PG for Girls by Sagar', 'House 44, Vijay Nagar, Single/Double Sharing, Delhi', 'Safe and secure girls accommodation near Delhi University North Campus with homemade meals and high-speed Wi-Fi.', 'female', '8500', '4.0', '4.0', '4.9');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('4', '1', 'Elite Heights PG', 'D-15, Kamla Nagar, Delhi', 'Modern rooms equipped with AC, study desk, 24/7 security guard, and power backup.', 'male', '12500', '4.7', '4.3', '4.6');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('5', '1', 'Sunshine Living', 'Block B, Laxmi Nagar, Delhi', 'Affordable student stay with clean washrooms, water filter, and close proximity to metro station.', 'unisex', '7000', '4.1', '3.9', '4.4');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('6', '1', 'Starlight Girls Hostel', 'C-88, South Extension Part 1, Delhi', 'Spacious rooms with modern interior, geyser in every washroom, and healthy multi-cuisine meals.', 'female', '14000', '4.9', '4.6', '4.9');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('7', '1', 'Royal Comforts PG', 'E-24, Lajpat Nagar 4, Delhi', 'Fully furnished PG with air conditioning, LED TV in common lounge, and weekly housekeeping.', 'unisex', '10500', '4.3', '4.1', '4.5');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('8', '1', 'Green View Residency', 'Flat 102, Dwarka Sector 7, Delhi', 'Quiet environment suitable for students and working professionals with 24-hour security.', 'male', '9000', '4.2', '4.0', '4.6');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('9', '1', 'Metro Heights Girls PG', '15-A, Karol Bagh, Near Metro Gate 3, Delhi', 'Clean and secure PG with single/double occupancy, CCTV coverage, and 3-time meal service.', 'female', '11500', '4.6', '4.4', '4.8');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('10', '2', 'Zolo Apex', '45, Near Mithibai College, Vile Parle West, Mumbai', 'Luxurious co-living space with biometric access, high speed internet, gaming area, and daily meals.', 'unisex', '18000', '4.7', '4.5', '4.8');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('11', '2', 'Stanza Living Mumbai', 'Sector 4, Airoli, Navi Mumbai', 'Comfortable stays for students and professionals with laundry services, fitness corner, and healthy food.', 'male', '15000', '4.5', '4.3', '4.7');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('12', '2', 'Orchid Haven PG', 'Pali Hill, Bandra West, Mumbai', 'Premium girls PG with sea view balconies, air-conditioned rooms, and dedicated house staff.', 'female', '22000', '4.9', '4.8', '5.0');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('13', '2', 'Sea Pearl Residence', 'Carter Road, Bandra West, Mumbai', 'Spacious rooms near top colleges, fully equipped kitchen, high-speed Wi-Fi, and 24/7 security.', 'unisex', '20000', '4.6', '4.4', '4.9');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('14', '2', 'Gokul Dham PG', 'Near station, Andheri East, Mumbai', 'Budget-friendly PG for male professionals with regular cleaning, RO water, and geyser facilities.', 'male', '11000', '4.0', '3.8', '4.3');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('15', '2', 'Graceful Stays for Women', 'Powai Lake Road, Powai, Mumbai', 'Located near IIT Bombay, offering quiet study environment, delicious meals, and air-conditioned rooms.', 'female', '16500', '4.8', '4.6', '4.9');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('16', '2', 'Metro Stays Juhu', 'Gulmohar Road, Juhu, Mumbai', 'Well-furnished rooms with flat TV, fast Wi-Fi, laundry facilities, and round-the-clock water supply.', 'unisex', '17500', '4.4', '4.2', '4.6');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('17', '2', 'Cosmo Living', 'Opposite Phoenix Marketcity, Kurla West, Mumbai', 'Modern co-living space with gym access, common recreational hall, and healthy dietary options.', 'male', '13500', '4.3', '4.0', '4.5');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('18', '2', 'Orchid Women Lodge', 'LBS Marg, Ghatkopar West, Mumbai', 'Safe environment for working women, close to metro station, with homemade meals and power backup.', 'female', '12500', '4.4', '4.3', '4.7');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('19', '3', 'Greens PG for Men', '100 Feet Road, Indiranagar, Bengaluru', 'Located in prime tech hub area, offering fast Wi-Fi, power backup, self-cooking kitchen, and daily cleaning.', 'male', '12000', '4.6', '4.3', '4.7');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('20', '3', 'Bloom Co-Living', 'Koramangala 5th Block, Bengaluru', 'Trendy co-living space with rooftop garden, high-speed internet, gaming lounge, and chef-cooked meals.', 'unisex', '16000', '4.8', '4.7', '4.8');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('21', '3', 'Tulip Girls PG', 'Near Forum Mall, Hosur Road, Bengaluru', 'Secure accommodation for female students and IT professionals with 24/7 warden and CCTV.', 'female', '10500', '4.5', '4.4', '4.9');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('22', '3', 'Silicon City Stays', 'Outer Ring Road, Marathahalli, Bengaluru', 'Close to IT parks, providing furnished AC rooms, laundry facilities, and multi-cuisine buffet meals.', 'unisex', '13000', '4.4', '4.2', '4.6');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('23', '3', 'Techie Habitat PG', 'EPIP Zone, Whitefield, Bengaluru', 'Spacious single/double rooms with study desk, dedicated power backup, and housekeeping.', 'male', '11500', '4.3', '4.1', '4.5');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('24', '3', 'Emerald Ladies Hostel', '6th Main, Malleshwaram, Bengaluru', 'Peaceful stay for women with strict security, nutritious home-cooked meals, and RO drinking water.', 'female', '9500', '4.6', '4.5', '4.9');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('25', '3', 'Garden City Residence', 'Bannerghatta Road, Near IIMB, Bengaluru', 'Fully furnished rooms with balcony, high-speed Wi-Fi, lift, and dedicated parking space.', 'unisex', '14000', '4.7', '4.4', '4.7');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('26', '3', 'Breeze Point PG', 'HSR Layout Sector 1, Bengaluru', 'Top-rated PG with spacious rooms, power backup, LED TV, and walking distance to parks and markets.', 'male', '12500', '4.5', '4.3', '4.6');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('27', '3', 'Serenity Girls Home', 'BTM Layout 2nd Stage, Bengaluru', 'Hygienic living with automated laundry, clean washrooms, 24/7 security guard, and biometric access.', 'female', '10000', '4.4', '4.2', '4.8');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('28', '4', 'Cyber Towers PG', 'Near Mindspace IT Park, Hitech City, Hyderabad', 'Ideal for IT employees with high-speed internet, air conditioning, daily room cleaning, and buffet food.', 'male', '11000', '4.5', '4.3', '4.7');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('29', '4', 'Charminar Stays', 'Road No. 12, Banjara Hills, Hyderabad', 'Luxury rooms with attached bathroom, Smart TV, power backup, and round-the-clock security.', 'unisex', '15000', '4.8', '4.6', '4.8');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('30', '4', 'Lotus Ladies Hostel', 'Near Metro Station, Ameerpet, Hyderabad', 'Budget friendly girls PG near coaching centers, providing three meals a day and high-speed Wi-Fi.', 'female', '7500', '4.2', '4.1', '4.8');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('31', '4', 'Pearl City Residency', 'Gachibowli Main Road, Hyderabad', 'Modern co-living space equipped with fitness center, indoor games, high-speed Wi-Fi, and 24/7 water.', 'unisex', '13500', '4.6', '4.4', '4.7');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('32', '4', 'Royal Men Stays', 'Kondapur Signal, Hyderabad', 'Spacious rooms with study chairs, power backup, daily maintenance, and tasty South/North Indian food.', 'male', '10000', '4.3', '4.2', '4.5');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('33', '4', 'Srinivasa Girls PG', 'Kukatpally Housing Board Colony, Hyderabad', 'Clean and secure hostel for women with CCTV surveillance, RO filter, geysers, and biometric entry.', 'female', '8500', '4.4', '4.3', '4.9');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('34', '4', 'Nizam Comforts', 'Jubilee Hills Checkpost, Hyderabad', 'High-end furnished accommodation with air conditioning, laundry service, and high-speed internet.', 'unisex', '16500', '4.7', '4.5', '4.8');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('35', '4', 'Venkateshwara Executive PG', 'Madhapur Main Road, Hyderabad', 'Located near major tech hubs, featuring individual lockers, Wi-Fi, power backup, and daily housekeeping.', 'male', '11500', '4.4', '4.2', '4.6');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('36', '4', 'Green Leaves Women Stay', 'DLF Road, Gachibowli, Hyderabad', 'Comfortable rooms with attached washrooms, geysers, healthy food, and 24-hour security.', 'female', '12000', '4.6', '4.5', '4.9');
INSERT INTO `properties` (`id`, `city_id`, `name`, `address`, `description`, `gender`, `rent`, `rating_clean`, `rating_food`, `rating_safety`) VALUES ('37', '4', 'Grand View Residence', 'Begumpet Airport Road, Hyderabad', 'Furnished PG with lift, car parking, power backup, and regular sanitation.', 'unisex', '9500', '4.2', '4.0', '4.5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('1', '1', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('2', '1', '2');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('3', '1', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('4', '1', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('5', '1', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('6', '1', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('7', '1', '12');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('8', '2', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('9', '2', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('10', '2', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('11', '2', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('12', '2', '9');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('13', '2', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('14', '2', '13');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('15', '3', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('16', '3', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('17', '3', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('18', '3', '6');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('19', '3', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('20', '3', '12');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('21', '4', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('22', '4', '2');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('23', '4', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('24', '4', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('25', '4', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('26', '4', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('27', '4', '13');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('28', '5', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('29', '5', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('30', '5', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('31', '5', '12');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('32', '6', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('33', '6', '2');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('34', '6', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('35', '6', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('36', '6', '6');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('37', '6', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('38', '6', '9');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('39', '6', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('40', '6', '12');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('41', '6', '13');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('42', '7', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('43', '7', '2');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('44', '7', '3');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('45', '7', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('46', '7', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('47', '7', '10');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('48', '7', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('49', '8', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('50', '8', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('51', '8', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('52', '8', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('53', '8', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('54', '8', '12');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('55', '9', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('56', '9', '2');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('57', '9', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('58', '9', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('59', '9', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('60', '9', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('61', '9', '12');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('62', '9', '13');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('63', '10', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('64', '10', '2');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('65', '10', '3');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('66', '10', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('67', '10', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('68', '10', '6');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('69', '10', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('70', '10', '9');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('71', '10', '10');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('72', '10', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('73', '10', '12');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('74', '10', '13');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('75', '11', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('76', '11', '2');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('77', '11', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('78', '11', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('79', '11', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('80', '11', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('81', '11', '13');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('82', '12', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('83', '12', '2');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('84', '12', '3');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('85', '12', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('86', '12', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('87', '12', '6');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('88', '12', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('89', '12', '9');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('90', '12', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('91', '12', '12');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('92', '12', '13');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('93', '13', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('94', '13', '2');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('95', '13', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('96', '13', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('97', '13', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('98', '13', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('99', '13', '12');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('100', '14', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('101', '14', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('102', '14', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('103', '14', '12');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('104', '15', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('105', '15', '2');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('106', '15', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('107', '15', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('108', '15', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('109', '15', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('110', '15', '12');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('111', '16', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('112', '16', '2');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('113', '16', '3');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('114', '16', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('115', '16', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('116', '16', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('117', '16', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('118', '16', '13');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('119', '17', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('120', '17', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('121', '17', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('122', '17', '6');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('123', '17', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('124', '17', '10');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('125', '17', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('126', '18', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('127', '18', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('128', '18', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('129', '18', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('130', '18', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('131', '18', '12');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('132', '19', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('133', '19', '2');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('134', '19', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('135', '19', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('136', '19', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('137', '19', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('138', '19', '12');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('139', '19', '13');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('140', '20', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('141', '20', '2');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('142', '20', '3');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('143', '20', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('144', '20', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('145', '20', '6');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('146', '20', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('147', '20', '9');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('148', '20', '10');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('149', '20', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('150', '20', '12');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('151', '20', '13');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('152', '21', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('153', '21', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('154', '21', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('155', '21', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('156', '21', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('157', '21', '12');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('158', '22', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('159', '22', '2');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('160', '22', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('161', '22', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('162', '22', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('163', '22', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('164', '22', '12');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('165', '22', '13');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('166', '23', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('167', '23', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('168', '23', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('169', '23', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('170', '23', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('171', '23', '12');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('172', '24', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('173', '24', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('174', '24', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('175', '24', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('176', '24', '12');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('177', '25', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('178', '25', '2');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('179', '25', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('180', '25', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('181', '25', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('182', '25', '9');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('183', '25', '10');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('184', '25', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('185', '26', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('186', '26', '2');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('187', '26', '3');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('188', '26', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('189', '26', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('190', '26', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('191', '26', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('192', '27', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('193', '27', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('194', '27', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('195', '27', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('196', '27', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('197', '27', '12');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('198', '27', '13');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('199', '28', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('200', '28', '2');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('201', '28', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('202', '28', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('203', '28', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('204', '28', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('205', '28', '12');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('206', '28', '13');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('207', '29', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('208', '29', '2');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('209', '29', '3');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('210', '29', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('211', '29', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('212', '29', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('213', '29', '10');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('214', '29', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('215', '30', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('216', '30', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('217', '30', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('218', '30', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('219', '30', '12');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('220', '31', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('221', '31', '2');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('222', '31', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('223', '31', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('224', '31', '6');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('225', '31', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('226', '31', '10');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('227', '31', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('228', '31', '12');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('229', '32', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('230', '32', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('231', '32', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('232', '32', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('233', '32', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('234', '32', '12');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('235', '33', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('236', '33', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('237', '33', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('238', '33', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('239', '33', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('240', '33', '12');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('241', '34', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('242', '34', '2');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('243', '34', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('244', '34', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('245', '34', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('246', '34', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('247', '34', '13');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('248', '35', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('249', '35', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('250', '35', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('251', '35', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('252', '35', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('253', '35', '12');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('254', '36', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('255', '36', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('256', '36', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('257', '36', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('258', '36', '11');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('259', '36', '12');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('260', '37', '1');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('261', '37', '4');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('262', '37', '5');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('263', '37', '8');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('264', '37', '9');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('265', '37', '10');
INSERT INTO `properties_amenities` (`id`, `property_id`, `amenity_id`) VALUES ('266', '37', '11');
INSERT INTO `testimonials` (`id`, `property_id`, `user_name`, `content`) VALUES ('1', '1', 'Ashutosh Gowariker', 'You just cannot beat the quality of food here. The staff is polite, helpful and always responsive.');
INSERT INTO `testimonials` (`id`, `property_id`, `user_name`, `content`) VALUES ('2', '1', 'Karan Johar', 'Awesome place to live in Delhi! Very safe and hygienic environment for students.');
INSERT INTO `testimonials` (`id`, `property_id`, `user_name`, `content`) VALUES ('3', '2', 'Rohit Sharma', 'Clean rooms and great maintenance. Highly recommended for students near GTB Nagar.');
INSERT INTO `testimonials` (`id`, `property_id`, `user_name`, `content`) VALUES ('4', '10', 'Aamir Khan', 'Great experience living here. Fast Wi-Fi and awesome co-living vibe.');
INSERT INTO `testimonials` (`id`, `property_id`, `user_name`, `content`) VALUES ('5', '19', 'Rahul Dravid', 'Indiranagar location is super convenient. Nice environment and clean amenities.');
INSERT INTO `testimonials` (`id`, `property_id`, `user_name`, `content`) VALUES ('6', '28', 'VVS Laxman', 'Excellent PG in Hitech city. Very peaceful and well managed.');

-- Dumping data for `users` (Including Admin and Demo Owner)
INSERT INTO `users` (`id`, `email`, `password`, `full_name`, `phone`, `gender`, `college_name`, `role`, `id_verified`) VALUES
(1, 'john@example.com', '7c4a8d09ca3762af61e59520943dc26494f8941b', 'John Doe', '9876543210', 'male', 'Delhi University', 'user', 'unverified'),
(2, 'jane@example.com', '7c4a8d09ca3762af61e59520943dc26494f8941b', 'Jane Smith', '9123456789', 'female', 'IIT Bombay', 'user', 'unverified'),
(3, 'dwivedianandnarayan@gmail.com', '326d13db9e204721eedf38d85204abee57b4a061', 'Anand Narayan Dwivedi', '9120367133', 'male', 'Bansal Institute Of Engineering Technology', 'admin', 'verified'),
(4, 'testuser993@example.com', 'f2b14f68eb995facb3a1c35287b778d5bd785511', 'Test User', '9998887776', 'male', 'Test College', 'user', 'unverified'),
(5, 'narayan@gmail.com', '4d02f24c6a1e207b78d9396869de49b6a86ce1b1', 'Anand', '9120367137', 'male', 'Bansal Institute Of Engineering Technology', 'owner', 'verified'),
(6, 'admin@pglife.com', SHA1('admin123'), 'Platform Super Admin', '9000000001', 'male', 'PG Life Admin HQ', 'admin', 'verified'),
(7, 'owner@pglife.com', SHA1('owner123'), 'Rajesh Sharma (Owner)', '9876543210', 'male', 'Venkateshwara Stays', 'owner', 'verified'),
(8, 'student@pglife.com', SHA1('student123'), 'Anand Narayan', '9123456789', 'male', 'Delhi University', 'user', 'verified')
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);


-- Update property vacancies and metadata
UPDATE `properties` 
SET `monthly_vacancy` = '{"January": 6, "February": 0, "March": 3, "April": 8, "May": 5, "June": 4, "July": 2, "August": 0, "September": 7, "October": 5, "November": 6, "December": 4}',
    `available_seats` = 6,
    `total_rooms` = 10,
    `total_beds` = 20,
    `total_seats` = 20,
    `reserved_seats` = 2,
    `occupied_seats` = 12
WHERE `available_seats` IS NULL OR `available_seats` = 6;

-- Assign demo properties to owner
UPDATE `properties` p
INNER JOIN `users` u ON u.email = 'owner@pglife.com'
SET p.owner_id = u.id, p.near_college = 'North Campus / Delhi University', p.is_pet_friendly = 1
WHERE p.id IN (1, 2, 3, 4, 5);

-- Seed rooms for properties
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
((SELECT `id` FROM `users` WHERE `email` = 'owner@pglife.com' LIMIT 1), 'interested', 'New Interested Student!', 'Anand Narayan expressed interest in Saxena PG.', 'owner_dashboard.php?tab=interested'),
((SELECT `id` FROM `users` WHERE `email` = 'student@pglife.com' LIMIT 1), 'booking', 'Welcome to PGLife!', 'Explore top PG accommodations near your college.', 'index.php');

SET FOREIGN_KEY_CHECKS = 1;
