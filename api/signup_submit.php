<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect direct GET browser requests back to index.php
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . "/../includes/database_connect.php";
require_once __DIR__ . "/../includes/notification_helper.php";

header('Content-Type: application/json');

// 0. CAPTCHA Validation
$user_captcha = isset($_POST['captcha']) ? strtolower(trim($_POST['captcha'])) : '';
$session_captcha = isset($_SESSION['captcha_code']) ? strtolower($_SESSION['captcha_code']) : '';
if (empty($user_captcha) || $user_captcha !== $session_captcha) {
    echo json_encode(array("success" => false, "message" => "Security verification failed! Invalid CAPTCHA code. Please try again."));
    exit;
}

// 0b. Terms & Conditions Acceptance Validation
if (!isset($_POST['tc_accepted']) || $_POST['tc_accepted'] !== '1') {
    echo json_encode(array("success" => false, "message" => "You must read and accept the PGLife Terms & Conditions before registering."));
    exit;
}

$full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
$raw_email = trim($_POST['email']);
$email = mysqli_real_escape_string($conn, strtolower($raw_email));

$raw_phone = trim($_POST['phone']);
$clean_phone = preg_replace('/[^0-9]/', '', $raw_phone);
if (strlen($clean_phone) >= 10) {
    $clean_phone = substr($clean_phone, -10);
}
$phone = mysqli_real_escape_string($conn, $clean_phone);

$password = trim($_POST['password']);
$pass_hash = sha1($password);
$college_name = mysqli_real_escape_string($conn, trim($_POST['college_name']));
$gender = isset($_POST['gender']) ? mysqli_real_escape_string($conn, trim($_POST['gender'])) : 'unisex';

if (empty($full_name) || empty($email) || empty($password) || empty($phone) || strlen($phone) !== 10) {
    echo json_encode(array("success" => false, "message" => "Please enter a valid 10-digit mobile number and fill in all mandatory fields."));
    exit;
}

// 1. Check if Email is already registered
$email_check = mysqli_query($conn, "SELECT id FROM users WHERE LOWER(email) = '$email'");
if ($email_check && mysqli_num_rows($email_check) > 0) {
    echo json_encode(array(
        "success" => false, 
        "message" => "The email address '$raw_email' is already registered with us. Please login instead!"
    ));
    exit;
}

// 2. Check if Mobile Phone Number is already registered
$phone_check = mysqli_query($conn, "SELECT id FROM users WHERE phone = '$phone'");
if ($phone_check && mysqli_num_rows($phone_check) > 0) {
    echo json_encode(array(
        "success" => false, 
        "message" => "The mobile number '$phone' is already registered to an existing account. Please login or use a different phone number!"
    ));
    exit;
}

// 3. Insert User Account
$sql = "INSERT INTO users (email, password, full_name, phone, gender, college_name, role, id_verified) 
        VALUES ('$email', '$pass_hash', '$full_name', '$phone', '$gender', '$college_name', 'user', 'verified')";

if (mysqli_query($conn, $sql)) {
    $new_user_id = mysqli_insert_id($conn);

    // Auto-login new student user
    $_SESSION['user_id'] = $new_user_id;
    $_SESSION['full_name'] = $full_name;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = 'user';

    // Dispatch Registration Notification & SMS/Email Outbound Alerts
    create_notification(
        $new_user_id,
        'welcome',
        'Welcome to PGLife!',
        "Hello $full_name, your student account has been created successfully! Welcome to PGLife.",
        'profile.php'
    );

    echo json_encode(array(
        "success" => true, 
        "message" => "Your account has been created successfully! Welcome to PGLife.",
        "redirect" => "profile.php"
    ));
} else {
    echo json_encode(array("success" => false, "message" => "Database error: " . mysqli_error($conn)));
}


