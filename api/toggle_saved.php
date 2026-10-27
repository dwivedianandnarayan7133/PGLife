<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/database_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(array('success' => false, 'is_saved' => false, 'message' => 'Please log in to save properties.'));
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$property_id = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;

if ($property_id <= 0) {
    echo json_encode(array('success' => false, 'message' => 'Invalid property ID.'));
    exit;
}

$check_res = mysqli_query($conn, "SELECT id FROM saved_properties WHERE user_id = $user_id AND property_id = $property_id");

if (mysqli_num_rows($check_res) > 0) {
    // Unsave
    mysqli_query($conn, "DELETE FROM saved_properties WHERE user_id = $user_id AND property_id = $property_id");
    echo json_encode(array('success' => true, 'is_saved' => false, 'message' => 'Removed from saved properties.'));
} else {
    // Save
    mysqli_query($conn, "INSERT INTO saved_properties (user_id, property_id) VALUES ($user_id, $property_id)");
    echo json_encode(array('success' => true, 'is_saved' => true, 'message' => 'Property saved to your bookmarks!'));
}
