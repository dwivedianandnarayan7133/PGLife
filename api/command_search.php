<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/database_connect.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

$q_lower = strtolower($q);
$results = array();

// Preset commands matching
if (empty($q_lower)) {
    $results[] = array('title' => 'Find hostel under ₹8,000', 'type' => 'filter', 'url' => 'property_list.php?city=Delhi&max_rent=8000', 'icon' => 'fa-tag');
    $results[] = array('title' => 'Show girls PG near University', 'type' => 'filter', 'url' => 'property_list.php?city=Delhi&gender=female', 'icon' => 'fa-venus');
    $results[] = array('title' => 'Hostels with AC', 'type' => 'filter', 'url' => 'property_list.php?city=Delhi&amenity=AC', 'icon' => 'fa-snowflake');
    $results[] = array('title' => 'Hostels with WiFi', 'type' => 'filter', 'url' => 'property_list.php?city=Delhi&amenity=Wifi', 'icon' => 'fa-wifi');
    $results[] = array('title' => 'Pet-friendly hostels', 'type' => 'filter', 'url' => 'property_list.php?city=Delhi&pet=1', 'icon' => 'fa-paw');
    if ($user_id > 0) {
        $results[] = array('title' => 'My Saved & Bookmarked PGs', 'type' => 'action', 'url' => 'profile.php?tab=saved', 'icon' => 'fa-bookmark');
        $results[] = array('title' => 'My Bookings History', 'type' => 'action', 'url' => 'profile.php?tab=bookings', 'icon' => 'fa-calendar-check');
    }
} else {
    // Dynamic Query Matching
    $q_escaped = mysqli_real_escape_string($conn, $q_lower);
    
    // Check for budget in query
    $max_rent = 0;
    if (preg_match('/(\d+)/', $q_lower, $m)) {
        $val = (int)$m[1];
        if ($val < 100) $val = $val * 1000;
        if ($val >= 2000 && $val <= 50000) $max_rent = $val;
    }

    $where = array("p.status = 'approved'");
    if ($max_rent > 0) {
        $where[] = "p.rent <= $max_rent";
    } else {
        $where[] = "(LOWER(p.name) LIKE '%$q_escaped%' OR LOWER(p.address) LIKE '%$q_escaped%' OR LOWER(c.name) LIKE '%$q_escaped%')";
    }

    $where_sql = implode(' AND ', $where);
    $sql = "SELECT p.id, p.name, p.rent, p.address, p.gender, c.name AS city_name 
            FROM properties p 
            INNER JOIN cities c ON p.city_id = c.id 
            WHERE $where_sql 
            ORDER BY p.rent ASC LIMIT 6";

    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $results[] = array(
                'title' => $row['name'] . ' (' . $row['city_name'] . ') - ₹' . number_format($row['rent']) . '/mo',
                'subtitle' => $row['address'],
                'type' => 'property',
                'url' => 'property_detail.php?property_id=' . $row['id'],
                'icon' => 'fa-building'
            );
        }
    }
}

echo json_encode(array('success' => true, 'query' => $q, 'results' => $results));
