-- Migration 007: Account Deletion Request, Preserved Data Archival, T&C Modal, CAPTCHA, and Admin 2FA OTP
USE `pglife`;

-- 1. Upgrade `users` table
ALTER TABLE users ADD COLUMN IF NOT EXISTS deletion_status ENUM('none', 'pending_approval', 'approved', 'rejected') DEFAULT 'none';
ALTER TABLE users ADD COLUMN IF NOT EXISTS deletion_reason TEXT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS deletion_requested_at DATETIME NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS deletion_admin_remark TEXT NULL;
ALTER TABLE users MODIFY COLUMN id_verified ENUM('unverified', 'pending', 'verified', 'rejected', 'deactivated_archived') DEFAULT 'unverified';

-- 2. Create `admin_otps` Table for Super Admin 2FA
CREATE TABLE IF NOT EXISTS admin_otps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
