<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database_connect.php';

function get_logged_in_user() {
    global $conn;
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $user_id = (int)$_SESSION['user_id'];
    $sql = "SELECT id, email, full_name, phone, gender, college_name, role, profile_image, address, id_proof_doc, id_verified, notification_prefs FROM users WHERE id = $user_id";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php?error=" . urlencode("Please log in to access this page."));
        exit;
    }
}

function check_role($allowed_roles = array('user')) {
    check_login();
    $user = get_logged_in_user();
    if (!$user) {
        session_destroy();
        header("Location: index.php?error=" . urlencode("Session expired. Please log in again."));
        exit;
    }
    
    if (!in_array($user['role'], (array)$allowed_roles)) {
        header("Location: index.php?error=" . urlencode("Access Denied: You do not have permission to view this section."));
        exit;
    }
    return $user;
}
