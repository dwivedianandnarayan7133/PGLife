<?php
session_start();
require '../includes/database_connect.php';
require '../includes/auth.php';
require '../includes/notifications_helper.php';

header('Access-Control-Allow-Origin: *');

if (!is_logged_in()) {
    json_response(array('success' => false, 'message' => 'Please login to book a PG.'));
}

$user_id = (int) $_SESSION['user_id'];
$property_id = (int) ($_POST['property_id'] ?? 0);
$move_in_date = trim($_POST['move_in_date'] ?? '');

if ($property_id <= 0) {
    json_response(array('success' => false, 'message' => 'Invalid property.'));
}

$stmt = mysqli_prepare($conn, 'SELECT id, name, owner_id, status FROM properties WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $property_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$property = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$property || $property['status'] !== 'approved') {
    json_response(array('success' => false, 'message' => 'This PG is not available for booking.'));
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT id FROM bookings WHERE user_id = ? AND property_id = ? AND status IN ('pending','owner_approved','admin_review','confirmed')"
);
mysqli_stmt_bind_param($stmt, 'ii', $user_id, $property_id);
mysqli_stmt_execute($stmt);
$existing = mysqli_stmt_get_result($stmt);
if (mysqli_num_rows($existing) > 0) {
    mysqli_stmt_close($stmt);
    json_response(array('success' => false, 'message' => 'You already have an active booking for this PG.'));
}
mysqli_stmt_close($stmt);

$move_in_sql = $move_in_date !== '' ? $move_in_date : null;
$stmt = mysqli_prepare(
    $conn,
    'INSERT INTO bookings (user_id, property_id, move_in_date, status) VALUES (?, ?, ?, ?)'
);
$status = 'pending';
mysqli_stmt_bind_param($stmt, 'iiss', $user_id, $property_id, $move_in_sql, $status);
$ok = mysqli_stmt_execute($stmt);
$booking_id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

if (!$ok) {
    json_response(array('success' => false, 'message' => 'Booking request failed.'));
}

if (!empty($property['owner_id'])) {
    create_notification(
        $conn,
        $property['owner_id'],
        'booking',
        'New booking request',
        $_SESSION['full_name'] . ' requested to book ' . $property['name'] . '.',
        'owner/bookings.php'
    );
}

notify_admins_new_booking($conn, $booking_id, $property['name'], $_SESSION['full_name']);

json_response(array(
    'success' => true,
    'message' => 'Booking request submitted. Owner and admin will review your request.',
    'booking_id' => $booking_id
));
