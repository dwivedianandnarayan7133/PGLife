<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/database_connect.php';
require_once __DIR__ . '/../includes/rbac.php';
require_once __DIR__ . '/../includes/notification_helper.php';

$user = check_role(array('owner', 'admin'));
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : 'get');

if ($action === 'update_status') {
    $lead_id = (int)$_POST['lead_id'];
    $new_status = mysqli_real_escape_string($conn, trim($_POST['status']));
    $notes = isset($_POST['notes']) ? mysqli_real_escape_string($conn, trim($_POST['notes'])) : '';

    $valid_statuses = array('New','Contacted','Seat Reserved','Documents Pending','Booking Confirmed','Booking Cancelled','Not Interested','Closed');
    if (!in_array($new_status, $valid_statuses)) {
        echo json_encode(array('success' => false, 'message' => 'Invalid lead status value.'));
        exit;
    }

    $sql = "UPDATE interested_users_properties SET lead_status = '$new_status', notes = '$notes' WHERE id = $lead_id";
    if (mysqli_query($conn, $sql)) {
        // Fetch lead metadata for audit notification
        $lead_res = mysqli_query($conn, "SELECT iup.*, u.full_name, p.name AS property_name FROM interested_users_properties iup INNER JOIN users u ON iup.user_id = u.id INNER JOIN properties p ON iup.property_id = p.id WHERE iup.id = $lead_id");
        if ($lead = mysqli_fetch_assoc($lead_res)) {
            create_notification($lead['user_id'], 'lead_update', 'Lead Status Updated', "Your interest in {$lead['property_name']} is now: $new_status.", 'profile.php');
            log_audit($user['id'], 'lead_status_update', "Updated Lead #$lead_id status to $new_status");
        }
        echo json_encode(array('success' => true, 'message' => "Lead status updated to $new_status."));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Database update error.'));
    }
    exit;
}

if ($action === 'convert_to_booking') {
    $lead_id = (int)$_POST['lead_id'];
    $lead_res = mysqli_query($conn, "SELECT * FROM interested_users_properties WHERE id = $lead_id");
    if (!$lead_res || mysqli_num_rows($lead_res) === 0) {
        echo json_encode(array('success' => false, 'message' => 'Lead record not found.'));
        exit;
    }

    $lead = mysqli_fetch_assoc($lead_res);
    $user_id = $lead['user_id'];
    $property_id = $lead['property_id'];
    $room_type = $lead['room_preference'];
    $move_in = $lead['move_in_date'];

    // Insert into bookings table
    $sql_bk = "INSERT INTO bookings (user_id, property_id, room_type, status, move_in_date, notes) 
               VALUES ($user_id, $property_id, '$room_type', 'Seat Reserved', '$move_in', 'Converted from Interested Lead #$lead_id')";
    if (mysqli_query($conn, $sql_bk)) {
        $booking_id = mysqli_insert_id($conn);
        // Mark lead as 'Booking Confirmed'
        mysqli_query($conn, "UPDATE interested_users_properties SET lead_status = 'Booking Confirmed' WHERE id = $lead_id");

        // Decrement available seats in property
        mysqli_query($conn, "UPDATE properties SET reserved_seats = reserved_seats + 1, available_seats = GREATEST(0, available_seats - 1) WHERE id = $property_id");

        create_notification($user_id, 'booking_created', 'Seat Reserved!', "A seat has been reserved for you! Booking #BK-$booking_id.", 'profile.php');
        log_audit($user['id'], 'convert_lead_booking', "Converted Lead #$lead_id to Booking #BK-$booking_id");

        echo json_encode(array('success' => true, 'booking_id' => $booking_id, 'message' => "Lead converted to Booking #BK-$booking_id successfully!"));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to create booking.'));
    }
    exit;
}

if ($action === 'get') {
    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
    $status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, trim($_GET['status'])) : '';
    $prop_filter = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;

    $where = "WHERE 1=1";
    if ($user['role'] === 'owner') {
        $where .= " AND p.owner_id = {$user['id']}";
    }
    if ($search !== '') {
        $where .= " AND (u.full_name LIKE '%$search%' OR u.phone LIKE '%$search%' OR u.email LIKE '%$search%')";
    }
    if ($status_filter !== '') {
        $where .= " AND iup.lead_status = '$status_filter'";
    }
    if ($prop_filter > 0) {
        $where .= " AND iup.property_id = $prop_filter";
    }

    $sql = "SELECT iup.*, u.full_name, u.phone, u.email, u.gender AS user_gender, p.name AS property_name, p.owner_id, o.full_name AS owner_name 
            FROM interested_users_properties iup 
            INNER JOIN users u ON iup.user_id = u.id 
            INNER JOIN properties p ON iup.property_id = p.id 
            LEFT JOIN users o ON p.owner_id = o.id 
            $where ORDER BY iup.id DESC";
    $res = mysqli_query($conn, $sql);
    $leads = array();
    while ($row = mysqli_fetch_assoc($res)) {
        $leads[] = $row;
    }
    echo json_encode(array('success' => true, 'leads' => $leads));
    exit;
}
