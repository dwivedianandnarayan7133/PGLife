<?php
/**
 * api/send_otp.php
 * Generates a 6-digit OTP, stores it in `admin_otps`, and emails it to the user.
 * Called via AJAX POST with: { user_id, purpose }
 * Purpose values: 'login', 'registration', 'password_reset', 'account_deletion'
 */
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../includes/database_connect.php';
require_once __DIR__ . '/../includes/mailer.php';

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$purpose = isset($_POST['purpose']) ? trim($_POST['purpose']) : 'login';

// Resolve user_id from session if not provided
if ($user_id <= 0 && isset($_SESSION['otp_pending_user_id'])) {
    $user_id = (int)$_SESSION['otp_pending_user_id'];
}

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'No user identified. Please start the login process again.']);
    exit;
}

// Fetch user from DB
$u_res = mysqli_query($conn, "SELECT id, full_name, email, phone, role FROM users WHERE id = $user_id LIMIT 1");
if (!$u_res || mysqli_num_rows($u_res) === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}
$user = mysqli_fetch_assoc($u_res);

if (empty($user['email'])) {
    echo json_encode(['success' => false, 'message' => 'No email address registered for this account.']);
    exit;
}

// Rate limit: max 3 OTPs per 5 minutes per user
$rate_check = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM admin_otps 
    WHERE user_id = $user_id AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
$rate_row = mysqli_fetch_assoc($rate_check);
if ((int)$rate_row['cnt'] >= 3) {
    echo json_encode(['success' => false, 'message' => 'Too many OTP requests. Please wait 5 minutes before requesting again.']);
    exit;
}

// Invalidate any existing unused OTPs for this user+purpose
mysqli_query($conn, "UPDATE admin_otps SET is_used = 1 WHERE user_id = $user_id AND purpose = '" . mysqli_real_escape_string($conn, $purpose) . "' AND is_used = 0");

// Generate secure 6-digit OTP
$otp_code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
$expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
$purpose_clean = mysqli_real_escape_string($conn, $purpose);
$otp_hash = password_hash($otp_code, PASSWORD_DEFAULT);

// Store OTP in DB
mysqli_query($conn, "INSERT INTO admin_otps (user_id, otp_code, purpose, expires_at, is_used) 
                     VALUES ($user_id, '$otp_hash', '$purpose_clean', '$expires_at', 0)");
$otp_id = mysqli_insert_id($conn);

// Send OTP Email
$purpose_label = ucwords(str_replace('_', ' ', $purpose));
$sent = send_otp_email(
    $user['email'],
    $otp_code,
    $user['full_name'],
    $user_id,
    $purpose_label
);

// Also store raw OTP in session for extra fallback verification
$_SESSION['otp_code_' . $purpose]        = $otp_code;
$_SESSION['otp_user_id_' . $purpose]     = $user_id;
$_SESSION['otp_expires_' . $purpose]     = $expires_at;
$_SESSION['otp_pending_user_id']          = $user_id;

echo json_encode([
    'success'  => true,
    'message'  => 'OTP sent successfully to ' . substr($user['email'], 0, 3) . '***@' . explode('@', $user['email'])[1],
    'otp_id'   => $otp_id,
    'email_ok' => $sent
]);
