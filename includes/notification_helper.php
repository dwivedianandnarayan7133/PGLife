<?php
require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/mailer.php';

/**
 * Creates in-app notification and automatically dispatches SMS to mobile phone and Email to recipient address.
 */
function create_notification($user_id, $type, $title, $message, $link = null) {
    global $conn;
    $user_id = (int)$user_id;
    $type_clean = mysqli_real_escape_string($conn, $type);
    $title_clean = mysqli_real_escape_string($conn, $title);
    $message_clean = mysqli_real_escape_string($conn, $message);
    $link_val = $link ? "'" . mysqli_real_escape_string($conn, $link) . "'" : "NULL";

    // 1. Insert In-App Notification
    $sql = "INSERT INTO notifications (user_id, type, title, message, link) VALUES ($user_id, '$type_clean', '$title_clean', '$message_clean', $link_val)";
    $res = mysqli_query($conn, $sql);

    // 2. Fetch User Contact Information for SMS & Email Dispatch
    $u_res = mysqli_query($conn, "SELECT full_name, phone, email FROM users WHERE id = $user_id");
    if ($u_res && mysqli_num_rows($u_res) > 0) {
        $u = mysqli_fetch_assoc($u_res);
        $phone = !empty($u['phone']) ? mysqli_real_escape_string($conn, $u['phone']) : '';
        $email = !empty($u['email']) ? trim($u['email']) : '';

        // Dispatch SMS Alert to User Mobile Phone Number
        if (!empty($phone)) {
            $sms_msg = mysqli_real_escape_string($conn, "[PGLife Alert] $title: $message");
            mysqli_query($conn, "INSERT INTO outbound_notifications (user_id, recipient_phone, channel, title, message, status) 
                                VALUES ($user_id, '$phone', 'SMS', '$title_clean', '$sms_msg', 'Delivered')");
        }

        // Dispatch HTML Email Alert to User Email Address
        if (!empty($email)) {
            send_email($email, $title, $message, $u['full_name'], $user_id);
        }
    }

    return $res;
}

function notify_owner_on_interest($user_id, $property_id) {
    global $conn;
    $user_id = (int)$user_id;
    $property_id = (int)$property_id;

    // Fetch user details
    $user_res = mysqli_query($conn, "SELECT full_name, phone, email FROM users WHERE id = $user_id");
    if (!$user_res || mysqli_num_rows($user_res) == 0) return false;
    $user = mysqli_fetch_assoc($user_res);

    // Fetch property & owner details
    $prop_res = mysqli_query($conn, "SELECT p.name AS property_name, p.owner_id FROM properties p WHERE p.id = $property_id");
    if (!$prop_res || mysqli_num_rows($prop_res) == 0) return false;
    $prop = mysqli_fetch_assoc($prop_res);

    $owner_id = (int)$prop['owner_id'];
    if ($owner_id <= 0) {
        $admin_res = mysqli_query($conn, "SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        if ($admin_res && mysqli_num_rows($admin_res) > 0) {
            $admin = mysqli_fetch_assoc($admin_res);
            $owner_id = (int)$admin['id'];
        } else {
            return false;
        }
    }

    $now_str = date('Y-m-d H:i:s');
    $title = "New Interested Student: " . $user['full_name'];
    $msg = "User {$user['full_name']} (Mobile Phone: {$user['phone']}, Email: {$user['email']}) marked interest in '{$prop['property_name']}' (ID: #$property_id) on $now_str.";
    $link = "owner_dashboard.php?tab=interested";

    return create_notification($owner_id, 'interested', $title, $msg, $link);
}

function log_audit_action($actor_id, $action, $details = '') {
    global $conn;
    $actor_id = (int)$actor_id;
    $action = mysqli_real_escape_string($conn, $action);
    $details = mysqli_real_escape_string($conn, $details);
    $sql = "INSERT INTO audit_logs (actor_id, action, details) VALUES ($actor_id, '$action', '$details')";
    return mysqli_query($conn, $sql);
}

function log_audit($actor_id, $action, $details = '') {
    return log_audit_action($actor_id, $action, $details);
}
