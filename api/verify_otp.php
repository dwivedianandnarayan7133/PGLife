<?php
/**
 * api/verify_otp.php
 * Verifies a submitted OTP against DB record (with session fallback).
 * Called via AJAX POST with: { otp_code, purpose }
 * Returns: { success, message }
 */
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../includes/database_connect.php';

$submitted_otp = isset($_POST['otp_code']) ? trim($_POST['otp_code']) : '';
$purpose       = isset($_POST['purpose']) ? trim($_POST['purpose']) : 'login';
$user_id       = isset($_SESSION['otp_pending_user_id']) ? (int)$_SESSION['otp_pending_user_id'] : 0;

if (empty($submitted_otp) || strlen($submitted_otp) !== 6 || !ctype_digit($submitted_otp)) {
    echo json_encode(['success' => false, 'message' => 'Invalid OTP format. Please enter the 6-digit code from your email.']);
    exit;
}

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please restart the verification process.']);
    exit;
}

// ── Session-based fallback check (instant) ────────────────────────────────────
$sess_key   = 'otp_code_' . $purpose;
$sess_exp   = 'otp_expires_' . $purpose;
$sess_uid   = 'otp_user_id_' . $purpose;

if (isset($_SESSION[$sess_key]) && (int)$_SESSION[$sess_uid] === $user_id) {
    if (strtotime($_SESSION[$sess_exp]) > time()) {
        if ($_SESSION[$sess_key] === $submitted_otp) {
            _mark_otp_used($conn, $user_id, $purpose);
            _clear_otp_session($purpose);
            echo json_encode(['success' => true, 'message' => 'OTP verified successfully!', 'user_id' => $user_id]);
            exit;
        }
    }
}

// ── Database-based verification ───────────────────────────────────────────────
$purpose_clean = mysqli_real_escape_string($conn, $purpose);
$res = mysqli_query($conn, "SELECT id, otp_code, expires_at FROM admin_otps 
    WHERE user_id = $user_id 
    AND purpose = '$purpose_clean'
    AND is_used = 0 
    AND expires_at > NOW()
    ORDER BY id DESC LIMIT 1");

if (!$res || mysqli_num_rows($res) === 0) {
    echo json_encode(['success' => false, 'message' => 'OTP has expired or is invalid. Please request a new one.']);
    exit;
}

$row = mysqli_fetch_assoc($res);

// Verify using password_verify (hashed OTPs)
$verified = false;
if (password_verify($submitted_otp, $row['otp_code'])) {
    $verified = true;
} elseif ($row['otp_code'] === $submitted_otp) {
    // Fallback: plain text OTP (older records)
    $verified = true;
}

if (!$verified) {
    echo json_encode(['success' => false, 'message' => 'Incorrect OTP. Please check your email and try again.']);
    exit;
}

// Mark OTP as used
_mark_otp_used($conn, $user_id, $purpose);
_clear_otp_session($purpose);

echo json_encode([
    'success' => true,
    'message' => 'OTP verified successfully!',
    'user_id' => $user_id
]);

// ── Helpers ───────────────────────────────────────────────────────────────────
function _mark_otp_used($conn, $user_id, $purpose) {
    $uid = (int)$user_id;
    $p   = mysqli_real_escape_string($conn, $purpose);
    mysqli_query($conn, "UPDATE admin_otps SET is_used = 1 WHERE user_id = $uid AND purpose = '$p' AND is_used = 0");
}

function _clear_otp_session($purpose) {
    unset(
        $_SESSION['otp_code_' . $purpose],
        $_SESSION['otp_expires_' . $purpose],
        $_SESSION['otp_user_id_' . $purpose],
        $_SESSION['otp_pending_user_id']
    );
}
