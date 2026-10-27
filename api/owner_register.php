<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/database_connect.php';
require_once __DIR__ . '/../includes/notification_helper.php';

// 0. CAPTCHA Validation
$user_captcha = isset($_POST['captcha']) ? strtolower(trim($_POST['captcha'])) : '';
$session_captcha = isset($_SESSION['captcha_code']) ? strtolower($_SESSION['captcha_code']) : '';
if (empty($user_captcha) || $user_captcha !== $session_captcha) {
    echo json_encode(array("success" => false, "message" => "Security verification failed! Invalid CAPTCHA code. Please try again."));
    exit;
}

// 0b. Terms & Conditions Acceptance Validation
if (!isset($_POST['tc_accepted']) || $_POST['tc_accepted'] !== '1') {
    echo json_encode(array("success" => false, "message" => "You must read and accept the PGLife Terms & Conditions before submitting registration."));
    exit;
}

$full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));

$raw_phone = trim($_POST['phone']);
$clean_phone = preg_replace('/[^0-9]/', '', $raw_phone);
if (strlen($clean_phone) >= 10) {
    $clean_phone = substr($clean_phone, -10);
}
$phone = mysqli_real_escape_string($conn, $clean_phone);

$raw_email = trim($_POST['email']);
$email = mysqli_real_escape_string($conn, strtolower($raw_email));

$password = trim($_POST['password']);
$property_name = mysqli_real_escape_string($conn, trim($_POST['property_name']));
$city_id = (int)$_POST['city_id'];
$property_address = mysqli_real_escape_string($conn, trim($_POST['property_address']));
$bank_account = isset($_POST['bank_account']) ? mysqli_real_escape_string($conn, trim($_POST['bank_account'])) : '';

if (empty($email) || empty($password) || empty($full_name) || empty($phone) || strlen($phone) !== 10) {
    echo json_encode(array('success' => false, 'message' => 'Please enter a valid 10-digit mobile number and fill in all mandatory fields.'));
    exit;
}

// Check if email already exists
$check_res = mysqli_query($conn, "SELECT id FROM users WHERE LOWER(email) = '$email'");
if (mysqli_num_rows($check_res) > 0) {
    echo json_encode(array('success' => false, 'message' => "The email address '$raw_email' is already registered with us. Please login instead!"));
    exit;
}

// Check if phone already exists
$check_phone = mysqli_query($conn, "SELECT id FROM users WHERE phone = '$phone'");
if (mysqli_num_rows($check_phone) > 0) {
    echo json_encode(array('success' => false, 'message' => "The mobile number '$phone' is already registered to an existing account. Please login or use a different phone number!"));
    exit;
}

// Handle Govt ID upload
$govt_doc_path = '';
if (isset($_FILES['govt_id_doc']) && $_FILES['govt_id_doc']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/../img/docs/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    $ext = pathinfo($_FILES['govt_id_doc']['name'], PATHINFO_EXTENSION);
    $filename = 'govt_id_' . time() . '_' . rand(100, 999) . '.' . $ext;
    if (move_uploaded_file($_FILES['govt_id_doc']['tmp_name'], $upload_dir . $filename)) {
        $govt_doc_path = 'img/docs/' . $filename;
    }
}

// Handle Property Doc upload
$prop_doc_path = '';
if (isset($_FILES['property_doc']) && $_FILES['property_doc']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/../img/docs/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    $ext = pathinfo($_FILES['property_doc']['name'], PATHINFO_EXTENSION);
    $filename = 'prop_doc_' . time() . '_' . rand(100, 999) . '.' . $ext;
    if (move_uploaded_file($_FILES['property_doc']['tmp_name'], $upload_dir . $filename)) {
        $prop_doc_path = 'img/docs/' . $filename;
    }
}

// Handle Business License
$biz_doc_path = '';
if (isset($_FILES['business_license_doc']) && $_FILES['business_license_doc']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/../img/docs/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    $ext = pathinfo($_FILES['business_license_doc']['name'], PATHINFO_EXTENSION);
    $filename = 'biz_doc_' . time() . '_' . rand(100, 999) . '.' . $ext;
    if (move_uploaded_file($_FILES['business_license_doc']['tmp_name'], $upload_dir . $filename)) {
        $biz_doc_path = 'img/docs/' . $filename;
    }
}

$pass_hash = sha1($password);
// Insert User with role = 'owner' and id_verified = 'pending'
$sql_user = "INSERT INTO users (email, password, full_name, phone, gender, college_name, role, id_proof_doc, id_verified) 
             VALUES ('$email', '$pass_hash', '$full_name', '$phone', 'male', 'PG Owner', 'owner', '$govt_doc_path', 'pending')";

if (mysqli_query($conn, $sql_user)) {
    $new_owner_id = mysqli_insert_id($conn);

    // Insert verification record
    $sql_ver = "INSERT INTO owner_verifications (owner_id, govt_id_doc, business_license_doc, property_doc, bank_account, status) 
                VALUES ($new_owner_id, '$govt_doc_path', '$biz_doc_path', '$prop_doc_path', '$bank_account', 'pending')";
    mysqli_query($conn, $sql_ver);

    // Insert pending property
    $sql_prop = "INSERT INTO properties (city_id, owner_id, name, address, description, gender, rent, rating_clean, rating_food, rating_safety, status, is_published) 
                 VALUES ($city_id, $new_owner_id, '$property_name', '$property_address', 'New owner submitted PG property.', 'unisex', 9500, 4.5, 4.2, 4.8, 'pending', 0)";
    mysqli_query($conn, $sql_prop);

    // Notify Super Admin
    $admin_res = mysqli_query($conn, "SELECT id FROM users WHERE role = 'admin'");
    while ($admin = mysqli_fetch_assoc($admin_res)) {
        create_notification($admin['id'], 'owner_registration', 'New Property Owner Application', "Owner $full_name ($email) submitted registration. Pending review.", 'admin_portal.php?tab=owners');
    }

    // Dispatch Registration Confirmation Notification & SMS/Email Alert to Owner
    create_notification(
        $new_owner_id,
        'owner_registration_welcome',
        'Property Owner Registration Submitted',
        "Hello $full_name, your owner registration for '$property_name' has been received. Your registration is currently under Super Admin review.",
        'owner_dashboard.php'
    );

    echo json_encode(array(
        'success' => true, 
        'message' => 'Registration submitted successfully! Your owner account is currently pending Super Admin review & approval before activation.'
    ));
} else {
    echo json_encode(array('success' => false, 'message' => 'Database error: ' . mysqli_error($conn)));
}
