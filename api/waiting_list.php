<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/database_connect.php';
require_once __DIR__ . '/../includes/notification_helper.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(array('success' => false, 'message' => 'Please login to join the waiting list.'));
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action = isset($_POST['action']) ? $_POST['action'] : 'join';

if ($action === 'join') {
    $property_id = (int)$_POST['property_id'];
    $preferred_move_in = mysqli_real_escape_string($conn, trim($_POST['preferred_move_in']));
    $room_type = mysqli_real_escape_string($conn, trim($_POST['requested_room_type']));

    $u_res = mysqli_query($conn, "SELECT full_name, phone, email FROM users WHERE id = $user_id");
    $user = mysqli_fetch_assoc($u_res);

    $p_res = mysqli_query($conn, "SELECT name, owner_id FROM properties WHERE id = $property_id");
    $prop = mysqli_fetch_assoc($p_res);

    // Check if user is already on waiting list for this property
    $check = mysqli_query($conn, "SELECT id FROM waiting_list WHERE property_id = $property_id AND user_id = $user_id AND status = 'waiting'");
    if (mysqli_num_rows($check) > 0) {
        echo json_encode(array('success' => false, 'message' => 'You are already on the waiting list for this property.'));
        exit;
    }

    $sql = "INSERT INTO waiting_list (property_id, user_id, full_name, phone, email, preferred_move_in, requested_room_type, status) 
            VALUES ($property_id, $user_id, '{$user['full_name']}', '{$user['phone']}', '{$user['email']}', '$preferred_move_in', '$room_type', 'waiting')";

    if (mysqli_query($conn, $sql)) {
        $wl_id = mysqli_insert_id($conn);

        // Notify Owner
        if ($prop['owner_id']) {
            create_notification($prop['owner_id'], 'waitlist_new', 'New Waiting List Entry', "{$user['full_name']} joined waiting list for {$prop['name']}.", 'owner_dashboard.php?tab=waitlist');
        }

        echo json_encode(array('success' => true, 'message' => "You have successfully joined the waiting list! You will be notified as soon as a seat becomes available."));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to join waiting list.'));
    }
    exit;
}
