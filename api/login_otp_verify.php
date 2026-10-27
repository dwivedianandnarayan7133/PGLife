<?php
/**
 * api/login_otp_verify.php
 * Verifies OTP submitted during login. On success, creates the user session and redirects.
 */
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../includes/database_connect.php';
require_once __DIR__ . '/../includes/notification_helper.php';

$submitted_otp = isset($_POST['otp_code']) ? trim($_POST['otp_code']) : '';
$user_id       = isset($_SESSION['otp_pending_user_id']) ? (int)$_SESSION['otp_pending_user_id'] : 0;
$redirect      = isset($_SESSION['otp_pending_redirect']) ? $_SESSION['otp_pending_redirect'] : 'index.php';

// Validations
if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    exit;
}
if (empty($submitted_otp) || strlen($submitted_otp) !== 6 || !ctype_digit($submitted_otp)) {
    echo json_encode(['success' => false, 'message' => 'Please enter the 6-digit OTP from your email.']);
    exit;
}

// ── 1. Session fallback check (fastest) ──────────────────────────────────────
$otp_verified = false;
if (isset($_SESSION['otp_code_login']) && (int)$_SESSION['otp_user_id_login'] === $user_id) {
    if (strtotime($_SESSION['otp_expires_login']) > time() && $_SESSION['otp_code_login'] === $submitted_otp) {
        $otp_verified = true;
    }
}

// ── 2. DB check ───────────────────────────────────────────────────────────────
if (!$otp_verified) {
    $res = mysqli_query($conn, "SELECT id, otp_code FROM admin_otps 
        WHERE user_id = $user_id AND purpose = 'login' AND is_used = 0 AND expires_at > NOW()
        ORDER BY id DESC LIMIT 1");
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        if (password_verify($submitted_otp, $row['otp_code']) || $row['otp_code'] === $submitted_otp) {
            $otp_verified = true;
        }
    }
}

if (!$otp_verified) {
    echo json_encode(['success' => false, 'message' => 'Incorrect or expired OTP. Please check your email and try again.']);
    exit;
}

// Mark OTP as used
mysqli_query($conn, "UPDATE admin_otps SET is_used = 1 WHERE user_id = $user_id AND purpose = 'login' AND is_used = 0");
unset($_SESSION['otp_code_login'], $_SESSION['otp_expires_login'], $_SESSION['otp_user_id_login'], $_SESSION['otp_pending_user_id'], $_SESSION['otp_pending_redirect'], $_SESSION['otp_pending_role']);

// Fetch user and set session
$user_res = mysqli_query($conn, "SELECT id, full_name, email, role FROM users WHERE id = $user_id LIMIT 1");
if (!$user_res || mysqli_num_rows($user_res) === 0) {
    echo json_encode(['success' => false, 'message' => 'User record not found. Please login again.']);
    exit;
}
$user = mysqli_fetch_assoc($user_res);

$_SESSION['user_id']   = $user['id'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['email']     = $user['email'];
$_SESSION['role']      = $user['role'];

// Dispatch login security notification
$login_time = date('d M Y, h:i A');
create_notification(
    $user['id'],
    'login_alert',
    'Security Alert: Successful Login',
    "Hello {$user['full_name']}, your PGLife account login was verified with OTP on {$login_time}.",
    $redirect
);

echo json_encode(['success' => true, 'message' => 'Login successful!', 'redirect' => $redirect]);
