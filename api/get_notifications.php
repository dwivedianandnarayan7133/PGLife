<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/database_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(array('success' => false, 'unread_count' => 0, 'notifications' => array()));
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action = isset($_GET['action']) ? $_GET['action'] : 'fetch';

if ($action === 'mark_read') {
    mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE user_id = $user_id");
    echo json_encode(array('success' => true));
    exit;
}

$sql = "SELECT id, type, title, message, link, is_read, created_at FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 15";
$res = mysqli_query($conn, $sql);
$list = array();
$unread = 0;

if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        if (!$row['is_read']) $unread++;
        $list[] = $row;
    }
}

echo json_encode(array('success' => true, 'unread_count' => $unread, 'notifications' => $list));
