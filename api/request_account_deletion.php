<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/database_connect.php';
require_once __DIR__ . '/../includes/notification_helper.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(array('success' => false, 'message' => 'Unauthorized: Please log in to request account deletion.'));
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$reason = isset($_POST['reason']) ? mysqli_real_escape_string($conn, trim($_POST['reason'])) : '';

if (empty($reason)) {
    echo json_encode(array('success' => false, 'message' => 'Please provide a valid reason for your account deletion request.'));
    exit;
}

// Check current user status
$res = mysqli_query($conn, "SELECT full_name, email, role, deletion_status FROM users WHERE id = $user_id");
if (!$res || mysqli_num_rows($res) === 0) {
    echo json_encode(array('success' => false, 'message' => 'User account not found.'));
    exit;
}

$user = mysqli_fetch_assoc($res);
if ($user['deletion_status'] === 'pending_approval') {
    echo json_encode(array('success' => false, 'message' => 'You already have an account deletion request pending Super Admin review.'));
    exit;
}

if ($user['deletion_status'] === 'approved') {
    echo json_encode(array('success' => false, 'message' => 'Your account deletion has already been approved and deactivated.'));
    exit;
}

// Update deletion status to pending_approval
$now = date('Y-m-d H:i:s');
$sql = "UPDATE users SET deletion_status = 'pending_approval', deletion_reason = '$reason', deletion_requested_at = '$now' WHERE id = $user_id";

if (mysqli_query($conn, $sql)) {
    // Notify Super Admin
    $admin_res = mysqli_query($conn, "SELECT id FROM users WHERE role = 'admin'");
    while ($admin = mysqli_fetch_assoc($admin_res)) {
        create_notification(
            $admin['id'],
            'deletion_request',
            '⚠️ Account Deletion Request Received',
            "User {$user['full_name']} ({$user['email']}, Role: {$user['role']}) submitted an account deletion request. Reason: $reason",
            'admin_portal.php?tab=deletion'
        );
    }

    log_audit_action($user_id, 'account_deletion_requested', "Reason: $reason");

    echo json_encode(array(
        'success' => true,
        'message' => 'Your account deletion request has been submitted successfully to Super Admin for review. Your account data will be preserved as per platform policy.'
    ));
} else {
    echo json_encode(array('success' => false, 'message' => 'Database error: ' . mysqli_error($conn)));
}
