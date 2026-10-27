<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/../includes/database_connect.php";
require_once __DIR__ . "/../includes/notification_helper.php";

header('Content-Type: application/json');

// 0. CAPTCHA Validation
$user_captcha    = isset($_POST['captcha']) ? strtolower(trim($_POST['captcha'])) : '';
$session_captcha = isset($_SESSION['captcha_code']) ? strtolower($_SESSION['captcha_code']) : '';
if (empty($user_captcha) || $user_captcha !== $session_captcha) {
    echo json_encode(["success" => false, "message" => "Security verification failed! Invalid CAPTCHA code. Please try again."]);
    exit;
}

$login_input = mysqli_real_escape_string($conn, trim($_POST['email']));
$password    = trim($_POST['password']);
$pass_hash   = sha1($password);

// Query by Email OR Phone
$sql    = "SELECT * FROM users WHERE (email='$login_input' OR phone='$login_input') AND (password='$password' OR password='$pass_hash')";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) === 0) {
    echo json_encode(["success" => false, "message" => "Login failed! Invalid email/mobile number or password."]);
    exit;
}

$row = mysqli_fetch_assoc($result);

// Block deactivated/archived accounts
if (isset($row['deletion_status']) && $row['deletion_status'] === 'approved') {
    echo json_encode(["success" => false, "message" => "Your account has been deactivated as per approved deletion request. Information is preserved by administration."]);
    exit;
}
if (isset($row['id_verified']) && $row['id_verified'] === 'deactivated_archived') {
    echo json_encode(["success" => false, "message" => "Account is inactive/archived. Please contact support."]);
    exit;
}

// Block unapproved owner accounts
if ($row['role'] === 'owner') {
    if ($row['id_verified'] === 'pending') {
        echo json_encode(["success" => false, "message" => "Your owner account is currently Pending Super Admin review & approval."]);
        exit;
    } elseif ($row['id_verified'] === 'rejected') {
        echo json_encode(["success" => false, "message" => "Your owner account application was rejected by Super Admin. Please contact support."]);
        exit;
    }
}

$user_id  = (int)$row['id'];
$redirect = 'index.php';
if ($row['role'] === 'admin') $redirect = 'admin_portal.php';
elseif ($row['role'] === 'owner') $redirect = 'owner_dashboard.php';
elseif ($row['role'] === 'user')  $redirect = 'profile.php';

// ── OTP Step: generate & email OTP, return otp_required flag ─────────────────
require_once __DIR__ . "/../includes/mailer.php";

$_SESSION['otp_pending_user_id'] = $user_id;
$_SESSION['otp_pending_redirect'] = $redirect;
$_SESSION['otp_pending_role']    = $row['role'];

// Invalidate old OTPs
mysqli_query($conn, "UPDATE admin_otps SET is_used = 1 WHERE user_id = $user_id AND purpose = 'login' AND is_used = 0");

// Generate OTP
$otp_code   = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
$expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
$otp_hash   = password_hash($otp_code, PASSWORD_DEFAULT);

mysqli_query($conn, "INSERT INTO admin_otps (user_id, otp_code, purpose, expires_at, is_used)
                     VALUES ($user_id, '$otp_hash', 'login', '$expires_at', 0)");

// Store raw in session as fallback
$_SESSION['otp_code_login']    = $otp_code;
$_SESSION['otp_expires_login'] = $expires_at;
$_SESSION['otp_user_id_login'] = $user_id;

// Send OTP email
$sent = send_otp_email($row['email'], $otp_code, $row['full_name'], $user_id, 'Account Login');

$masked_email = substr($row['email'], 0, 3) . '***@' . explode('@', $row['email'])[1];

echo json_encode([
    "success"       => true,
    "otp_required"  => true,
    "message"       => "OTP sent to {$masked_email}. Please enter it to complete login.",
    "masked_email"  => $masked_email,
    "email_ok"      => $sent
]);
