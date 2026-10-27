<?php
session_start();
require_once __DIR__ . '/../includes/database_connect.php';
require_once __DIR__ . '/../includes/rbac.php';

$user = check_role(array('owner', 'admin'));
$user_id = (int)$user['id'];
$is_admin = ($user['role'] === 'admin');

$type = isset($_GET['type']) ? $_GET['type'] : 'interested'; // interested, booked, waiting
$format = isset($_GET['format']) ? $_GET['format'] : 'excel'; // excel, pdf

$owner_clause = $is_admin ? "1=1" : "p.owner_id = $user_id";

if ($type === 'interested') {
    $filename = "Interested_Students_" . date('Ymd_His');
    $title = "All Interested Students & Lead Pipeline Report";
    $sql = "SELECT iup.id AS lead_id, u.full_name AS student_name, u.phone AS student_phone, u.email AS student_email, 
                   u.college_name, u.gender, p.name AS property_name, c.name AS city_name, 
                   iup.room_preference, iup.move_in_date, iup.budget, iup.lead_status, iup.notes
            FROM interested_users_properties iup
            INNER JOIN users u ON iup.user_id = u.id
            INNER JOIN properties p ON iup.property_id = p.id
            INNER JOIN cities c ON p.city_id = c.id
            WHERE $owner_clause
            ORDER BY iup.id DESC";
} elseif ($type === 'booked') {
    $filename = "Booked_Students_" . date('Ymd_His');
    $title = "All Confirmed & Active Booked Students Registry";
    $sql = "SELECT b.id AS booking_id, u.full_name AS student_name, u.phone AS student_phone, u.email AS student_email, 
                   u.college_name, u.gender, p.name AS property_name, c.name AS city_name, 
                   b.room_type, b.move_in_date, p.rent, b.status AS booking_status, b.created_at
            FROM bookings b
            INNER JOIN users u ON b.user_id = u.id
            INNER JOIN properties p ON b.property_id = p.id
            INNER JOIN cities c ON p.city_id = c.id
            WHERE $owner_clause
            ORDER BY b.id DESC";
} else { // waiting
    $filename = "Waiting_List_Students_" . date('Ymd_His');
    $title = "Property Seat Vacancy Waiting List Registry";
    $sql = "SELECT w.id AS waitlist_id, w.full_name AS student_name, w.phone AS student_phone, w.email AS student_email, 
                   p.name AS property_name, c.name AS city_name, 
                   w.requested_room_type, w.preferred_move_in, w.status AS waitlist_status, w.created_at
            FROM waiting_list w
            INNER JOIN properties p ON w.property_id = p.id
            INNER JOIN cities c ON p.city_id = c.id
            WHERE $owner_clause
            ORDER BY w.id DESC";
}

$res = mysqli_query($conn, $sql);
$data = array();
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $data[] = $row;
    }
}

// ---------------- EXCEL / CSV EXPORT ----------------
if ($format === 'excel' || $format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');

    $output = fopen('php://output', 'w');

    // Print UTF-8 BOM for Excel opening
    fputs($output, "\xEF\xBB\xBF");

    if ($type === 'interested') {
        fputcsv($output, array('Lead ID', 'Student Name', 'Phone', 'Email', 'College', 'Gender', 'Property Name', 'City', 'Room Preference', 'Move-in Date', 'Budget (INR)', 'Status', 'Notes'));
        foreach ($data as $r) {
            fputcsv($output, array($r['lead_id'], $r['student_name'], $r['student_phone'], $r['student_email'], $r['college_name'], $r['gender'], $r['property_name'], $r['city_name'], $r['room_preference'], $r['move_in_date'], $r['budget'], $r['lead_status'], $r['notes']));
        }
    } elseif ($type === 'booked') {
        fputcsv($output, array('Booking ID', 'Student Name', 'Phone', 'Email', 'College', 'Gender', 'Property Name', 'City', 'Room Type', 'Move-in Date', 'Monthly Rent (INR)', 'Booking Status', 'Date Created'));
        foreach ($data as $r) {
            fputcsv($output, array($r['booking_id'], $r['student_name'], $r['student_phone'], $r['student_email'], $r['college_name'], $r['gender'], $r['property_name'], $r['city_name'], $r['room_type'], $r['move_in_date'], $r['rent'], $r['booking_status'], $r['created_at']));
        }
    } else { // waiting
        fputcsv($output, array('Waitlist ID', 'Student Name', 'Phone', 'Email', 'Property Name', 'City', 'Requested Room', 'Preferred Move-in', 'Status', 'Date Joined'));
        foreach ($data as $r) {
            fputcsv($output, array($r['waitlist_id'], $r['student_name'], $r['student_phone'], $r['student_email'], $r['property_name'], $r['city_name'], $r['requested_room_type'], $r['preferred_move_in'], $r['waitlist_status'], $r['created_at']));
        }
    }

    fclose($output);
    exit;
}

// ---------------- PDF EXPORT ----------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title); ?> | PGLife Enterprise</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
    <style>
        body { font-family: 'Open Sans', Arial, sans-serif; background: #f8fafc; color: #0f172a; padding: 30px; }
        .report-header { border-bottom: 3px double #0284c7; padding-bottom: 20px; margin-bottom: 25px; }
        .table-data th { background: #0284c7; color: white; border: none; font-size: 13px; text-transform: uppercase; }
        .table-data td { font-size: 13px; vertical-align: middle; }
        @media print {
            .no-print { display: none !important; }
            body { background: white; padding: 0; }
            .card { border: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print mb-4 d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm border">
        <div>
            <h5 class="font-weight-bold mb-0 text-primary"><i class="fas fa-file-pdf"></i> Official PDF Student Registry Report</h5>
            <small class="text-muted">Generated for Property Owner / Admin Oversight.</small>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-primary font-weight-bold mr-2"><i class="fas fa-print"></i> Print / Save as PDF</button>
            <button onclick="window.close()" class="btn btn-secondary font-weight-bold">Close Window</button>
        </div>
    </div>

    <div class="card p-4 border-0 shadow-sm bg-white">
        <div class="report-header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="font-weight-bold text-dark mb-1"><?= htmlspecialchars($title); ?></h3>
                <div class="text-muted small">Generated on <?= date('d F Y, h:i A'); ?> | Owner/Admin: <?= htmlspecialchars($user['full_name']); ?></div>
            </div>
            <div class="text-right">
                <span class="badge badge-primary p-2 font-weight-bold" style="font-size: 13px;">OFFICIAL PGLIFE REPORT</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-data">
                <thead>
                    <?php if ($type === 'interested') { ?>
                        <tr>
                            <th>ID</th>
                            <th>Student Name</th>
                            <th>Contact Info</th>
                            <th>Property Name</th>
                            <th>Move-in Date</th>
                            <th>Budget</th>
                            <th>Status</th>
                        </tr>
                    <?php } elseif ($type === 'booked') { ?>
                        <tr>
                            <th>ID</th>
                            <th>Student Name</th>
                            <th>Contact Info</th>
                            <th>Property Name</th>
                            <th>Room Type</th>
                            <th>Monthly Rent</th>
                            <th>Booking Status</th>
                        </tr>
                    <?php } else { ?>
                        <tr>
                            <th>ID</th>
                            <th>Student Name</th>
                            <th>Contact Info</th>
                            <th>Property Name</th>
                            <th>Requested Room</th>
                            <th>Preferred Move-in</th>
                            <th>Status</th>
                        </tr>
                    <?php } ?>
                </thead>
                <tbody>
                    <?php if (!empty($data)) {
                        foreach ($data as $r) { ?>
                        <tr>
                            <td>#<?= isset($r['lead_id']) ? $r['lead_id'] : (isset($r['booking_id']) ? $r['booking_id'] : $r['waitlist_id']); ?></td>
                            <td class="font-weight-bold"><?= htmlspecialchars($r['student_name']); ?></td>
                            <td>
                                📞 <?= htmlspecialchars($r['student_phone']); ?><br>
                                <small class="text-muted">✉️ <?= htmlspecialchars($r['student_email']); ?></small>
                            </td>
                            <td><?= htmlspecialchars($r['property_name']); ?> (<?= htmlspecialchars($r['city_name']); ?>)</td>
                            <?php if ($type === 'interested') { ?>
                                <td>📅 <?= htmlspecialchars($r['move_in_date']); ?></td>
                                <td class="text-success font-weight-bold">₹<?= number_format($r['budget']); ?></td>
                                <td><span class="badge badge-info"><?= strtoupper($r['lead_status']); ?></span></td>
                            <?php } elseif ($type === 'booked') { ?>
                                <td>🛏️ <?= htmlspecialchars($r['room_type']); ?></td>
                                <td class="text-success font-weight-bold">₹<?= number_format($r['rent']); ?>/mo</td>
                                <td><span class="badge badge-success"><?= strtoupper($r['booking_status']); ?></span></td>
                            <?php } else { ?>
                                <td>🛏️ <?= htmlspecialchars($r['requested_room_type']); ?></td>
                                <td>📅 <?= htmlspecialchars($r['preferred_move_in']); ?></td>
                                <td><span class="badge badge-warning"><?= strtoupper($r['waitlist_status']); ?></span></td>
                            <?php } ?>
                        </tr>
                    <?php } } else { ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted p-4">No student records found in registry.</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-3 border-top d-flex justify-content-between text-muted small">
            <div>PGLife Housing Verification & Student Management System</div>
            <div>Total Records: <?= count($data); ?></div>
        </div>
    </div>
</body>
</html>
