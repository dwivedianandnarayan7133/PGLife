<?php
session_start();
require_once 'includes/database_connect.php';
require_once 'includes/rbac.php';

$owner = check_role(array('owner', 'admin'));
$owner_id = (int)$owner['id'];

// 1. Analytics & Lead Statistics
$prop_count_res = mysqli_query($conn, "SELECT COUNT(*) AS total_props, SUM(views_count) AS total_views, SUM(total_seats) AS total_seats, SUM(occupied_seats) AS total_occupied, SUM(reserved_seats) AS total_reserved, SUM(available_seats) AS total_avail FROM properties WHERE owner_id = $owner_id");
$prop_stats = mysqli_fetch_assoc($prop_count_res);

// Lead Metrics
$lead_stats_res = mysqli_query($conn, "SELECT 
    COUNT(*) AS total_leads,
    SUM(CASE WHEN lead_status = 'Contacted' THEN 1 ELSE 0 END) AS contacted_leads,
    SUM(CASE WHEN lead_status = 'Seat Reserved' THEN 1 ELSE 0 END) AS reserved_seats,
    SUM(CASE WHEN lead_status = 'Booking Confirmed' THEN 1 ELSE 0 END) AS confirmed_bookings,
    SUM(CASE WHEN lead_status = 'New' THEN 1 ELSE 0 END) AS pending_users,
    SUM(CASE WHEN lead_status = 'Booking Cancelled' THEN 1 ELSE 0 END) AS cancelled_requests
    FROM interested_users_properties iup 
    INNER JOIN properties p ON iup.property_id = p.id 
    WHERE p.owner_id = $owner_id");
$lead_metrics = mysqli_fetch_assoc($lead_stats_res);

// 2. Fetch Leads
$interested_res = mysqli_query($conn, "SELECT iup.*, u.full_name, u.phone, u.email, u.gender AS user_gender, p.name AS property_name, p.id AS property_id 
                                        FROM interested_users_properties iup 
                                        INNER JOIN properties p ON iup.property_id = p.id 
                                        INNER JOIN users u ON iup.user_id = u.id 
                                        WHERE p.owner_id = $owner_id ORDER BY iup.id DESC");

// 3. Fetch Properties & Images
$properties_res = mysqli_query($conn, "SELECT p.*, c.name AS city_name FROM properties p INNER JOIN cities c ON p.city_id = c.id WHERE p.owner_id = $owner_id ORDER BY p.id DESC");

// 4. Fetch Bookings & Agreements
$booking_res = mysqli_query($conn, "SELECT b.id, b.room_type, b.status, b.created_at, b.move_in_date, u.full_name, u.phone, u.email, p.name AS property_name, p.rent, a.agreement_doc, a.status AS agreement_status, a.admin_remark 
                                    FROM bookings b 
                                    INNER JOIN properties p ON b.property_id = p.id 
                                    INNER JOIN users u ON b.user_id = u.id 
                                    LEFT JOIN agreements a ON a.booking_id = b.id 
                                    WHERE p.owner_id = $owner_id ORDER BY b.id DESC");

// 5. Fetch Waiting List Entries
$waitlist_res = mysqli_query($conn, "SELECT w.*, p.name AS property_name 
                                    FROM waiting_list w 
                                    INNER JOIN properties p ON w.property_id = p.id 
                                    WHERE p.owner_id = $owner_id 
                                    ORDER BY w.id DESC");

// Fetch Cities & Amenities
$cities_res = mysqli_query($conn, "SELECT * FROM cities ORDER BY name ASC");
$all_amenities_res = mysqli_query($conn, "SELECT * FROM amenities ORDER BY type ASC, name ASC");
$amenities_list = mysqli_fetch_all($all_amenities_res, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Admin Panel | PGLife Enterprise</title>
    <?php include 'includes/head_links.php'; ?>
    <style>
        .owner-sidebar { background: #0f172a; color: white; min-height: calc(100vh - 60px); padding: 20px 10px; }
        .owner-sidebar-link {
            color: #94a3b8; font-weight: 600; text-decoration: none !important; padding: 12px 16px;
            border-radius: 10px; display: block; margin-bottom: 6px; cursor: pointer; transition: all 0.2s ease;
        }
        .owner-sidebar-link:hover, .owner-sidebar-link.active { background: #0284c7; color: white; }
        .stat-card { border-radius: 14px; border: 1px solid #e2e8f0; background: white; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
    </style>
</head>
<body style="background-color: #f8fafc;">
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation -->
            <div class="col-md-2 owner-sidebar d-none d-md-block">
                <h6 class="text-uppercase text-muted font-weight-bold mb-4 px-2" style="font-size: 11px; letter-spacing: 1px;">Owner Control Panel</h6>
                <a class="owner-sidebar-link active" onclick="switchOwnerTab('tab-analytics', this)"><i class="fas fa-chart-line mr-2"></i> Analytics & Capacity</a>
                <a class="owner-sidebar-link" onclick="switchOwnerTab('tab-leads', this)"><i class="fas fa-filter mr-2"></i> Interested Leads Pipeline</a>
                <a class="owner-sidebar-link" onclick="switchOwnerTab('tab-properties', this)"><i class="fas fa-building mr-2"></i> My Properties & Photos</a>
                <a class="owner-sidebar-link" onclick="switchOwnerTab('tab-bookings', this)"><i class="fas fa-calendar-check mr-2"></i> Bookings & Agreements</a>
                <a class="owner-sidebar-link" onclick="switchOwnerTab('tab-waitlist', this)"><i class="fas fa-user-clock mr-2"></i> Waiting List Students</a>
            </div>

            <!-- Main Content Area -->
            <div class="col-md-10 p-4">
                <div class="tab-content">
                    
                    <!-- 1. Analytics & Capacity Tab -->
                    <div class="tab-pane fade show active" id="tab-analytics">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="font-weight-bold text-dark mb-0">Overview Analytics & Seat Capacity</h4>
                            <button class="btn btn-primary font-weight-bold" data-toggle="modal" data-target="#modalAddProperty"><i class="fas fa-plus-circle"></i> Add New Hostel / PG</button>
                        </div>

                        <!-- Lead Statistics Bar -->
                        <div class="row mb-4">
                            <div class="col-md-2">
                                <div class="stat-card text-center">
                                    <div class="text-muted font-weight-bold small">TOTAL LEADS</div>
                                    <h3 class="font-weight-bold text-primary mb-0"><?php echo (int)$lead_metrics['total_leads']; ?></h3>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-card text-center">
                                    <div class="text-muted font-weight-bold small">CONTACTED</div>
                                    <h3 class="font-weight-bold text-info mb-0"><?php echo (int)$lead_metrics['contacted_leads']; ?></h3>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-card text-center">
                                    <div class="text-muted font-weight-bold small">RESERVED SEATS</div>
                                    <h3 class="font-weight-bold text-warning mb-0"><?php echo (int)$lead_metrics['reserved_seats']; ?></h3>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-card text-center">
                                    <div class="text-muted font-weight-bold small">CONFIRMED</div>
                                    <h3 class="font-weight-bold text-success mb-0"><?php echo (int)$lead_metrics['confirmed_bookings']; ?></h3>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-card text-center">
                                    <div class="text-muted font-weight-bold small">NEW LEADS</div>
                                    <h3 class="font-weight-bold text-dark mb-0"><?php echo (int)$lead_metrics['pending_users']; ?></h3>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stat-card text-center">
                                    <div class="text-muted font-weight-bold small">CANCELLED</div>
                                    <h3 class="font-weight-bold text-danger mb-0"><?php echo (int)$lead_metrics['cancelled_requests']; ?></h3>
                                </div>
                            </div>
                        </div>

                        <!-- Real-Time Capacity Summary -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="text-muted font-weight-bold small">TOTAL SEATS</div>
                                    <h2 class="font-weight-bold text-primary mb-0"><?php echo (int)$prop_stats['total_seats']; ?></h2>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="text-muted font-weight-bold small">OCCUPIED SEATS</div>
                                    <h2 class="font-weight-bold text-danger mb-0"><?php echo (int)$prop_stats['total_occupied']; ?></h2>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="text-muted font-weight-bold small">RESERVED SEATS</div>
                                    <h2 class="font-weight-bold text-warning mb-0"><?php echo (int)$prop_stats['total_reserved']; ?></h2>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="text-muted font-weight-bold small">AVAILABLE SEATS</div>
                                    <h2 class="font-weight-bold text-success mb-0"><?php echo (int)$prop_stats['total_avail']; ?></h2>
                                </div>
                            </div>
                        </div>

                        <!-- Interactive Analytics Charts Row -->
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="stat-card">
                                    <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-chart-pie text-primary"></i> Seat Occupancy & Capacity Breakdown</h6>
                                    <div style="height: 250px;">
                                        <canvas id="chartOwnerOccupancy"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="stat-card">
                                    <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-filter text-info"></i> Interested Student Lead Pipeline</h6>
                                    <div style="height: 250px;">
                                        <canvas id="chartOwnerLeads"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Interested Leads Pipeline Tab -->
                    <div class="tab-pane fade" id="tab-leads" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-filter text-primary"></i> Interested Leads Pipeline</h4>
                            <div>
                                <a href="api/export_student_data.php?type=interested&format=excel" class="btn btn-sm btn-success font-weight-bold mr-2"><i class="fas fa-file-excel"></i> Export Excel (.csv)</a>
                                <a href="api/export_student_data.php?type=interested&format=pdf" target="_blank" class="btn btn-sm btn-danger font-weight-bold"><i class="fas fa-file-pdf"></i> Export PDF</a>
                            </div>
                        </div>
                        <div class="card card-custom p-4 bg-white">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Lead ID</th>
                                            <th>Student Name</th>
                                            <th>Contact Info</th>
                                            <th>Move-in / Room</th>
                                            <th>Budget</th>
                                            <th>Property</th>
                                            <th>Lead Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($interested_res) > 0) {
                                            while ($i = mysqli_fetch_assoc($interested_res)) { ?>
                                            <tr>
                                                <td>#LEAD-<?php echo $i['id']; ?></td>
                                                <td class="font-weight-bold"><?php echo htmlspecialchars($i['full_name']); ?></td>
                                                <td>📞 <?php echo htmlspecialchars($i['phone']); ?><br><small class="text-muted">✉️ <?php echo htmlspecialchars($i['email']); ?></small></td>
                                                <td>📅 <?php echo $i['move_in_date'] ? $i['move_in_date'] : 'Flexible'; ?><br><small class="text-muted">🛏️ <?php echo $i['room_preference']; ?></small></td>
                                                <td class="text-success font-weight-bold">₹<?php echo number_format($i['budget']); ?></td>
                                                <td><span class="badge badge-info"><?php echo htmlspecialchars($i['property_name']); ?></span></td>
                                                <td>
                                                    <span class="badge <?php echo $i['lead_status'] === 'Booking Confirmed' ? 'badge-success' : ($i['lead_status'] === 'New' ? 'badge-primary' : 'badge-warning'); ?>">
                                                        <?php echo strtoupper($i['lead_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="https://wa.me/91<?php echo $i['phone']; ?>" target="_blank" class="btn btn-success"><i class="fab fa-whatsapp"></i></a>
                                                        <a href="tel:<?php echo $i['phone']; ?>" class="btn btn-primary"><i class="fas fa-phone"></i></a>
                                                        <button class="btn btn-warning" onclick="openUpdateLeadModal(<?php echo $i['id']; ?>, '<?php echo $i['lead_status']; ?>')"><i class="fas fa-edit"></i></button>
                                                        <button class="btn btn-dark" onclick="convertLeadToBooking(<?php echo $i['id']; ?>)" title="Convert to Booking"><i class="fas fa-check-circle"></i></button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } } else { ?>
                                            <tr><td colspan="8" class="text-center text-muted p-4">No interested leads logged yet.</td></tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- 3. My Properties & Photo Uploads Tab -->
                    <div class="tab-pane fade" id="tab-properties" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="font-weight-bold text-dark mb-0">Property Details & Photo Gallery Manager</h4>
                            <button class="btn btn-primary font-weight-bold" data-toggle="modal" data-target="#modalAddProperty"><i class="fas fa-plus-circle"></i> Add Property</button>
                        </div>

                        <div class="card card-custom p-4 bg-white">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Property Name</th>
                                            <th>City</th>
                                            <th>Rent/Month</th>
                                            <th>Seats Info</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($properties_res) > 0) {
                                            while ($p = mysqli_fetch_assoc($properties_res)) {
                                                $pa_res = mysqli_query($conn, "SELECT amenity_id FROM properties_amenities WHERE property_id = {$p['id']}");
                                                $pa_ids = array();
                                                if ($pa_res) {
                                                    while ($pa = mysqli_fetch_assoc($pa_res)) {
                                                        $pa_ids[] = $pa['amenity_id'];
                                                    }
                                                }
                                                $amenity_str = implode(',', $pa_ids);
                                            ?>
                                            <tr>
                                                <td>#<?php echo $p['id']; ?></td>
                                                <td class="font-weight-bold"><?php echo htmlspecialchars($p['name']); ?></td>
                                                <td><?php echo htmlspecialchars($p['city_name']); ?></td>
                                                <td class="text-success font-weight-bold">₹<?php echo number_format($p['rent']); ?></td>
                                                <td><strong><?php echo $p['total_seats']; ?></strong> Total | <span class="text-success"><?php echo $p['available_seats']; ?> Avail</span></td>
                                                <td>
                                                    <span class="badge <?php echo $p['status'] === 'approved' ? 'badge-success' : 'badge-warning'; ?>">
                                                        <?php echo strtoupper($p['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="property_detail.php?property_id=<?php echo $p['id']; ?>" target="_blank" class="btn btn-outline-primary"><i class="fas fa-eye"></i> View</a>
                                                        <button class="btn btn-warning font-weight-bold btn-edit-prop" 
                                                                data-id="<?php echo $p['id']; ?>"
                                                                data-name="<?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?>"
                                                                data-city="<?php echo $p['city_id']; ?>"
                                                                data-rent="<?php echo $p['rent']; ?>"
                                                                data-gender="<?php echo htmlspecialchars($p['gender'], ENT_QUOTES); ?>"
                                                                data-address="<?php echo htmlspecialchars($p['address'], ENT_QUOTES); ?>"
                                                                data-college="<?php echo htmlspecialchars($p['near_college'], ENT_QUOTES); ?>"
                                                                data-desc="<?php echo htmlspecialchars($p['description'], ENT_QUOTES); ?>"
                                                                data-amenities="<?php echo $amenity_str; ?>">
                                                            <i class="fas fa-edit"></i> Edit Details
                                                        </button>
                                                        <button class="btn btn-info font-weight-bold" onclick="openPhotoModal(<?php echo $p['id']; ?>, '<?php echo addslashes($p['name']); ?>')">
                                                            <i class="fas fa-camera"></i> Photos
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Bookings & Agreements Tab -->
                    <div class="tab-pane fade" id="tab-bookings" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-file-contract text-primary"></i> Bookings & Rental Agreement Management</h4>
                            <div>
                                <a href="api/export_student_data.php?type=booked&format=excel" class="btn btn-sm btn-success font-weight-bold mr-2"><i class="fas fa-file-excel"></i> Export Excel (.csv)</a>
                                <a href="api/export_student_data.php?type=booked&format=pdf" target="_blank" class="btn btn-sm btn-danger font-weight-bold"><i class="fas fa-file-pdf"></i> Export PDF</a>
                            </div>
                        </div>
                        <div class="card card-custom p-4 bg-white">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Booking ID</th>
                                            <th>Student Name</th>
                                            <th>Contact Info</th>
                                            <th>Property Name</th>
                                            <th>Booking Status</th>
                                            <th>Rental Agreement & Admin Seal Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($booking_res) > 0) {
                                            while ($b = mysqli_fetch_assoc($booking_res)) {
                                                $agr_status = !empty($b['agreement_status']) ? $b['agreement_status'] : 'not_generated';
                                            ?>
                                            <tr>
                                                <td>#BK-<?php echo $b['id']; ?></td>
                                                <td class="font-weight-bold"><?php echo htmlspecialchars($b['full_name']); ?></td>
                                                <td>📞 <?php echo htmlspecialchars($b['phone']); ?><br><small class="text-muted">✉️ <?php echo htmlspecialchars($b['email']); ?></small></td>
                                                <td><?php echo htmlspecialchars($b['property_name']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $b['status'] === 'accepted' || $b['status'] === 'completed' || $b['status'] === 'Approved' ? 'badge-success' : ($b['status'] === 'rejected' ? 'badge-danger' : 'badge-warning'); ?>">
                                                        <?php echo strtoupper($b['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="mb-1">
                                                        <a href="api/generate_agreement_pdf.php?booking_id=<?php echo $b['id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary font-weight-bold">
                                                            <i class="fas fa-file-pdf text-danger"></i> Generate & View Document
                                                        </a>
                                                    </div>
                                                    <?php if ($agr_status === 'approved') { ?>
                                                        <span class="badge badge-success"><i class="fas fa-check-circle"></i> SEALED & APPROVED BY ADMIN</span>
                                                    <?php } elseif ($agr_status === 'rejected') { ?>
                                                        <span class="badge badge-danger"><i class="fas fa-times-circle"></i> REJECTED BY ADMIN</span>
                                                    <?php } elseif ($agr_status === 'pending_review') { ?>
                                                        <span class="badge badge-warning"><i class="fas fa-clock"></i> PENDING ADMIN APPROVAL & SEAL</span>
                                                    <?php } else { ?>
                                                        <span class="badge badge-secondary"><i class="fas fa-info-circle"></i> Ready to Generate</span>
                                                    <?php } ?>
                                                    <?php if (!empty($b['admin_remark'])) { ?>
                                                        <div class="small text-muted font-italic mt-1">"<?php echo htmlspecialchars($b['admin_remark']); ?>"</div>
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-success font-weight-bold mr-1" onclick="updateBooking(<?php echo $b['id']; ?>, 'accepted')">Accept</button>
                                                    <button class="btn btn-sm btn-danger font-weight-bold" onclick="updateBooking(<?php echo $b['id']; ?>, 'rejected')">Reject</button>
                                                </td>
                                            </tr>
                                        <?php } } else { ?>
                                            <tr><td colspan="7" class="text-center text-muted p-4">No booking requests received yet.</td></tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Waiting List Students Tab -->
                    <div class="tab-pane fade" id="tab-waitlist" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="font-weight-bold text-dark mb-0"><i class="fas fa-user-clock text-warning"></i> Waiting List Student Registry</h4>
                            <div>
                                <a href="api/export_student_data.php?type=waiting&format=excel" class="btn btn-sm btn-success font-weight-bold mr-2"><i class="fas fa-file-excel"></i> Export Excel (.csv)</a>
                                <a href="api/export_student_data.php?type=waiting&format=pdf" target="_blank" class="btn btn-sm btn-danger font-weight-bold"><i class="fas fa-file-pdf"></i> Export PDF</a>
                            </div>
                        </div>
                        <div class="card card-custom p-4 bg-white">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Waitlist ID</th>
                                            <th>Student Name</th>
                                            <th>Contact Info</th>
                                            <th>Hostel / PG</th>
                                            <th>Requested Room</th>
                                            <th>Preferred Move-in</th>
                                            <th>Status</th>
                                            <th>Date Joined</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($waitlist_res && mysqli_num_rows($waitlist_res) > 0) {
                                            while ($w = mysqli_fetch_assoc($waitlist_res)) { ?>
                                            <tr>
                                                <td>#WL-<?php echo $w['id']; ?></td>
                                                <td class="font-weight-bold"><?php echo htmlspecialchars($w['full_name']); ?></td>
                                                <td>📞 <?php echo htmlspecialchars($w['phone']); ?><br><small class="text-muted">✉️ <?php echo htmlspecialchars($w['email']); ?></small></td>
                                                <td><span class="badge badge-info"><?php echo htmlspecialchars($w['property_name']); ?></span></td>
                                                <td>🛏️ <?php echo htmlspecialchars($w['requested_room_type']); ?></td>
                                                <td>📅 <?php echo $w['preferred_move_in']; ?></td>
                                                <td>
                                                    <span class="badge <?php echo $w['status'] === 'converted' ? 'badge-success' : ($w['status'] === 'waiting' ? 'badge-warning' : 'badge-secondary'); ?>">
                                                        <?php echo strtoupper($w['status']); ?>
                                                    </span>
                                                </td>
                                                <td><small class="text-muted"><?php echo date('d M Y', strtotime($w['created_at'])); ?></small></td>
                                            </tr>
                                        <?php } } else { ?>
                                            <tr><td colspan="8" class="text-center text-muted p-4">No students currently on the waiting list.</td></tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>


                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Add Property (With Photo Attachment Support) -->
    <div class="modal fade" id="modalAddProperty" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="formAddProperty" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_property">
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-bold"><i class="fas fa-plus-circle text-primary"></i> Add New PG / Hostel</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold small">PG / Hostel Name *</label>
                                <input type="text" class="form-control" name="name" placeholder="e.g. Starlight Residency" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold small">City Location *</label>
                                <select class="form-control" name="city_id" required>
                                    <?php mysqli_data_seek($cities_res, 0); while ($c = mysqli_fetch_assoc($cities_res)) { ?>
                                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold small">Monthly Rent (₹/month) *</label>
                                <input type="number" class="form-control" name="rent" placeholder="9500" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold small">Gender Preference *</label>
                                <select class="form-control" name="gender" required>
                                    <option value="unisex">Unisex</option>
                                    <option value="female">Female</option>
                                    <option value="male">Male</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold small">Full Property Address *</label>
                            <input type="text" class="form-control" name="address" placeholder="House No, Street, Landmark" required>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold small text-dark"><i class="fas fa-list-check text-primary"></i> Select Amenities & Extra Facilities Available *</label>
                            <div class="row p-3 border rounded bg-white shadow-sm" style="max-height: 220px; overflow-y: auto;">
                                <?php foreach ($amenities_list as $am) { ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" name="amenities[]" value="<?php echo $am['id']; ?>" id="amenity_add_<?php echo $am['id']; ?>" checked>
                                            <label class="custom-control-label small font-weight-bold text-dark" for="amenity_add_<?php echo $am['id']; ?>">
                                                <img src="img/amenities/<?php echo $am['icon']; ?>.svg" width="16" height="16" class="mr-1" onerror="this.style.display='none'">
                                                <?php echo htmlspecialchars($am['name']); ?> <span class="text-muted font-weight-normal">(<?php echo htmlspecialchars($am['type']); ?>)</span>
                                            </label>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold small">Primary Cover Photo (Optional)</label>
                            <input type="file" class="form-control-file" name="property_image" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold small">Property Description *</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                    </div>

                    <div class="modal-footer bg-light">
                        <button type="submit" class="btn btn-primary font-weight-bold"><i class="fas fa-check"></i> Submit Property for Review</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Edit Property Details -->
    <div class="modal fade" id="modalEditProperty" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="formEditProperty">
                    <input type="hidden" name="action" value="edit_property">
                    <input type="hidden" name="property_id" id="editPropId">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title font-weight-bold text-dark"><i class="fas fa-edit"></i> Edit Property Details</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold small">PG / Hostel Name *</label>
                                <input type="text" class="form-control" name="name" id="editPropName" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold small">City Location *</label>
                                <select class="form-control" name="city_id" id="editPropCity" required>
                                    <?php mysqli_data_seek($cities_res, 0); while ($c = mysqli_fetch_assoc($cities_res)) { ?>
                                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold small">Monthly Rent (₹) *</label>
                                <input type="number" class="form-control" name="rent" id="editPropRent" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="font-weight-bold small">Gender *</label>
                                <select class="form-control" name="gender" id="editPropGender" required>
                                    <option value="unisex">Unisex</option>
                                    <option value="female">Female</option>
                                    <option value="male">Male</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold small">Full Address *</label>
                            <input type="text" class="form-control" name="address" id="editPropAddress" required>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold small">Nearby University / College</label>
                            <input type="text" class="form-control" name="near_college" id="editPropCollege">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold small text-dark"><i class="fas fa-list-check text-warning"></i> Select Amenities & Extra Facilities *</label>
                            <div class="row p-3 border rounded bg-white shadow-sm" style="max-height: 220px; overflow-y: auto;">
                                <?php foreach ($amenities_list as $am) { ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" name="amenities[]" value="<?php echo $am['id']; ?>" id="amenity_edit_<?php echo $am['id']; ?>" checked>
                                            <label class="custom-control-label small font-weight-bold text-dark" for="amenity_edit_<?php echo $am['id']; ?>">
                                                <img src="img/amenities/<?php echo $am['icon']; ?>.svg" width="16" height="16" class="mr-1" onerror="this.style.display='none'">
                                                <?php echo htmlspecialchars($am['name']); ?> <span class="text-muted font-weight-normal">(<?php echo htmlspecialchars($am['type']); ?>)</span>
                                            </label>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold small">Description *</label>
                            <textarea class="form-control" name="description" id="editPropDesc" rows="3" required></textarea>
                        </div>
                    </div>

                    <div class="modal-footer bg-light">
                        <button type="submit" class="btn btn-warning font-weight-bold text-dark"><i class="fas fa-save"></i> Save Property Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Manage Property Photos -->
    <div class="modal fade" id="modalManagePhotos" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formUploadPhoto" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_property_photos">
                    <input type="hidden" name="property_id" id="photoPropId">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title font-weight-bold"><i class="fas fa-camera"></i> Upload Property Photos</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="text-muted small">Upload high-resolution photos of rooms, building exterior, dining hall, and washrooms for <strong id="photoPropName"></strong>.</p>
                        <div class="form-group">
                            <label class="font-weight-bold small">Select Photo File (JPG / PNG) *</label>
                            <input type="file" class="form-control-file" name="photo_file" accept="image/*" required>
                        </div>
                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" name="is_cover" value="1" id="checkCover">
                            <label class="form-check-label small font-weight-bold" for="checkCover">Set as primary cover photo</label>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="submit" class="btn btn-info font-weight-bold"><i class="fas fa-upload"></i> Upload Image</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Update Lead Status -->
    <div class="modal fade" id="modalUpdateLead" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formUpdateLead">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="lead_id" id="updateLeadId">
                    <div class="modal-header">
                        <h5 class="modal-title font-weight-bold">Update Lead Status Lifecycle</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-group">
                            <label class="font-weight-bold small">Lead Status *</label>
                            <select class="form-control" name="status" id="updateLeadStatusSelect" required>
                                <option value="New">New</option>
                                <option value="Contacted">Contacted</option>
                                <option value="Seat Reserved">Seat Reserved</option>
                                <option value="Documents Pending">Documents Pending</option>
                                <option value="Booking Confirmed">Booking Confirmed</option>
                                <option value="Booking Cancelled">Booking Cancelled</option>
                                <option value="Not Interested">Not Interested</option>
                                <option value="Closed">Closed</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold small">Status Notes</label>
                            <textarea class="form-control" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary font-weight-bold">Update Lead Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
    function switchOwnerTab(targetTabId, linkEl) {
        document.querySelectorAll('.owner-sidebar-link').forEach(el => el.classList.remove('active'));
        if (linkEl) linkEl.classList.add('active');

        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.style.display = 'none';
            pane.classList.remove('show', 'active');
        });

        const targetPane = document.getElementById(targetTabId);
        if (targetPane) {
            targetPane.style.display = 'block';
            targetPane.classList.add('show', 'active');
        }
    }

    document.getElementById('formAddProperty').addEventListener('submit', function(e) {
        e.preventDefault();
        fetch('api/owner_actions.php', { method: 'POST', body: new FormData(this) })
            .then(r => r.json())
            .then(d => {
                alert(d.message);
                if (d.success) location.reload();
            });
    });

    function openEditPropertyModal(id, name, cityId, rent, gender, address, college, desc, amenitiesStr) {
        document.getElementById('editPropId').value = id;
        document.getElementById('editPropName').value = name;
        document.getElementById('editPropCity').value = cityId;
        document.getElementById('editPropRent').value = rent;
        document.getElementById('editPropGender').value = gender;
        document.getElementById('editPropAddress').value = address;
        if (document.getElementById('editPropCollege')) {
            document.getElementById('editPropCollege').value = college || '';
        }
        document.getElementById('editPropDesc').value = desc || '';

        // Uncheck all edit amenity checkboxes first
        document.querySelectorAll('input[id^="amenity_edit_"]').forEach(el => el.checked = false);

        // Check assigned amenity checkboxes
        if (amenitiesStr) {
            const ids = String(amenitiesStr).split(',');
            ids.forEach(aId => {
                const cb = document.getElementById('amenity_edit_' + aId.trim());
                if (cb) cb.checked = true;
            });
        }
        $('#modalEditProperty').modal('show');
    }

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-edit-prop');
        if (btn) {
            const id = btn.getAttribute('data-id');
            const name = btn.getAttribute('data-name');
            const city = btn.getAttribute('data-city');
            const rent = btn.getAttribute('data-rent');
            const gender = btn.getAttribute('data-gender');
            const address = btn.getAttribute('data-address');
            const college = btn.getAttribute('data-college');
            const desc = btn.getAttribute('data-desc');
            const amenities = btn.getAttribute('data-amenities');
            openEditPropertyModal(id, name, city, rent, gender, address, college, desc, amenities);
        }
    });


    document.getElementById('formEditProperty').addEventListener('submit', function(e) {
        e.preventDefault();
        fetch('api/owner_actions.php', { method: 'POST', body: new FormData(this) })
            .then(r => r.json())
            .then(d => {
                alert(d.message);
                if (d.success) location.reload();
            });
    });

    function openPhotoModal(propId, propName) {
        document.getElementById('photoPropId').value = propId;
        document.getElementById('photoPropName').innerText = propName;
        $('#modalManagePhotos').modal('show');
    }

    document.getElementById('formUploadPhoto').addEventListener('submit', function(e) {
        e.preventDefault();
        fetch('api/owner_actions.php', { method: 'POST', body: new FormData(this) })
            .then(r => r.json())
            .then(d => {
                alert(d.message);
                if (d.success) location.reload();
            });
    });

    function openUpdateLeadModal(leadId, status) {
        document.getElementById('updateLeadId').value = leadId;
        document.getElementById('updateLeadStatusSelect').value = status;
        $('#modalUpdateLead').modal('show');
    }

    document.getElementById('formUpdateLead').addEventListener('submit', function(e) {
        e.preventDefault();
        fetch('api/leads.php', { method: 'POST', body: new FormData(this) })
            .then(r => r.json())
            .then(d => {
                alert(d.message);
                if (d.success) location.reload();
            });
    });

    function convertLeadToBooking(leadId) {
        if (!confirm("Are you sure you want to convert this interested lead into a confirmed booking?")) return;
        const formData = new FormData();
        formData.append('action', 'convert_to_booking');
        formData.append('lead_id', leadId);
        fetch('api/leads.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(d => {
                alert(d.message);
                if (d.success) location.reload();
            });
    }

    document.addEventListener("DOMContentLoaded", function() {
        if (document.getElementById('chartOwnerOccupancy')) {
            new Chart(document.getElementById('chartOwnerOccupancy'), {
                type: 'doughnut',
                data: {
                    labels: ['Occupied Seats', 'Reserved Seats', 'Available Seats'],
                    datasets: [{
                        data: [<?php echo (int)$prop_stats['total_occupied']; ?>, <?php echo (int)$prop_stats['total_reserved']; ?>, <?php echo (int)$prop_stats['total_avail']; ?>],
                        backgroundColor: ['#ef4444', '#f59e0b', '#10b981']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }

        if (document.getElementById('chartOwnerLeads')) {
            new Chart(document.getElementById('chartOwnerLeads'), {
                type: 'bar',
                data: {
                    labels: ['Total Leads', 'Contacted', 'Seat Reserved', 'Confirmed', 'New Pending', 'Cancelled'],
                    datasets: [{
                        label: 'Lead Pipeline Count',
                        data: [
                            <?php echo (int)$lead_metrics['total_leads']; ?>,
                            <?php echo (int)$lead_metrics['contacted_leads']; ?>,
                            <?php echo (int)$lead_metrics['reserved_seats']; ?>,
                            <?php echo (int)$lead_metrics['confirmed_bookings']; ?>,
                            <?php echo (int)$lead_metrics['pending_users']; ?>,
                            <?php echo (int)$lead_metrics['cancelled_requests']; ?>
                        ],
                        backgroundColor: ['#0284c7', '#06b6d4', '#f59e0b', '#10b981', '#64748b', '#ef4444']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }
    });

    function updateBooking(bookingId, status) {
        const formData = new FormData();
        formData.append('action', 'update_booking_status');
        formData.append('booking_id', bookingId);
        formData.append('status', status);
        fetch('api/owner_actions.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(d => {
                alert(d.message);
                if (d.success) location.reload();
            });
    }
    </script>
</body>
</html>

