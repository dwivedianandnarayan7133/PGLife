<?php
session_start();
require_once 'includes/database_connect.php';
require_once 'includes/rbac.php';

$admin = check_role('admin');
$admin_id = (int)$admin['id'];

// 0. Analytics Queries for Super Admin Dashboard
$stat_prop_counts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT 
    COUNT(*) AS total,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
    SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) AS suspended,
    SUM(total_seats) AS total_seats,
    SUM(occupied_seats) AS occupied_seats,
    SUM(reserved_seats) AS reserved_seats,
    SUM(available_seats) AS available_seats
FROM properties"));

$stat_booking_counts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT 
    COUNT(*) AS total,
    SUM(CASE WHEN status IN ('Approved', 'accepted', 'completed', 'Active', 'Checked In') THEN 1 ELSE 0 END) AS active,
    SUM(CASE WHEN status IN ('Pending', 'Contacted', 'Seat Reserved') THEN 1 ELSE 0 END) AS pending
FROM bookings"));

$stat_rev_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(p.rent) AS monthly_rev FROM bookings b INNER JOIN properties p ON b.property_id = p.id WHERE b.status IN ('Approved', 'accepted', 'completed', 'Active', 'Checked In')"));
$total_rev = !empty($stat_rev_res['monthly_rev']) ? (int)$stat_rev_res['monthly_rev'] : 0;

$stat_leads_counts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT 
    COUNT(*) AS total,
    SUM(CASE WHEN lead_status = 'New' THEN 1 ELSE 0 END) AS new_leads,
    SUM(CASE WHEN lead_status = 'Contacted' THEN 1 ELSE 0 END) AS contacted,
    SUM(CASE WHEN lead_status = 'Booking Confirmed' THEN 1 ELSE 0 END) AS confirmed
FROM interested_users_properties"));

$city_stats_res = mysqli_query($conn, "SELECT c.name AS city_name, COUNT(p.id) AS prop_count, SUM(p.total_seats) AS total_seats FROM cities c LEFT JOIN properties p ON c.id = p.city_id GROUP BY c.id");
$city_labels = array();
$city_props = array();
$city_seats = array();
if ($city_stats_res) {
    while ($cs = mysqli_fetch_assoc($city_stats_res)) {
        $city_labels[] = $cs['city_name'];
        $city_props[] = (int)$cs['prop_count'];
        $city_seats[] = (int)$cs['total_seats'];
    }
}

// 1. Pending Property Approvals Queue
$pending_props_res = mysqli_query($conn, "SELECT p.*, c.name AS city_name, u.full_name AS owner_name, u.email AS owner_email 
                                           FROM properties p 
                                           INNER JOIN cities c ON p.city_id = c.id 
                                           LEFT JOIN users u ON p.owner_id = u.id 
                                           WHERE p.status = 'pending' ORDER BY p.id DESC");

// 2. All Booked PGs Queue & Rental Agreements
$all_bookings_res = mysqli_query($conn, "SELECT b.*, p.name AS property_name, p.rent, c.name AS city_name, 
                                                u.full_name AS tenant_name, u.phone AS tenant_phone, u.email AS tenant_email,
                                                o.full_name AS owner_name, a.id AS agreement_id, a.agreement_doc, a.status AS agreement_status, a.admin_remark
                                         FROM bookings b
                                         INNER JOIN properties p ON b.property_id = p.id
                                         INNER JOIN cities c ON p.city_id = c.id
                                         INNER JOIN users u ON b.user_id = u.id
                                         LEFT JOIN users o ON p.owner_id = o.id
                                         LEFT JOIN agreements a ON a.booking_id = b.id
                                         ORDER BY b.id DESC");


// 3. All Interested PGs & Users Queue
$leads_res = mysqli_query($conn, "SELECT iup.*, u.full_name, u.phone, u.email, p.name AS property_name, o.full_name AS owner_name 
                                  FROM interested_users_properties iup 
                                  INNER JOIN users u ON iup.user_id = u.id 
                                  INNER JOIN properties p ON iup.property_id = p.id 
                                  LEFT JOIN users o ON p.owner_id = o.id 
                                  ORDER BY iup.id DESC");

// 4. Pending Owner Verification Queue
$verifications_res = mysqli_query($conn, "SELECT u.id, u.full_name, u.email, u.phone, u.role, u.id_proof_doc, u.id_verified 
                                           FROM users u WHERE u.role = 'owner' OR u.id_proof_doc IS NOT NULL ORDER BY u.id DESC");

// 5. Support Tickets Queue
$tickets_res = mysqli_query($conn, "SELECT t.*, u.full_name, u.email, p.name AS property_name 
                                    FROM support_tickets t 
                                    INNER JOIN users u ON t.user_id = u.id 
                                    LEFT JOIN properties p ON t.property_id = p.id 
                                    ORDER BY t.id DESC");

// 6. Global Platform Properties & Capacity Audit
$all_props_res = mysqli_query($conn, "SELECT p.*, c.name AS city_name, u.full_name AS owner_name FROM properties p INNER JOIN cities c ON p.city_id = c.id LEFT JOIN users u ON p.owner_id = u.id ORDER BY p.id DESC");

// 7. Audit Logs
$audit_res = mysqli_query($conn, "SELECT a.*, u.full_name FROM audit_logs a LEFT JOIN users u ON a.actor_id = u.id ORDER BY a.id DESC LIMIT 30");

// 8. Account Deletion Requests Queue
$deletion_requests_res = mysqli_query($conn, "SELECT id, full_name, email, phone, role, deletion_status, deletion_reason, deletion_requested_at, deletion_admin_remark FROM users WHERE deletion_status != 'none' ORDER BY id DESC");

// 9. Outbound SMS & Email Notification Dispatch Logs
$outbound_res = mysqli_query($conn, "SELECT o.*, u.full_name FROM outbound_notifications o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.id DESC LIMIT 40");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Enterprise Portal | PGLife</title>
    <?php include 'includes/head_links.php'; ?>
    <style>
        .admin-sidebar { background: #0f172a; color: white; min-height: calc(100vh - 60px); padding: 20px 10px; }
        .admin-sidebar-link {
            color: #94a3b8; font-weight: 600; text-decoration: none !important; padding: 12px 16px;
            border-radius: 10px; display: block; margin-bottom: 6px; cursor: pointer; transition: all 0.2s ease;
        }
        .admin-sidebar-link:hover { background: #1e293b; color: #ffffff; }
        .admin-sidebar-link.active { background: #ef4444 !important; color: #ffffff !important; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.35); }
        .card-custom { border-radius: 16px; border: 1px solid #e2e8f0; background: white; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .tab-content > .tab-pane { display: none; }
        .tab-content > .tab-pane.active { display: block !important; }
    </style>
</head>
<body style="background-color: #f8fafc;">
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <!-- Mobile View Navigation Selector -->
        <div class="d-md-none p-3 bg-dark text-white">
            <label class="font-weight-bold text-white small mb-1"><i class="fas fa-bars"></i> Select Admin View:</label>
            <select class="form-control" onchange="switchAdminTab(this.value, null)">
                <option value="tab-analytics">📊 Analytical Dashboard & Metrics</option>
                <option value="tab-pending">🕒 Pending Property Reviews</option>
                <option value="tab-booked">📋 All Booked PGs Queue</option>
                <option value="tab-interested-all">👥 All Interested PGs & Leads</option>
                <option value="tab-owners">🛡️ Owner Identity Verification</option>
                <option value="tab-seats">🪑 Seat Capacity Audit</option>
                <option value="tab-all-props">🏙️ All Properties & Moderation</option>
                <option value="tab-tickets">🎧 Support & Callbacks</option>
                <option value="tab-deletion">🗑️ Account Deletion Requests</option>
                <option value="tab-audit">📜 Audit Logs</option>
            </select>
        </div>

        <div class="row">
            <!-- Sidebar Desktop Navigation -->
            <div class="col-md-2 admin-sidebar d-none d-md-block">
                <h6 class="text-uppercase text-muted font-weight-bold mb-4 px-2" style="font-size: 11px; letter-spacing: 1px;">Super Admin Controls</h6>
                <a class="admin-sidebar-link active" onclick="switchAdminTab('tab-analytics', this)"><i class="fas fa-chart-line mr-2"></i> Analytical Dashboard</a>
                <a class="admin-sidebar-link" onclick="switchAdminTab('tab-pending', this)"><i class="fas fa-clock mr-2"></i> Pending Reviews</a>
                <a class="admin-sidebar-link" onclick="switchAdminTab('tab-booked', this)"><i class="fas fa-calendar-check mr-2"></i> All Booked PGs</a>
                <a class="admin-sidebar-link" onclick="switchAdminTab('tab-interested-all', this)"><i class="fas fa-heart mr-2"></i> Interested PGs & Leads</a>
                <a class="admin-sidebar-link" onclick="switchAdminTab('tab-owners', this)"><i class="fas fa-user-check mr-2"></i> Owner Verification</a>
                <a class="admin-sidebar-link" onclick="switchAdminTab('tab-seats', this)"><i class="fas fa-chair mr-2"></i> Seat Capacity Audit</a>
                <a class="admin-sidebar-link" onclick="switchAdminTab('tab-all-props', this)"><i class="fas fa-building mr-2"></i> All Properties</a>
                <a class="admin-sidebar-link" onclick="switchAdminTab('tab-tickets', this)"><i class="fas fa-headset mr-2"></i> Support & Callbacks</a>
                <a class="admin-sidebar-link" onclick="switchAdminTab('tab-deletion', this)"><i class="fas fa-trash-alt mr-2"></i> Deletion Requests</a>
                <a class="admin-sidebar-link" onclick="switchAdminTab('tab-audit', this)"><i class="fas fa-history mr-2"></i> Audit Logs</a>
            </div>

            <!-- Main Content Area -->
            <div class="col-md-10 p-4">
                <div class="tab-content">
                    
                    <!-- 0. Super Admin Analytical Dashboard -->
                    <div class="tab-pane fade show active" id="tab-analytics">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-chart-line text-danger"></i> Enterprise Analytical Dashboard & Platform Metrics</h4>
                                <small class="text-muted">Real-time statistics across properties, bookings, city capacity, and revenue.</small>
                            </div>
                            <div>
                                <a href="api/print_audit_log.php" target="_blank" class="btn btn-outline-danger font-weight-bold"><i class="fas fa-print"></i> Print Official Audit Trail</a>
                            </div>
                        </div>

                        <!-- Metric Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card card-custom p-3 text-center border-left border-primary" style="border-left-width: 5px !important;">
                                    <div class="text-muted small font-weight-bold">TOTAL HOSTELS / PGS</div>
                                    <h2 class="font-weight-bold text-primary mb-0"><?php echo (int)$stat_prop_counts['total']; ?></h2>
                                    <small class="text-success font-weight-bold"><?php echo (int)$stat_prop_counts['approved']; ?> Approved & Active</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card card-custom p-3 text-center border-left border-success" style="border-left-width: 5px !important;">
                                    <div class="text-muted small font-weight-bold">ACTIVE BOOKINGS</div>
                                    <h2 class="font-weight-bold text-success mb-0"><?php echo (int)$stat_booking_counts['active']; ?></h2>
                                    <small class="text-muted"><?php echo (int)$stat_booking_counts['pending']; ?> Pending Verification</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card card-custom p-3 text-center border-left border-info" style="border-left-width: 5px !important;">
                                    <div class="text-muted small font-weight-bold">OCCUPANCY RATE</div>
                                    <?php 
                                        $total_s = max(1, (int)$stat_prop_counts['total_seats']);
                                        $occ_s = (int)$stat_prop_counts['occupied_seats'];
                                        $occ_pct = round(($occ_s / $total_s) * 100);
                                    ?>
                                    <h2 class="font-weight-bold text-info mb-0"><?php echo $occ_pct; ?>%</h2>
                                    <small class="text-muted"><?php echo $occ_s; ?> / <?php echo $total_s; ?> Seats Occupied</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card card-custom p-3 text-center border-left border-warning" style="border-left-width: 5px !important;">
                                    <div class="text-muted small font-weight-bold">MONTHLY REVENUE</div>
                                    <h2 class="font-weight-bold text-warning mb-0">₹<?php echo number_format($total_rev); ?></h2>
                                    <small class="text-muted">Total Active Monthly Rent</small>
                                </div>
                            </div>
                        </div>

                        <!-- Charts Row 1 -->
                        <div class="row mb-4">
                            <div class="col-md-6 mb-4">
                                <div class="card card-custom">
                                    <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-chart-pie text-danger"></i> Property Review & Moderation Status</h6>
                                    <div style="height: 220px; position: relative;">
                                        <canvas id="chartPropStatus"></canvas>
                                    </div>
                                    <div class="d-flex justify-content-around text-center mt-3 pt-2 border-top">
                                        <div><span class="badge badge-success px-2 py-1">Approved: <?php echo (int)$stat_prop_counts['approved']; ?></span></div>
                                        <div><span class="badge badge-warning px-2 py-1">Pending: <?php echo (int)$stat_prop_counts['pending']; ?></span></div>
                                        <div><span class="badge badge-dark px-2 py-1">Suspended: <?php echo (int)$stat_prop_counts['suspended']; ?></span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card card-custom">
                                    <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-city text-primary"></i> City-wise Hostel & Seat Capacity</h6>
                                    <div style="height: 220px; position: relative;">
                                        <canvas id="chartCityDistribution"></canvas>
                                    </div>
                                    <div class="d-flex justify-content-around text-center mt-3 pt-2 border-top">
                                        <?php for($i=0; $i<min(3, count($city_labels)); $i++) { ?>
                                            <div><small class="font-weight-bold"><?php echo htmlspecialchars($city_labels[$i]); ?>:</small> <span class="text-primary font-weight-bold"><?php echo $city_props[$i]; ?> PGs (<?php echo $city_seats[$i]; ?> Seats)</span></div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Charts Row 2 -->
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="card card-custom">
                                    <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-filter text-success"></i> Lead Pipeline & Conversion Metrics</h6>
                                    <div style="height: 220px; position: relative;">
                                        <canvas id="chartLeadFunnel"></canvas>
                                    </div>
                                    <div class="d-flex justify-content-around text-center mt-3 pt-2 border-top">
                                        <div><small class="text-muted font-weight-bold">New Leads:</small> <span class="badge badge-primary"><?php echo (int)$stat_leads_counts['new_leads']; ?></span></div>
                                        <div><small class="text-muted font-weight-bold">Contacted:</small> <span class="badge badge-info"><?php echo (int)$stat_leads_counts['contacted']; ?></span></div>
                                        <div><small class="text-muted font-weight-bold">Confirmed:</small> <span class="badge badge-success"><?php echo (int)$stat_leads_counts['confirmed']; ?></span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="card card-custom">
                                    <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-chair text-warning"></i> Platform Seat Occupancy Breakdown</h6>
                                    <div style="height: 220px; position: relative;">
                                        <canvas id="chartSeatCapacity"></canvas>
                                    </div>
                                    <div class="d-flex justify-content-around text-center mt-3 pt-2 border-top">
                                        <div><small class="text-muted font-weight-bold">Occupied:</small> <span class="badge badge-danger"><?php echo (int)$stat_prop_counts['occupied_seats']; ?></span></div>
                                        <div><small class="text-muted font-weight-bold">Reserved:</small> <span class="badge badge-warning"><?php echo (int)$stat_prop_counts['reserved_seats']; ?></span></div>
                                        <div><small class="text-muted font-weight-bold">Available:</small> <span class="badge badge-success"><?php echo (int)$stat_prop_counts['available_seats']; ?></span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 1. Pending Property Approval Queue -->
                    <div class="tab-pane fade" id="tab-pending">
                        <h4 class="font-weight-bold text-dark mb-4"><i class="fas fa-clock text-warning"></i> Pending Property Approval Queue</h4>
                        <div class="card card-custom">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Prop ID</th>
                                            <th>Property Name</th>
                                            <th>Owner</th>
                                            <th>City</th>
                                            <th>Rent</th>
                                            <th>Review Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($pending_props_res) > 0) {
                                            while ($p = mysqli_fetch_assoc($pending_props_res)) { ?>
                                            <tr>
                                                <td>#<?php echo $p['id']; ?></td>
                                                <td class="font-weight-bold"><?php echo htmlspecialchars($p['name']); ?></td>
                                                <td><?php echo htmlspecialchars($p['owner_name'] ? $p['owner_name'] : 'N/A'); ?><br><small class="text-muted"><?php echo htmlspecialchars($p['owner_email']); ?></small></td>
                                                <td><?php echo htmlspecialchars($p['city_name']); ?></td>
                                                <td class="text-success font-weight-bold">₹<?php echo number_format($p['rent']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-success font-weight-bold" onclick="reviewProperty(<?php echo $p['id']; ?>, 'approved')"><i class="fas fa-check"></i> Approve</button>
                                                    <button class="btn btn-sm btn-danger font-weight-bold" onclick="reviewProperty(<?php echo $p['id']; ?>, 'rejected')"><i class="fas fa-times"></i> Reject</button>
                                                </td>
                                            </tr>
                                        <?php } } else { ?>
                                            <tr><td colspan="6" class="text-center text-muted p-4">No pending property submissions. All listings are reviewed!</td></tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- 2. All Booked PGs Queue & Rental Agreements Approval -->
                    <div class="tab-pane fade" id="tab-booked">
                        <h4 class="font-weight-bold text-dark mb-4"><i class="fas fa-file-contract text-success"></i> Rental Agreements Approval & Resident Registry</h4>
                        <div class="card card-custom">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Booking ID</th>
                                            <th>Tenant Name</th>
                                            <th>Contact Info</th>
                                            <th>PG Name & City</th>
                                            <th>Move-in Date</th>
                                            <th>Booking Status</th>
                                            <th>Super Admin Seal & Status</th>
                                            <th>Action & Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($all_bookings_res) > 0) {
                                            while ($bk = mysqli_fetch_assoc($all_bookings_res)) {
                                                $agr_status = !empty($bk['agreement_status']) ? $bk['agreement_status'] : 'pending_review';
                                            ?>
                                            <tr>
                                                <td>#BK-<?php echo $bk['id']; ?></td>
                                                <td class="font-weight-bold"><?php echo htmlspecialchars($bk['tenant_name']); ?></td>
                                                <td>📞 <?php echo htmlspecialchars($bk['tenant_phone']); ?><br><small class="text-muted">✉️ <?php echo htmlspecialchars($bk['tenant_email']); ?></small></td>
                                                <td><?php echo htmlspecialchars($bk['property_name']); ?> (<?php echo htmlspecialchars($bk['city_name']); ?>)</td>
                                                <td>📅 <?php echo $bk['move_in_date'] ? $bk['move_in_date'] : 'N/A'; ?></td>
                                                <td>
                                                    <span class="badge <?php echo $bk['status'] === 'accepted' || $bk['status'] === 'completed' || $bk['status'] === 'Approved' ? 'badge-success' : 'badge-warning'; ?>">
                                                        <?php echo strtoupper($bk['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($agr_status === 'approved') { ?>
                                                        <span class="badge badge-success p-2"><i class="fas fa-check-circle"></i> SEALED & APPROVED</span>
                                                    <?php } elseif ($agr_status === 'rejected') { ?>
                                                        <span class="badge badge-danger p-2"><i class="fas fa-times-circle"></i> REJECTED</span>
                                                    <?php } else { ?>
                                                        <span class="badge badge-warning p-2"><i class="fas fa-clock"></i> PENDING ADMIN SEAL</span>
                                                    <?php } ?>
                                                    <?php if (!empty($bk['admin_remark'])) { ?>
                                                        <div class="small text-muted font-italic mt-1" style="max-width: 180px;">"<?php echo htmlspecialchars($bk['admin_remark']); ?>"</div>
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <a href="api/generate_agreement_pdf.php?booking_id=<?php echo $bk['id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary font-weight-bold mb-1">
                                                        <i class="fas fa-eye text-danger"></i> View Document
                                                    </a>
                                                    <button class="btn btn-sm btn-success font-weight-bold" onclick="openReviewAgreementModal(<?php echo $bk['id']; ?>, <?php echo (int)$bk['agreement_id']; ?>, '<?php echo $agr_status; ?>', '<?php echo addslashes($bk['admin_remark'] ? $bk['admin_remark'] : ''); ?>')">
                                                        <i class="fas fa-stamp"></i> Seal & Remark
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php } } else { ?>
                                            <tr><td colspan="8" class="text-center text-muted p-4">No bookings or agreement records found.</td></tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>


                    <!-- 3. All Interested PGs & Leads Queue -->
                    <div class="tab-pane fade" id="tab-interested-all">
                        <h4 class="font-weight-bold text-dark mb-4"><i class="fas fa-heart text-danger"></i> All Interested Users & PG Leads Oversight</h4>
                        <div class="card card-custom">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Lead ID</th>
                                            <th>Student Name</th>
                                            <th>Contact Info</th>
                                            <th>Hostel / PG Name</th>
                                            <th>Move-in Date / Budget</th>
                                            <th>Owner</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($leads_res) > 0) {
                                            while ($l = mysqli_fetch_assoc($leads_res)) { ?>
                                            <tr>
                                                <td>#LEAD-<?php echo $l['id']; ?></td>
                                                <td class="font-weight-bold"><?php echo htmlspecialchars($l['full_name']); ?></td>
                                                <td>📞 <?php echo htmlspecialchars($l['phone']); ?><br><small class="text-muted">✉️ <?php echo htmlspecialchars($l['email']); ?></small></td>
                                                <td><?php echo htmlspecialchars($l['property_name']); ?></td>
                                                <td>📅 <?php echo $l['move_in_date']; ?><br><strong class="text-success">₹<?php echo number_format($l['budget']); ?>/mo</strong></td>
                                                <td><?php echo htmlspecialchars($l['owner_name'] ? $l['owner_name'] : 'System'); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $l['lead_status'] === 'Booking Confirmed' ? 'badge-success' : 'badge-primary'; ?>">
                                                        <?php echo strtoupper($l['lead_status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php } } else { ?>
                                            <tr><td colspan="7" class="text-center text-muted p-4">No interest leads logged in platform.</td></tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Owner Verification Queue -->
                    <div class="tab-pane fade" id="tab-owners">
                        <h4 class="font-weight-bold text-dark mb-4"><i class="fas fa-user-check text-info"></i> Owner & User Identity Verification</h4>
                        <div class="card card-custom">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>User ID</th>
                                            <th>Full Name</th>
                                            <th>Email / Phone</th>
                                            <th>Role</th>
                                            <th>ID Proof Document</th>
                                            <th>Verification Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($verifications_res) > 0) {
                                            while ($v = mysqli_fetch_assoc($verifications_res)) { ?>
                                            <tr>
                                                <td>#<?php echo $v['id']; ?></td>
                                                <td class="font-weight-bold"><?php echo htmlspecialchars($v['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($v['email']); ?><br><small class="text-muted"><?php echo htmlspecialchars($v['phone']); ?></small></td>
                                                <td><span class="badge badge-light border"><?php echo strtoupper($v['role']); ?></span></td>
                                                <td>
                                                    <?php if (!empty($v['id_proof_doc'])) { ?>
                                                        <a href="<?php echo htmlspecialchars($v['id_proof_doc']); ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-file"></i> View ID Doc</a>
                                                    <?php } else { ?>
                                                        <span class="text-muted small">No doc uploaded</span>
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $v['id_verified'] === 'verified' ? 'badge-success' : ($v['id_verified'] === 'rejected' ? 'badge-danger' : 'badge-warning'); ?>">
                                                        <?php echo strtoupper($v['id_verified']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-success font-weight-bold mr-1" onclick="verifyUserDoc(<?php echo $v['id']; ?>, 'verified')"><i class="fas fa-user-check"></i> Mark Verified</button>
                                                    <button class="btn btn-sm btn-danger font-weight-bold" onclick="verifyUserDoc(<?php echo $v['id']; ?>, 'rejected')"><i class="fas fa-user-times"></i> Reject</button>
                                                </td>
                                            </tr>
                                        <?php } } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Seat Capacity Audit Table -->
                    <div class="tab-pane fade" id="tab-seats">
                        <h4 class="font-weight-bold text-dark mb-4"><i class="fas fa-chair text-success"></i> Platform Seat Capacity & Vacancy Audit</h4>
                        <div class="card card-custom">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Property ID</th>
                                            <th>Hostel Name</th>
                                            <th>Owner</th>
                                            <th>Total Seats</th>
                                            <th>Occupied</th>
                                            <th>Reserved</th>
                                            <th>Available Now</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($all_props_res) > 0) {
                                            while ($ap = mysqli_fetch_assoc($all_props_res)) { ?>
                                            <tr>
                                                <td>#<?php echo $ap['id']; ?></td>
                                                <td class="font-weight-bold"><?php echo htmlspecialchars($ap['name']); ?></td>
                                                <td><?php echo htmlspecialchars($ap['owner_name'] ? $ap['owner_name'] : 'System'); ?></td>
                                                <td><strong><?php echo $ap['total_seats']; ?></strong></td>
                                                <td class="text-danger font-weight-bold"><?php echo $ap['occupied_seats']; ?></td>
                                                <td class="text-warning font-weight-bold"><?php echo $ap['reserved_seats']; ?></td>
                                                <td class="text-success font-weight-bold"><?php echo $ap['available_seats']; ?></td>
                                            </tr>
                                        <?php } } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- 6. All Platform Properties & Moderation -->
                    <div class="tab-pane fade" id="tab-all-props">
                        <h4 class="font-weight-bold text-dark mb-4"><i class="fas fa-building text-dark"></i> All Platform Hostels & Moderation</h4>
                        <div class="card card-custom">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Property Name</th>
                                            <th>Owner</th>
                                            <th>City</th>
                                            <th>Rent</th>
                                            <th>Status</th>
                                            <th>Moderation Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php mysqli_data_seek($all_props_res, 0); if (mysqli_num_rows($all_props_res) > 0) {
                                            while ($ap = mysqli_fetch_assoc($all_props_res)) { ?>
                                            <tr>
                                                <td>#<?php echo $ap['id']; ?></td>
                                                <td class="font-weight-bold"><?php echo htmlspecialchars($ap['name']); ?></td>
                                                <td><?php echo htmlspecialchars($ap['owner_name'] ? $ap['owner_name'] : 'System Seeded'); ?></td>
                                                <td><?php echo htmlspecialchars($ap['city_name']); ?></td>
                                                <td>₹<?php echo number_format($ap['rent']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $ap['status'] === 'approved' ? 'badge-success' : ($ap['status'] === 'suspended' ? 'badge-dark' : 'badge-warning'); ?>">
                                                        <?php echo strtoupper($ap['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($ap['status'] !== 'suspended') { ?>
                                                        <button class="btn btn-sm btn-dark font-weight-bold" onclick="reviewProperty(<?php echo $ap['id']; ?>, 'suspended')"><i class="fas fa-ban"></i> Suspend</button>
                                                    <?php } else { ?>
                                                        <button class="btn btn-sm btn-success font-weight-bold" onclick="reviewProperty(<?php echo $ap['id']; ?>, 'approved')"><i class="fas fa-check"></i> Restore</button>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                        <?php } } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- 7. Support & Callbacks Center -->
                    <div class="tab-pane fade" id="tab-tickets">
                        <h4 class="font-weight-bold text-dark mb-4"><i class="fas fa-headset text-danger"></i> Support Tickets & User Callback Requests</h4>
                        <div class="card card-custom">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Ticket ID</th>
                                            <th>User Name</th>
                                            <th>Type</th>
                                            <th>Subject / Message</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($tickets_res) > 0) {
                                            while ($t = mysqli_fetch_assoc($tickets_res)) { ?>
                                            <tr>
                                                <td>#TCK-<?php echo $t['id']; ?></td>
                                                <td class="font-weight-bold"><?php echo htmlspecialchars($t['full_name']); ?></td>
                                                <td><span class="badge <?php echo $t['ticket_type'] === 'other' ? 'badge-warning' : 'badge-info'; ?>"><?php echo strtoupper($t['ticket_type']); ?></span></td>
                                                <td><strong><?php echo htmlspecialchars($t['subject']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($t['message']); ?></small></td>
                                                <td>
                                                    <span class="badge <?php echo $t['status'] === 'Resolved' ? 'badge-success' : 'badge-danger'; ?>">
                                                        <?php echo strtoupper($t['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-success font-weight-bold" onclick="updateTicket(<?php echo $t['id']; ?>, 'Resolved')"><i class="fas fa-check-circle"></i> Mark Resolved</button>
                                                </td>
                                            </tr>
                                        <?php } } else { ?>
                                            <tr><td colspan="6" class="text-center text-muted p-4">No support tickets submitted yet.</td></tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- 8. Account Deletion Requests Queue -->
                    <div class="tab-pane fade" id="tab-deletion">
                        <h4 class="font-weight-bold text-dark mb-4"><i class="fas fa-trash-alt text-danger"></i> Account Deletion Requests & Archival Moderation</h4>
                        <div class="card card-custom">
                            <div class="alert alert-info py-2" style="font-size: 13px;">
                                <i class="fas fa-info-circle"></i> Approving a deletion request soft-deletes/deactivates the user account while preserving historical bookings, transactions, and user logs in compliance with regulatory auditing rules.
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>User ID</th>
                                            <th>Full Name</th>
                                            <th>Email / Mobile</th>
                                            <th>Role</th>
                                            <th>Requested At</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($deletion_requests_res) > 0) {
                                            while ($dr = mysqli_fetch_assoc($deletion_requests_res)) { ?>
                                            <tr>
                                                <td>#<?php echo $dr['id']; ?></td>
                                                <td class="font-weight-bold"><?php echo htmlspecialchars($dr['full_name']); ?></td>
                                                <td>📞 <?php echo htmlspecialchars($dr['phone']); ?><br><small class="text-muted">✉️ <?php echo htmlspecialchars($dr['email']); ?></small></td>
                                                <td><span class="badge badge-light border"><?php echo strtoupper($dr['role']); ?></span></td>
                                                <td style="font-size: 12px;"><?php echo $dr['deletion_requested_at']; ?></td>
                                                <td style="max-width: 220px; font-size: 12.5px;">"<?php echo htmlspecialchars($dr['deletion_reason']); ?>"</td>
                                                <td>
                                                    <span class="badge <?php echo $dr['deletion_status'] === 'approved' ? 'badge-success' : ($dr['deletion_status'] === 'rejected' ? 'badge-danger' : 'badge-warning'); ?>">
                                                        <?php echo strtoupper($dr['deletion_status']); ?>
                                                    </span>
                                                    <?php if (!empty($dr['deletion_admin_remark'])) { ?>
                                                        <div class="small text-muted font-italic mt-1">Remark: "<?php echo htmlspecialchars($dr['deletion_admin_remark']); ?>"</div>
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <?php if ($dr['deletion_status'] === 'pending_approval') { ?>
                                                        <button class="btn btn-sm btn-success font-weight-bold mr-1" onclick="reviewDeletionRequest(<?php echo $dr['id']; ?>, 'approved')">
                                                            <i class="fas fa-check"></i> Approve & Archive
                                                        </button>
                                                        <button class="btn btn-sm btn-danger font-weight-bold" onclick="reviewDeletionRequest(<?php echo $dr['id']; ?>, 'rejected')">
                                                            <i class="fas fa-times"></i> Reject Request
                                                        </button>
                                                    <?php } else { ?>
                                                        <span class="text-muted small">Reviewed</span>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                        <?php } } else { ?>
                                            <tr><td colspan="8" class="text-center text-muted p-4">No account deletion requests logged.</td></tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- 9. Audit Logs & Outbound SMS/Email Dispatch Logs -->
                    <div class="tab-pane fade" id="tab-audit">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-history text-secondary"></i> System Audit Trail & Outbound Alerts</h4>
                            <a href="api/print_audit_log.php" target="_blank" class="btn btn-danger font-weight-bold"><i class="fas fa-print"></i> Print Official Audit Record</a>
                        </div>
                        <div class="card card-custom mb-4">
                            <h6 class="font-weight-bold text-primary mb-3"><i class="fas fa-paper-plane"></i> Outbound SMS & Email Notification Dispatches</h6>
                            <div class="table-responsive">
                                <table class="table table-hover table-sm align-middle" style="font-size: 12.5px;">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Recipient</th>
                                            <th>Channel</th>
                                            <th>Title</th>
                                            <th>Message Body</th>
                                            <th>Status</th>
                                            <th>Timestamp</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($outbound_res && mysqli_num_rows($outbound_res) > 0) {
                                            while ($ob = mysqli_fetch_assoc($outbound_res)) { ?>
                                            <tr>
                                                <td>#<?php echo $ob['id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($ob['full_name'] ? $ob['full_name'] : 'User'); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($ob['recipient_phone'] ? $ob['recipient_phone'] : $ob['recipient_email']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $ob['channel'] === 'SMS' ? 'badge-info' : 'badge-warning'; ?>">
                                                        <?php echo $ob['channel']; ?>
                                                    </span>
                                                </td>
                                                <td class="font-weight-bold"><?php echo htmlspecialchars($ob['title']); ?></td>
                                                <td style="max-width: 250px;" class="text-truncate" title="<?php echo htmlspecialchars($ob['message']); ?>"><?php echo htmlspecialchars($ob['message']); ?></td>
                                                <td><span class="badge badge-success"><?php echo $ob['status']; ?></span></td>
                                                <td style="font-size: 11px;"><?php echo isset($ob['sent_at']) ? $ob['sent_at'] : ''; ?></td>
                                            </tr>
                                        <?php } } else { ?>
                                            <tr><td colspan="7" class="text-center text-muted p-3">No outbound SMS/Email dispatches recorded.</td></tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="card card-custom">
                            <h6 class="font-weight-bold text-secondary mb-3"><i class="fas fa-list-alt"></i> System Activity Audit Trail</h6>
                            <ul class="list-group list-group-flush">
                                <?php if (mysqli_num_rows($audit_res) > 0) {
                                    while ($l = mysqli_fetch_assoc($audit_res)) { ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($l['full_name'] ? $l['full_name'] : 'System'); ?></strong>: <?php echo htmlspecialchars($l['action']); ?> - <?php echo htmlspecialchars($l['details']); ?>
                                        </div>
                                        <small class="text-muted"><?php echo $l['created_at']; ?></small>
                                    </li>
                                <?php } } ?>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Super Admin Rental Agreement Review, Seal & Remarks -->
    <div class="modal fade" id="modalReviewAgreement" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formReviewAgreement">
                    <input type="hidden" name="action" value="verify_agreement">
                    <input type="hidden" name="booking_id" id="reviewAgrBookingId">
                    <input type="hidden" name="agreement_id" id="reviewAgrId">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title font-weight-bold"><i class="fas fa-stamp"></i> Super Admin Agreement Approval & Seal</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-group">
                            <label class="font-weight-bold small">Document Verification Action *</label>
                            <select class="form-control" name="status" id="reviewAgrStatusSelect" required>
                                <option value="approved">✓ Approve & Apply Super Admin Official Seal</option>
                                <option value="rejected">✖ Reject & Request Agreement Revision</option>
                                <option value="pending_review">⏳ Mark as Under Verification Review</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold small">Super Admin Official Remarks / Verification Notes *</label>
                            <textarea class="form-control" name="admin_remark" id="reviewAgrRemark" rows="3" placeholder="e.g. Verified owner property documents and student identity. Approved and official seal applied." required></textarea>
                        </div>
                        <div class="mb-2">
                            <a id="reviewAgrDocLink" href="#" target="_blank" class="btn btn-sm btn-outline-info font-weight-bold">
                                <i class="fas fa-external-link-alt"></i> Preview Agreement PDF
                            </a>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="submit" class="btn btn-success font-weight-bold"><i class="fas fa-check-circle"></i> Save Seal & Status Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
    function switchAdminTab(targetTabId, linkEl) {
        document.querySelectorAll('.admin-sidebar-link').forEach(el => el.classList.remove('active'));
        if (linkEl) {
            linkEl.classList.add('active');
        } else {
            const matchLink = document.querySelector(`.admin-sidebar-link[onclick*="${targetTabId}"]`);
            if (matchLink) matchLink.classList.add('active');
        }

        const sel = document.querySelector('.d-md-none select');
        if (sel) sel.value = targetTabId;

        document.querySelectorAll('.tab-content > .tab-pane').forEach(pane => {
            pane.classList.remove('show', 'active');
        });

        const targetPane = document.getElementById(targetTabId);
        if (targetPane) {
            targetPane.classList.add('show', 'active');
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        if (tabParam) {
            const targetTab = tabParam.startsWith('tab-') ? tabParam : 'tab-' + tabParam;
            switchAdminTab(targetTab, null);
        } else {
            switchAdminTab('tab-analytics', document.querySelector('.admin-sidebar-link'));
        }

        if (typeof Chart === 'undefined') {
            console.warn("Chart.js not loaded. Displaying visual fallback metrics.");
            return;
        }
        try {
            // Chart 1: Property Status Doughnut
            const c1 = document.getElementById('chartPropStatus');
            if (c1) {
                new Chart(c1, {
                    type: 'doughnut',
                    data: {
                        labels: ['Approved', 'Pending Review', 'Suspended'],
                        datasets: [{
                            data: [<?php echo (int)$stat_prop_counts['approved']; ?>, <?php echo (int)$stat_prop_counts['pending']; ?>, <?php echo (int)$stat_prop_counts['suspended']; ?>],
                            backgroundColor: ['#16a34a', '#eab308', '#0f172a']
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                });
            }

            // Chart 2: City-wise Bar Chart
            const c2 = document.getElementById('chartCityDistribution');
            if (c2) {
                new Chart(c2, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($city_labels); ?>,
                        datasets: [
                            { label: 'Hostels Listed', data: <?php echo json_encode($city_props); ?>, backgroundColor: '#0284c7' },
                            { label: 'Total Seat Capacity', data: <?php echo json_encode($city_seats); ?>, backgroundColor: '#38bdf8' }
                        ]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                });
            }

            // Chart 3: Lead Pipeline Bar Chart
            const c3 = document.getElementById('chartLeadFunnel');
            if (c3) {
                new Chart(c3, {
                    type: 'bar',
                    data: {
                        labels: ['New Leads', 'Contacted', 'Confirmed Bookings'],
                        datasets: [{
                            label: 'Student Inquiries',
                            data: [<?php echo (int)$stat_leads_counts['new_leads']; ?>, <?php echo (int)$stat_leads_counts['contacted']; ?>, <?php echo (int)$stat_leads_counts['confirmed']; ?>],
                            backgroundColor: ['#6366f1', '#06b6d4', '#10b981']
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                });
            }

            // Chart 4: Seat Capacity Pie Chart
            const c4 = document.getElementById('chartSeatCapacity');
            if (c4) {
                new Chart(c4, {
                    type: 'pie',
                    data: {
                        labels: ['Occupied Seats', 'Reserved Seats', 'Available Seats'],
                        datasets: [{
                            data: [<?php echo (int)$stat_prop_counts['occupied_seats']; ?>, <?php echo (int)$stat_prop_counts['reserved_seats']; ?>, <?php echo (int)$stat_prop_counts['available_seats']; ?>],
                            backgroundColor: ['#ef4444', '#f59e0b', '#10b981']
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                });
            }
        } catch(e) {
            console.error("Error initializing Chart.js:", e);
        }
    });

    function openReviewAgreementModal(bookingId, agreementId, currentStatus, currentRemark) {
        document.getElementById('reviewAgrBookingId').value = bookingId;
        document.getElementById('reviewAgrId').value = agreementId;
        document.getElementById('reviewAgrStatusSelect').value = currentStatus ? currentStatus : 'approved';
        document.getElementById('reviewAgrRemark').value = currentRemark ? currentRemark : 'Verified tenant identity & landlord details. Approved and official seal applied.';
        document.getElementById('reviewAgrDocLink').href = 'api/generate_agreement_pdf.php?booking_id=' + bookingId;
        $('#modalReviewAgreement').modal('show');
    }

    document.getElementById('formReviewAgreement').addEventListener('submit', function(e) {
        e.preventDefault();
        fetch('api/admin_actions.php', { method: 'POST', body: new FormData(this) })
            .then(r => r.json())
            .then(d => {
                alert(d.message);
                if (d.success) location.reload();
            });
    });

    function reviewProperty(propId, status) {
        const formData = new FormData();
        formData.append('action', 'review_property');
        formData.append('property_id', propId);
        formData.append('status', status);
        fetch('api/admin_actions.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(d => {
                alert(d.message);
                if (d.success) location.reload();
            });
    }

    function verifyUserDoc(userId, status) {
        const formData = new FormData();
        formData.append('action', 'verify_owner_doc');
        formData.append('user_id', userId);
        formData.append('status', status);
        fetch('api/admin_actions.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(d => {
                alert(d.message);
                if (d.success) location.reload();
            });
    }

    function updateTicket(ticketId, status) {
        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('ticket_id', ticketId);
        formData.append('status', status);
        fetch('api/tickets.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(d => {
                alert(d.message);
                if (d.success) location.reload();
            });
    }

    function reviewDeletionRequest(userId, status) {
        const remark = prompt(status === 'approved' ? 'Enter Super Admin approval & archival remark:' : 'Enter Super Admin rejection reason:', status === 'approved' ? 'Account deletion approved. User deactivated and data preserved for audit.' : 'Deletion request rejected.');
        if (remark === null) return; // User cancelled prompt

        const formData = new FormData();
        formData.append('action', 'review_account_deletion');
        formData.append('user_id', userId);
        formData.append('status', status);
        formData.append('remark', remark);

        fetch('api/admin_actions.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(d => {
                alert(d.message);
                if (d.success) location.reload();
            });
    }
    </script>
</body>
</html>


