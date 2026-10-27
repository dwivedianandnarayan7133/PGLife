<?php
session_start();
require '../includes/database_connect.php';
require '../includes/auth.php';

header('Access-Control-Allow-Origin: *');

if (!is_logged_in()) {
    json_response(array('success' => false, 'message' => 'Please login first.'));
}

$user_id = (int) $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';

if ($action === 'count') {
    $stmt = mysqli_prepare($conn, 'SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0');
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    json_response(array('success' => true, 'count' => (int) $row['total']));
}

if ($action === 'mark_read') {
    $notification_id = (int) ($_GET['id'] ?? 0);
    $stmt = mysqli_prepare($conn, 'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
    mysqli_stmt_bind_param($stmt, 'ii', $notification_id, $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    json_response(array('success' => true));
}

$stmt = mysqli_prepare(
    $conn,
    'SELECT id, type, title, message, link, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20'
);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$notifications = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

json_response(array('success' => true, 'notifications' => $notifications));
