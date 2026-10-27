<?php
session_start();
require_once 'includes/database_connect.php';
require_once 'includes/rbac.php';

$user = check_role(array('user', 'owner', 'admin'));
$user_id = (int)$user['id'];

// Fetch Saved Properties
$saved_sql = "SELECT p.id, p.name, p.address, p.rent, p.gender, c.name AS city_name 
              FROM saved_properties sp 
              INNER JOIN properties p ON sp.property_id = p.id 
              INNER JOIN cities c ON p.city_id = c.id 
              WHERE sp.user_id = $user_id ORDER BY sp.id DESC";
$saved_res = mysqli_query($conn, $saved_sql);

// Fetch Interested Properties & Owner Contact Details
$interested_sql = "SELECT p.id, p.name, p.address, p.rent, p.gender, c.name AS city_name,
                          o.full_name AS owner_name, o.phone AS owner_phone, o.email AS owner_email
                    FROM interested_users_properties iup 
                    INNER JOIN properties p ON iup.property_id = p.id 
                    INNER JOIN cities c ON p.city_id = c.id 
                    LEFT JOIN users o ON p.owner_id = o.id
                    WHERE iup.user_id = $user_id ORDER BY iup.id DESC";
$interested_res = mysqli_query($conn, $interested_sql);

// Fetch Bookings & Agreement Status
$bookings_sql = "SELECT b.id, b.room_type, b.status, b.move_in_date, b.payment_status, b.created_at, p.name AS property_name, p.address, a.agreement_doc, a.status AS agreement_status, a.admin_remark 
                 FROM bookings b 
                 INNER JOIN properties p ON b.property_id = p.id 
                 LEFT JOIN agreements a ON a.booking_id = b.id 
                 WHERE b.user_id = $user_id ORDER BY b.id DESC";
$bookings_res = mysqli_query($conn, $bookings_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | PGLife</title>
    <?php include 'includes/head_links.php'; ?>
    <style>
        .profile-header { background: linear-gradient(135deg, #0f172a, #1e293b); color: white; padding: 40px 0; border-bottom: 4px solid #0284c7; }
        .profile-avatar-wrapper { position: relative; width: 110px; height: 110px; margin: 0 auto 15px; }
        .profile-avatar-img { width: 110px; height: 110px; border-radius: 50%; object-fit: cover; border: 4px solid #0284c7; }
        .nav-tabs .nav-link { color: #475569; font-weight: 600; border: none; padding: 12px 20px; }
        .nav-tabs .nav-link.active { color: #0284c7; border-bottom: 3px solid #0284c7; background: transparent; }
        .card-custom { border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
    </style>
</head>
<body style="background-color: #f8fafc;">
    <?php include 'includes/header.php'; ?>

    <div class="profile-header text-center">
        <div class="container">
            <div class="profile-avatar-wrapper">
                <img src="<?php echo !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : 'img/man.png'; ?>" class="profile-avatar-img" id="avatarPreview" onerror="this.src='img/man.png'">
            </div>
            <h3 class="font-weight-bold mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h3>
            <p class="text-muted mb-2"><?php echo htmlspecialchars($user['email']); ?> • <?php echo htmlspecialchars($user['phone']); ?></p>
            <div>
                <span class="badge badge-info uppercase-badge mr-1"><?php echo strtoupper($user['role']); ?></span>
                <span class="badge <?php echo $user['id_verified'] === 'verified' ? 'badge-success' : ($user['id_verified'] === 'pending' ? 'badge-warning' : 'badge-secondary'); ?>">
                    <i class="fas fa-shield-alt"></i> ID <?php echo strtoupper($user['id_verified']); ?>
                </span>
            </div>
        </div>
    </div>

    <div class="container my-4">
        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs justify-content-center mb-4 bg-white rounded shadow-sm p-2" id="profileTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="tab-info-link" data-toggle="tab" href="#tab-info" role="tab"><i class="fas fa-user-edit mr-1"></i> Edit Profile</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-id-link" data-toggle="tab" href="#tab-id" role="tab"><i class="fas fa-id-card mr-1"></i> ID Verification</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-bookings-link" data-toggle="tab" href="#tab-bookings" role="tab"><i class="fas fa-calendar-alt mr-1"></i> Bookings & Agreements</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-saved-link" data-toggle="tab" href="#tab-saved" role="tab"><i class="fas fa-bookmark mr-1"></i> Saved PGs</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-interested-link" data-toggle="tab" href="#tab-interested" role="tab"><i class="fas fa-heart mr-1"></i> Interested List</a>
            </li>
        </ul>

        <div class="tab-content" id="profileTabContent">
            <!-- 1. Edit Profile Tab -->
            <div class="tab-pane fade show active" id="tab-info" role="tabpanel">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card card-custom p-4 bg-white">
                            <h5 class="font-weight-bold text-dark mb-3"><i class="fas fa-user-circle text-primary"></i> Personal Details</h5>
                            <form id="formUpdateProfile" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="update_info">
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label class="font-weight-bold">Full Name</label>
                                        <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label class="font-weight-bold">Phone Number</label>
                                        <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label class="font-weight-bold">Email Address</label>
                                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label class="font-weight-bold">College / Institution</label>
                                        <input type="text" class="form-control" name="college_name" value="<?php echo htmlspecialchars($user['college_name']); ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="font-weight-bold">Residential Address</label>
                                    <textarea class="form-control" name="address" rows="2"><?php echo htmlspecialchars($user['address']); ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="font-weight-bold">Update Profile Picture</label>
                                    <input type="file" class="form-control-file" name="profile_image" accept="image/*">
                                </div>
                                <button type="submit" class="btn btn-primary font-weight-bold"><i class="fas fa-save"></i> Save Profile Details</button>
                            </form>

                            <hr class="my-4">

                            <h5 class="font-weight-bold text-dark mb-3"><i class="fas fa-lock text-warning"></i> Change Password</h5>
                            <form id="formChangePassword">
                                <input type="hidden" name="action" value="change_password">
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label class="font-weight-bold">Current Password</label>
                                        <input type="password" class="form-control" name="old_password" required>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label class="font-weight-bold">New Password</label>
                                        <input type="password" class="form-control" name="new_password" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-warning font-weight-bold text-dark"><i class="fas fa-key"></i> Update Password</button>
                            </form>

                            <hr class="my-4">

                            <!-- Danger Zone: Account Deletion Request -->
                            <div class="p-3 border border-danger rounded bg-light">
                                <h6 class="font-weight-bold text-danger mb-2"><i class="fas fa-exclamation-triangle"></i> Account Deletion Request (Admin Approval Required)</h6>
                                <p class="small text-muted mb-3">You can submit an account deletion request. Accounts are reviewed & approved by Super Admin. Account data & records will be preserved for compliance and audit logging.</p>

                                <?php if ($user['deletion_status'] === 'pending_approval') { ?>
                                    <div class="alert alert-warning py-2 small font-weight-bold mb-0">
                                        <i class="fas fa-clock"></i> Account Deletion Pending Review (Requested on <?php echo $user['deletion_requested_at']; ?>). Admin is reviewing your request.
                                    </div>
                                <?php } elseif ($user['deletion_status'] === 'rejected') { ?>
                                    <div class="alert alert-danger py-2 small mb-2">
                                        <strong>Request Rejected:</strong> <?php echo htmlspecialchars($user['deletion_admin_remark']); ?>
                                    </div>
                                    <button class="btn btn-sm btn-outline-danger font-weight-bold" data-toggle="modal" data-target="#modalRequestDeletion">
                                        <i class="fas fa-trash-alt"></i> Re-submit Account Deletion Request
                                    </button>
                                <?php } else { ?>
                                    <button class="btn btn-sm btn-outline-danger font-weight-bold" data-toggle="modal" data-target="#modalRequestDeletion">
                                        <i class="fas fa-user-times"></i> Request Account Deletion
                                    </button>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. ID Verification Tab -->
            <div class="tab-pane fade" id="tab-id" role="tabpanel">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card card-custom p-4 bg-white text-center">
                            <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                            <h5 class="font-weight-bold">Government Identity Proof</h5>
                            <p class="text-muted" style="font-size: 13px;">Upload your Aadhaar Card, Passport, or Govt ID for student safety verification.</p>
                            
                            <div class="alert alert-info py-2" style="font-size: 13px;">
                                Current Status: <strong><?php echo strtoupper($user['id_verified']); ?></strong>
                            </div>

                            <?php if (!empty($user['id_proof_doc'])) { ?>
                                <div class="mb-3">
                                    <a href="<?php echo htmlspecialchars($user['id_proof_doc']); ?>" target="_blank" class="btn btn-outline-info btn-sm"><i class="fas fa-eye"></i> View Uploaded Document</a>
                                </div>
                            <?php } ?>

                            <form id="formUploadID" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="upload_id_proof">
                                <div class="form-group">
                                    <input type="file" class="form-control-file" name="id_proof" required>
                                </div>
                                <button type="submit" class="btn btn-success font-weight-bold"><i class="fas fa-upload"></i> Upload Identity Proof</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Bookings & Agreements Tab -->
            <div class="tab-pane fade" id="tab-bookings" role="tabpanel">
                <div class="card card-custom p-4 bg-white">
                    <h5 class="font-weight-bold mb-3"><i class="fas fa-receipt text-success"></i> My Bookings & Rental Agreements</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="thead-light">
                                <tr>
                                    <th>Booking ID</th>
                                    <th>PG / Hostel</th>
                                    <th>Room Type</th>
                                    <th>Booking Status</th>
                                    <th>Official Agreement & Admin Seal Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($bookings_res) > 0) { 
                                    while ($b = mysqli_fetch_assoc($bookings_res)) {
                                        $agr_status = !empty($b['agreement_status']) ? $b['agreement_status'] : 'pending_review';
                                    ?>
                                    <tr>
                                        <td>#BK-<?php echo $b['id']; ?></td>
                                        <td class="font-weight-bold"><?php echo htmlspecialchars($b['property_name']); ?></td>
                                        <td><span class="badge badge-light border"><?php echo $b['room_type']; ?></span></td>
                                        <td>
                                            <span class="badge <?php echo $b['status'] === 'accepted' || $b['status'] === 'completed' ? 'badge-success' : ($b['status'] === 'rejected' ? 'badge-danger' : 'badge-warning'); ?>">
                                                <?php echo strtoupper($b['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="mb-1">
                                                <a href="api/generate_agreement_pdf.php?booking_id=<?php echo $b['id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary font-weight-bold">
                                                    <i class="fas fa-file-contract"></i> View Official Agreement
                                                </a>
                                            </div>
                                            <?php if ($agr_status === 'approved'): ?>
                                                <span class="badge badge-success"><i class="fas fa-check-circle"></i> SEALED & APPROVED BY SUPER ADMIN</span>
                                            <?php elseif ($agr_status === 'rejected'): ?>
                                                <span class="badge badge-danger"><i class="fas fa-times-circle"></i> REJECTED BY ADMIN</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning"><i class="fas fa-clock"></i> PENDING ADMIN SEAL</span>
                                            <?php endif; ?>
                                            <?php if (!empty($b['admin_remark'])) { ?>
                                                <div class="small text-muted font-italic mt-1">"<?php echo htmlspecialchars($b['admin_remark']); ?>"</div>
                                            <?php } ?>
                                        </td>
                                        <td style="font-size: 12px;"><?php echo $b['created_at']; ?></td>
                                    </tr>
                                <?php } } else { ?>
                                    <tr><td colspan="6" class="text-center text-muted p-4">No active booking requests found. Explore properties to book a room!</td></tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 4. Saved PGs Tab -->
            <div class="tab-pane fade" id="tab-saved" role="tabpanel">
                <div class="row">
                    <?php if (mysqli_num_rows($saved_res) > 0) { 
                        while ($p = mysqli_fetch_assoc($saved_res)) { ?>
                            <div class="col-md-4 mb-3">
                                <div class="card card-custom p-3 bg-white h-100">
                                    <h6 class="font-weight-bold text-primary mb-1"><?php echo htmlspecialchars($p['name']); ?></h6>
                                    <p class="text-muted mb-2" style="font-size: 12px;">📍 <?php echo htmlspecialchars($p['address']); ?></p>
                                    <div class="d-flex justify-content-between align-items-center mt-auto">
                                        <span class="font-weight-bold text-success">₹<?php echo number_format($p['rent']); ?>/mo</span>
                                        <a href="property_detail.php?property_id=<?php echo $p['id']; ?>" class="btn btn-sm btn-primary">View PG</a>
                                    </div>
                                </div>
                            </div>
                    <?php } } else { ?>
                        <div class="col-12 text-center text-muted p-5">You haven't bookmarked any PG accommodations yet.</div>
                    <?php } ?>
                </div>
            </div>

            <!-- 5. Interested List Tab with Owner Mobile & Contact Details -->
            <div class="tab-pane fade" id="tab-interested" role="tabpanel">
                <div class="row">
                    <?php if (mysqli_num_rows($interested_res) > 0) { 
                        while ($p = mysqli_fetch_assoc($interested_res)) {
                            $o_name = !empty($p['owner_name']) ? $p['owner_name'] : 'Rajesh Sharma (Owner)';
                            $o_phone = !empty($p['owner_phone']) ? $p['owner_phone'] : '9876543210';
                            $o_email = !empty($p['owner_email']) ? $p['owner_email'] : 'owner@pglife.com';
                        ?>
                            <div class="col-md-6 mb-3">
                                <div class="card card-custom p-3 bg-white h-100 border-danger shadow-sm">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="font-weight-bold text-danger mb-0"><i class="fas fa-heart"></i> <?php echo htmlspecialchars($p['name']); ?></h6>
                                        <span class="badge badge-light border text-success font-weight-bold">₹<?php echo number_format($p['rent']); ?>/mo</span>
                                    </div>
                                    <p class="text-muted mb-2" style="font-size: 12px;">📍 <?php echo htmlspecialchars($p['address']); ?></p>

                                    <!-- Owner Contact Info Section -->
                                    <div class="p-2 mb-3 rounded bg-light border border-info" style="font-size: 12px;">
                                        <div class="font-weight-bold text-info mb-1"><i class="fas fa-user-shield"></i> Property Owner Contact Information:</div>
                                        <div><strong>Owner Name:</strong> <?php echo htmlspecialchars($o_name); ?></div>
                                        <div><strong>Mobile Phone:</strong> <span class="text-primary font-weight-bold">📞 <?php echo htmlspecialchars($o_phone); ?></span></div>
                                        <div class="text-muted"><strong>Email:</strong> ✉️ <?php echo htmlspecialchars($o_email); ?></div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mt-auto">
                                        <div class="btn-group btn-group-sm">
                                            <a href="tel:<?php echo $o_phone; ?>" class="btn btn-success font-weight-bold"><i class="fas fa-phone-alt"></i> Call Owner</a>
                                            <a href="https://wa.me/91<?php echo $o_phone; ?>" target="_blank" class="btn btn-outline-success font-weight-bold"><i class="fab fa-whatsapp"></i> WhatsApp</a>
                                        </div>
                                        <a href="property_detail.php?property_id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-danger font-weight-bold">View Details</a>
                                    </div>
                                </div>
                            </div>
                    <?php } } else { ?>
                        <div class="col-12 text-center text-muted p-5">No interested PGs recorded yet.</div>
                    <?php } ?>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal: Request Account Deletion -->
    <div class="modal fade" id="modalRequestDeletion" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formRequestDeletion">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title font-weight-bold"><i class="fas fa-exclamation-triangle"></i> Account Deletion Request</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-warning small">
                            <i class="fas fa-info-circle"></i> Account deletion requires Super Admin approval. Once approved, your account access will be deactivated, but historical records will be preserved for compliance.
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold small">Reason for Account Deletion *</label>
                            <textarea class="form-control" name="reason" rows="3" placeholder="Please explain why you want to delete/deactivate your account..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger font-weight-bold"><i class="fas fa-paper-plane"></i> Submit Deletion Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
    // AJAX Profile Form Handler
    document.getElementById('formUpdateProfile').addEventListener('submit', function(e) {
        e.preventDefault();
        fetch('api/update_profile.php', { method: 'POST', body: new FormData(this) })
            .then(r => r.json())
            .then(d => {
                alert(d.message);
                if (d.success) location.reload();
            });
    });

    document.getElementById('formChangePassword').addEventListener('submit', function(e) {
        e.preventDefault();
        fetch('api/update_profile.php', { method: 'POST', body: new FormData(this) })
            .then(r => r.json())
            .then(d => {
                alert(d.message);
                if (d.success) this.reset();
            });
    });

    document.getElementById('formUploadID').addEventListener('submit', function(e) {
        e.preventDefault();
        fetch('api/update_profile.php', { method: 'POST', body: new FormData(this) })
            .then(r => r.json())
            .then(d => {
                alert(d.message);
                if (d.success) location.reload();
            });
    });

    document.getElementById('formRequestDeletion')?.addEventListener('submit', function(e) {
        e.preventDefault();
        fetch('api/request_account_deletion.php', { method: 'POST', body: new FormData(this) })
            .then(r => r.json())
            .then(d => {
                alert(d.message);
                if (d.success) location.reload();
            });
    });
    </script>
</body>
</html>
