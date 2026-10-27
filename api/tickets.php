<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/database_connect.php';
require_once __DIR__ . '/../includes/notification_helper.php';

$action = isset($_POST['action']) ? $_POST['action'] : 'create';

if ($action !== 'request_callback' && !isset($_SESSION['user_id'])) {
    echo json_encode(array('success' => false, 'message' => 'Please login to submit support tickets.'));
    exit;
}

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;


// 1. Standard Ticket Creation
if ($action === 'create') {
    $ticket_type = mysqli_real_escape_string($conn, trim($_POST['ticket_type']));
    $subject = mysqli_real_escape_string($conn, trim($_POST['subject']));
    $message = mysqli_real_escape_string($conn, trim($_POST['message']));
    $property_id = isset($_POST['property_id']) && $_POST['property_id'] > 0 ? (int)$_POST['property_id'] : 'NULL';

    $sql = "INSERT INTO support_tickets (user_id, property_id, ticket_type, subject, message, status) 
            VALUES ($user_id, $property_id, '$ticket_type', '$subject', '$message', 'Open')";

    if (mysqli_query($conn, $sql)) {
        $ticket_id = mysqli_insert_id($conn);

        // Notify Super Admin
        $admin_res = mysqli_query($conn, "SELECT id FROM users WHERE role = 'admin'");
        while ($admin = mysqli_fetch_assoc($admin_res)) {
            create_notification($admin['id'], 'ticket_new', 'New Support Ticket Created', "Ticket #$ticket_id ($ticket_type): $subject", 'admin_portal.php?tab=tickets');
        }

        echo json_encode(array('success' => true, 'ticket_id' => $ticket_id, 'message' => "Support ticket #$ticket_id created successfully! Support team will update you shortly."));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to create support ticket.'));
    }
    exit;
}

// 2. Request Instant Callback Ticket
if ($action === 'request_callback') {
    $raw_name = isset($_POST['user_name']) ? trim($_POST['user_name']) : (isset($_POST['name']) ? trim($_POST['name']) : (isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Guest User'));
    $raw_type = isset($_POST['user_type']) ? trim($_POST['user_type']) : 'Student Resident';
    $raw_phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $raw_slot = isset($_POST['time_slot']) ? trim($_POST['time_slot']) : '';
    $raw_topic = isset($_POST['topic']) ? trim($_POST['topic']) : '';

    $phone = mysqli_real_escape_string($conn, $raw_phone);
    $time_slot = mysqli_real_escape_string($conn, $raw_slot);
    $topic = mysqli_real_escape_string($conn, $raw_topic);
    $user_name = mysqli_real_escape_string($conn, $raw_name);
    $user_type = mysqli_real_escape_string($conn, $raw_type);

    $raw_subject = "Instant Callback Request ($raw_type) - $raw_name";
    $raw_message = "Name: $raw_name | User Type: $raw_type | Phone: $raw_phone | Preferred Slot: $raw_slot | Topic: $raw_topic";

    $subject = mysqli_real_escape_string($conn, $raw_subject);
    $message = mysqli_real_escape_string($conn, $raw_message);

    $sql = "INSERT INTO support_tickets (user_id, ticket_type, subject, message, status) 
            VALUES ($user_id, 'other', '$subject', '$message', 'Open')";

    if (mysqli_query($conn, $sql)) {
        $ticket_id = mysqli_insert_id($conn);

        // Notify Super Admin & Owners
        $admin_res = mysqli_query($conn, "SELECT id FROM users WHERE role = 'admin'");
        if ($admin_res) {
            while ($admin = mysqli_fetch_assoc($admin_res)) {
                create_notification($admin['id'], 'callback_request', '📞 New Callback Request', "$raw_name ($raw_type - $raw_phone) requested callback for: $raw_topic", 'admin_portal.php?tab=tickets');
            }
        }

        echo json_encode(array('success' => true, 'ticket_id' => $ticket_id, 'message' => "Callback request #$ticket_id submitted successfully! Our housing advisor will call you shortly."));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to submit callback request: ' . mysqli_error($conn)));
    }
    exit;
}

// 3. Update Ticket Status
if ($action === 'update_status') {
    $ticket_id = (int)$_POST['ticket_id'];
    $status = mysqli_real_escape_string($conn, trim($_POST['status']));
    $admin_notes = isset($_POST['admin_notes']) ? mysqli_real_escape_string($conn, trim($_POST['admin_notes'])) : '';

    $sql = "UPDATE support_tickets SET status = '$status', admin_notes = '$admin_notes' WHERE id = $ticket_id";
    if (mysqli_query($conn, $sql)) {
        $t_res = mysqli_query($conn, "SELECT user_id, subject FROM support_tickets WHERE id = $ticket_id");
        $t = mysqli_fetch_assoc($t_res);
        create_notification($t['user_id'], 'ticket_update', 'Support Ticket Status Updated', "Ticket #$ticket_id status is now: $status.", 'profile.php?tab=tickets');

        echo json_encode(array('success' => true, 'message' => "Ticket #$ticket_id status updated to $status."));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to update ticket status.'));
    }
    exit;
}

echo json_encode(array('success' => false, 'message' => 'Invalid ticket action request.'));
