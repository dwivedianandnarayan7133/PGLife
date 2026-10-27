<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/database_connect.php';

$raw_input = @file_get_contents('php://input');
$data = json_decode($raw_input, true);

$user_msg = isset($data['message']) ? trim($data['message']) : '';
if (empty($user_msg) && isset($_GET['msg'])) {
    $user_msg = trim($_GET['msg']);
}

if (empty($user_msg)) {
    echo json_encode(array('success' => false, 'reply' => 'Please ask me anything! E.g. "Find hostels under 10000 in Delhi" or "Show girls PG near Delhi University".'));
    exit;
}

$msg_lower = strtolower($user_msg);
$response = array('success' => true, 'reply' => '', 'properties' => array(), 'comparison' => null);

// 1. Check FAQs
if (strpos($msg_lower, 'cancellation') !== false || strpos($msg_lower, 'refund') !== false) {
    $response['reply'] = "<strong>Cancellation Policy:</strong> Bookings can be cancelled up to 48 hours before the move-in date for a full refund. Cancellations made within 48 hours are subject to owner approval.";
    echo json_encode($response);
    exit;
}
if (strpos($msg_lower, 'deposit') !== false || strpos($msg_lower, 'security') !== false) {
    $response['reply'] = "<strong>Security Deposit:</strong> Most PGs require 1 month's rent as a security deposit, which is 100% refundable upon move-out after checking room condition.";
    echo json_encode($response);
    exit;
}
if (strpos($msg_lower, 'food') !== false || strpos($msg_lower, 'meal') !== false || strpos($msg_lower, 'timing') !== false) {
    $response['reply'] = "<strong>Meals & Food Service:</strong> PGs listed on PGLife offer 3 meals a day (Breakfast, Lunch, Dinner) with both Veg & Non-Veg options. Breakfast is served 7:30 - 9:30 AM, Dinner 8:00 - 10:00 PM.";
    echo json_encode($response);
    exit;
}

// 2. Check Comparison Intent
if (strpos($msg_lower, 'compare') !== false) {
    // Fetch top 2 properties to compare
    $comp_sql = "SELECT p.id, p.name, p.rent, p.gender, p.address, p.rating_clean, p.rating_food, p.rating_safety, c.name AS city_name 
                FROM properties p INNER JOIN cities c ON p.city_id = c.id ORDER BY p.id ASC LIMIT 2";
    $comp_res = mysqli_query($conn, $comp_sql);
    $items = array();
    while ($r = mysqli_fetch_assoc($comp_res)) {
        $items[] = $r;
    }
    $response['reply'] = "Here is a quick side-by-side comparison of top recommendations:";
    $response['comparison'] = $items;
    echo json_encode($response);
    exit;
}

// 3. Property Search Recommendation Intent
$where_clauses = array("p.status = 'approved'");

// Budget extraction
if (preg_match('/(\d+)\s*(k|000|rs|rupees)?/i', $msg_lower, $matches)) {
    $val = (int)$matches[1];
    if ($val < 100) $val = $val * 1000;
    if ($val > 2000 && $val < 50000) {
        $where_clauses[] = "p.rent <= $val";
    }
}

// City extraction
if (strpos($msg_lower, 'delhi') !== false) $where_clauses[] = "c.name = 'Delhi'";
elseif (strpos($msg_lower, 'mumbai') !== false) $where_clauses[] = "c.name = 'Mumbai'";
elseif (strpos($msg_lower, 'bengaluru') !== false || strpos($msg_lower, 'bangalore') !== false) $where_clauses[] = "c.name = 'Bengaluru'";
elseif (strpos($msg_lower, 'hyderabad') !== false) $where_clauses[] = "c.name = 'Hyderabad'";

// Gender extraction
if (strpos($msg_lower, 'girl') !== false || strpos($msg_lower, 'female') !== false || strpos($msg_lower, 'women') !== false) {
    $where_clauses[] = "(p.gender = 'female' OR p.gender = 'unisex')";
} elseif (strpos($msg_lower, 'boy') !== false || strpos($msg_lower, 'male') !== false || strpos($msg_lower, 'men') !== false) {
    $where_clauses[] = "(p.gender = 'male' OR p.gender = 'unisex')";
}

// College / Location extraction
if (strpos($msg_lower, 'du') !== false || strpos($msg_lower, 'university') !== false || strpos($msg_lower, 'college') !== false || strpos($msg_lower, 'campus') !== false) {
    $where_clauses[] = "(p.address LIKE '%College%' OR p.address LIKE '%Campus%' OR p.address LIKE '%University%' OR p.near_college IS NOT NULL)";
}

$where_sql = implode(' AND ', $where_clauses);
$sql = "SELECT p.id, p.name, p.address, p.description, p.gender, p.rent, p.rating_clean, p.rating_food, p.rating_safety, c.name AS city_name 
        FROM properties p 
        INNER JOIN cities c ON p.city_id = c.id 
        WHERE $where_sql 
        ORDER BY p.rent ASC LIMIT 4";

$result = mysqli_query($conn, $sql);
$props = array();
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $props[] = $row;
    }
}

if (count($props) > 0) {
    $response['reply'] = "Based on your preferences, I found " . count($props) . " top matching PG recommendations:";
    $response['properties'] = $props;
} else {
    // Fallback: Return featured properties
    $fb_res = mysqli_query($conn, "SELECT p.id, p.name, p.address, p.description, p.gender, p.rent, p.rating_clean, p.rating_food, p.rating_safety, c.name AS city_name FROM properties p INNER JOIN cities c ON p.city_id = c.id ORDER BY p.rent ASC LIMIT 3");
    $fb_props = array();
    while ($r = mysqli_fetch_assoc($fb_res)) {
        $fb_props[] = $r;
    }
    $response['reply'] = "I couldn't find an exact match for that specific criteria, but here are our top student-rated accommodations:";
    $response['properties'] = $fb_props;
}

echo json_encode($response);
