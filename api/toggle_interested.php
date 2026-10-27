<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/database_connect.php';
require_once __DIR__ . '/../includes/notification_helper.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(array('success' => false, 'message' => 'Please login to mark interest in properties.'));
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$property_id = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;
$move_in_date = isset($_POST['move_in_date']) ? mysqli_real_escape_string($conn, trim($_POST['move_in_date'])) : date('Y-m-d', strtotime('+7 days'));
$room_pref = isset($_POST['room_preference']) ? mysqli_real_escape_string($conn, trim($_POST['room_preference'])) : 'Single';
$budget = isset($_POST['budget']) ? (int)$_POST['budget'] : 10000;
$notes = isset($_POST['notes']) ? mysqli_real_escape_string($conn, trim($_POST['notes'])) : '';

if ($property_id <= 0) {
    echo json_encode(array('success' => false, 'message' => 'Invalid property ID.'));
    exit;
}

// Fetch User & Property metadata
$u_res = mysqli_query($conn, "SELECT full_name, phone, email FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($u_res);

$p_res = mysqli_query($conn, "SELECT name, owner_id FROM properties WHERE id = $property_id");
$property = mysqli_fetch_assoc($p_res);

if (!$property) {
    echo json_encode(array('success' => false, 'message' => 'Property not found.'));
    exit;
}

// Check existing interest record
$check = mysqli_query($conn, "SELECT id, lead_status FROM interested_users_properties WHERE user_id = $user_id AND property_id = $property_id");
if (mysqli_num_rows($check) > 0) {
    $existing = mysqli_fetch_assoc($check);
    // Toggle off if already interested or update lead info
    mysqli_query($conn, "DELETE FROM interested_users_properties WHERE id = {$existing['id']}");
    echo json_encode(array('success' => true, 'is_interested' => false, 'message' => 'Property removed from your interested list.'));
    exit;
} else {
    // Insert new lead
    $sql_lead = "INSERT INTO interested_users_properties (user_id, property_id, move_in_date, room_preference, budget, lead_status, notes) 
                 VALUES ($user_id, $property_id, '$move_in_date', '$room_pref', $budget, 'New', '$notes')";
    mysqli_query($conn, $sql_lead);
    $lead_id = mysqli_insert_id($conn);

    // Notify Property Owner
    if ($property['owner_id']) {
        $notif_msg = "New Lead! {$user['full_name']} ({$user['phone']}) expressed interest in {$property['name']}. Move-in: $move_in_date, Budget: ₹$budget.";
        create_notification($property['owner_id'], 'lead_new', 'New Interested Student Lead', $notif_msg, 'owner_dashboard.php?tab=interested');
    }

    // Notify Super Admin
    $admin_res = mysqli_query($conn, "SELECT id FROM users WHERE role = 'admin'");
    while ($admin = mysqli_fetch_assoc($admin_res)) {
        create_notification($admin['id'], 'lead_new', 'Platform Lead Created', "Lead #$lead_id created for {$property['name']} by {$user['full_name']}.", 'admin_portal.php?tab=leads');
    }

    echo json_encode(array(
        'success' => true, 
        'is_interested' => true, 
        'lead_id' => $lead_id,
        'message' => 'Your interest has been submitted! The property owner will contact you shortly.'
    ));
}
