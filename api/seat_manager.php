<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/database_connect.php';
require_once __DIR__ . '/../includes/rbac.php';
require_once __DIR__ . '/../includes/notification_helper.php';

$user = check_role(array('owner', 'admin'));
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : 'recalculate');

if ($action === 'update_capacity') {
    $property_id = (int)$_POST['property_id'];
    $total_rooms = (int)$_POST['total_rooms'];
    $total_beds = (int)$_POST['total_beds'];
    $total_seats = (int)$_POST['total_seats'];
    $reserved_seats = (int)$_POST['reserved_seats'];
    $occupied_seats = (int)$_POST['occupied_seats'];

    // Verify property ownership if owner
    if ($user['role'] === 'owner') {
        $check_p = mysqli_query($conn, "SELECT owner_id FROM properties WHERE id = $property_id");
        $p = mysqli_fetch_assoc($check_p);
        if (!$p || (int)$p['owner_id'] !== (int)$user['id']) {
            echo json_encode(array('success' => false, 'message' => 'Unauthorized property edit.'));
            exit;
        }
    }

    $available_seats = max(0, $total_seats - $occupied_seats - $reserved_seats);

    $sql = "UPDATE properties 
            SET total_rooms = $total_rooms, total_beds = $total_beds, total_seats = $total_seats, 
                reserved_seats = $reserved_seats, occupied_seats = $occupied_seats, available_seats = $available_seats 
            WHERE id = $property_id";

    if (mysqli_query($conn, $sql)) {
        log_audit($user['id'], 'update_capacity', "Updated property #$property_id capacity: $available_seats seats available out of $total_seats");

        // If seats became available (>0), trigger waiting list notification engine
        if ($available_seats > 0) {
            trigger_waitlist_alerts($property_id, $conn);
        }

        echo json_encode(array(
            'success' => true, 
            'available_seats' => $available_seats,
            'message' => "Capacity updated! Real-time available seats: $available_seats."
        ));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to update capacity.'));
    }
    exit;
}

function trigger_waitlist_alerts($property_id, $conn) {
    // Find top user waiting for this property
    $wl_res = mysqli_query($conn, "SELECT wl.*, p.name AS property_name FROM waiting_list wl INNER JOIN properties p ON wl.property_id = p.id WHERE wl.property_id = $property_id AND wl.status = 'waiting' ORDER BY wl.id ASC LIMIT 1");
    if ($wl = mysqli_fetch_assoc($wl_res)) {
        $wl_id = $wl['id'];
        $user_id = $wl['user_id'];
        $prop_name = $wl['property_name'];

        // Mark waitlist item as notified
        mysqli_query($conn, "UPDATE waiting_list SET status = 'notified', notified_at = NOW() WHERE id = $wl_id");

        // Notify user
        $msg = "A seat has just opened up at $prop_name! You have 24 hours to confirm your booking before the offer moves to the next person.";
        create_notification($user_id, 'waitlist_open', 'Seat Available Alert!', $msg, 'property_detail.php?property_id=' . $property_id);
    }
}
