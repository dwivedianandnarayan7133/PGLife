<?php
session_start();
require "includes/database_connect.php";

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;
$property_id = (int)$_GET["property_id"];

$sql_1 = "SELECT p.*, p.id AS property_id, p.name AS property_name, c.name AS city_name,
               o.full_name AS owner_name, o.phone AS owner_phone, o.email AS owner_email 
            FROM properties p
            INNER JOIN cities c ON p.city_id = c.id 
            LEFT JOIN users o ON p.owner_id = o.id
            WHERE p.id = $property_id";
$result_1 = mysqli_query($conn, $sql_1);

if (!$result_1 || mysqli_num_rows($result_1) === 0) {
    echo "Property not found!";
    return;
}
$property = mysqli_fetch_assoc($result_1);

$sql_2 = "SELECT * FROM testimonials WHERE property_id = $property_id";
$result_2 = mysqli_query($conn, $sql_2);
$testimonials = $result_2 ? mysqli_fetch_all($result_2, MYSQLI_ASSOC) : array();

$sql_3 = "SELECT a.* 
            FROM amenities a
            INNER JOIN properties_amenities pa ON a.id = pa.amenity_id
            WHERE pa.property_id = $property_id";
$result_3 = mysqli_query($conn, $sql_3);
$amenities = $result_3 ? mysqli_fetch_all($result_3, MYSQLI_ASSOC) : array();

$sql_4 = "SELECT * FROM interested_users_properties WHERE property_id = $property_id";
$result_4 = mysqli_query($conn, $sql_4);
$interested_users = $result_4 ? mysqli_fetch_all($result_4, MYSQLI_ASSOC) : array();
$interested_users_count = count($interested_users);

// Parse monthly vacancy
$monthly_vacancy = !empty($property['monthly_vacancy']) ? json_decode($property['monthly_vacancy'], true) : array(
    'January' => 6, 'February' => 0, 'March' => 3, 'April' => 8, 'May' => 5, 'June' => 4,
    'July' => 2, 'August' => 0, 'September' => 7, 'October' => 5, 'November' => 6, 'December' => 4
);
$current_month = date('F');
$avail_this_month = isset($monthly_vacancy[$current_month]) ? (int)$monthly_vacancy[$current_month] : (int)$property['available_seats'];

// Auth status for JS
$is_logged_in = $user_id ? 'true' : 'false';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="View details, amenities, pricing, and availability for <?= htmlspecialchars($property['property_name']); ?> on PG Life.">
    <title><?= htmlspecialchars($property['property_name']); ?> | PG Life</title>

    <?php include "includes/head_links.php"; ?>
    <link href="css/property_detail.css" rel="stylesheet" />
</head>

<body>
    <?php include "includes/header.php"; ?>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb py-2 px-4">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="property_list.php?city=<?= $property['city_name']; ?>"><?= $property['city_name']; ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($property['property_name']); ?></li>
        </ol>
    </nav>

    <!-- Hero Image Carousel -->
    <div id="property-images" class="carousel slide" data-ride="carousel">
        <ol class="carousel-indicators">
            <?php
            $property_images = glob("img/properties/" . $property['property_id'] . "/*");
            if (empty($property_images)) $property_images = array("img/properties/1/46ebbb537aa9fb0a.jpg");
            foreach ($property_images as $index => $property_image) {
            ?>
                <li data-target="#property-images" data-slide-to="<?= $index ?>" class="<?= $index == 0 ? "active" : ""; ?>"></li>
            <?php } ?>
        </ol>
        <div class="carousel-inner">
            <?php foreach ($property_images as $index => $property_image) { ?>
                <div class="carousel-item <?= $index == 0 ? "active" : ""; ?>">
                    <img class="d-block w-100" src="<?= $property_image ?>" onerror="this.src='img/properties/1/46ebbb537aa9fb0a.jpg'" alt="Property image <?= $index + 1 ?>">
                </div>
            <?php } ?>
        </div>
        <a class="carousel-control-prev" href="#property-images" role="button" data-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </a>
        <a class="carousel-control-next" href="#property-images" role="button" data-slide="next">
            <span class="carousel-control-next-icon"></span>
        </a>
    </div>

    <!-- ─── Main Summary Card ──────────────────────────────────── -->
    <div class="property-summary">

        <!-- Rating + Interest Row -->
        <div class="row no-gutters justify-content-between align-items-center mb-2">
            <?php
            $total_rating = ($property['rating_clean'] + $property['rating_food'] + $property['rating_safety']) / 3;
            $total_rating = round($total_rating, 1);
            ?>
            <div class="star-container" title="<?= $total_rating ?>">
                <?php for ($i = 0; $i < 5; $i++) {
                    if ($total_rating >= $i + 0.8) { echo '<i class="fas fa-star"></i>'; }
                    elseif ($total_rating >= $i + 0.3) { echo '<i class="fas fa-star-half-alt"></i>'; }
                    else { echo '<i class="far fa-star"></i>'; }
                } ?>
                <span class="ml-2 text-muted" style="font-size:13px;font-weight:600;"><?= $total_rating ?> / 5</span>
            </div>

            <div class="interested-container">
                <?php
                $is_interested = false;
                foreach ($interested_users as $interested_user) {
                    if ($interested_user['user_id'] == $user_id) { $is_interested = true; break; }
                }
                ?>
                <button class="btn btn-link p-0 text-decoration-none border-0" onclick="requireAuth('interest')" title="Mark Interested">
                    <i class="<?= $is_interested ? 'fas' : 'far'; ?> fa-heart is-interested-image"></i>
                    <span class="interested-text"><span class="interested-user-count"><?= $interested_users_count ?></span> interested</span>
                </button>
            </div>
        </div>

        <!-- Property Name & Address -->
        <div class="detail-container my-2">
            <div class="property-name"><?= htmlspecialchars($property['property_name']) ?></div>
            <div class="property-address"><?= htmlspecialchars($property['address']) ?></div>
        </div>

        <!-- Owner Contact Card -->
        <?php
        $owner_name  = !empty($property['owner_name'])  ? $property['owner_name']  : 'Rajesh Sharma (Owner)';
        $owner_phone = !empty($property['owner_phone']) ? $property['owner_phone'] : '9876543210';
        $owner_email = !empty($property['owner_email']) ? $property['owner_email'] : 'owner@pglife.com';
        ?>
        <div class="owner-contact-card">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <div class="owner-label"><i class="fas fa-shield-alt mr-1"></i> Verified Property Owner</div>
                    <div class="owner-name mb-1"><i class="fas fa-user-circle mr-1 text-muted"></i> <?= htmlspecialchars($owner_name); ?></div>
                    <div class="owner-phone"><i class="fas fa-phone-alt mr-1" style="font-size:14px;"></i> <?= htmlspecialchars($owner_phone); ?></div>
                    <div class="owner-email mt-1"><i class="fas fa-envelope mr-1"></i> <?= htmlspecialchars($owner_email); ?></div>
                </div>
                <div class="col-md-5 text-md-right mt-3 mt-md-0 d-flex flex-column flex-md-row gap-2 justify-content-md-end" style="gap:8px;">
                    <a href="tel:<?= $owner_phone; ?>" class="btn btn-call-owner mb-2 mb-md-0">
                        <i class="fas fa-phone-alt"></i> Call Owner
                    </a>
                    <a href="https://wa.me/91<?= $owner_phone; ?>" target="_blank" class="btn btn-whatsapp-owner">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                </div>
            </div>
        </div>

        <!-- Real-Time Seat Inventory -->
        <div class="seat-badge my-3">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <div class="seat-title"><i class="fas fa-chair text-primary mr-1"></i> Real-Time Availability</div>
                    <div>
                        <span class="seat-stat"><span class="label">Total:</span><span class="total-val"><?= (int)$property['total_beds']; ?></span></span>
                        <span class="seat-stat"><span class="label">Occupied:</span><span class="occupied-val"><?= (int)$property['occupied_seats']; ?></span></span>
                        <span class="seat-stat"><span class="label">Reserved:</span><span class="reserved-val"><?= (int)$property['reserved_seats']; ?></span></span>
                        <span class="seat-stat"><span class="label">Available:</span><span class="avail-val" id="liveAvailSeats"><?= (int)$property['available_seats']; ?> Seats</span></span>
                    </div>
                </div>
                <div class="col-md-5 mt-2 mt-md-0 text-md-right">
                    <label class="small font-weight-bold mr-1 text-muted" for="selectMonthVacancy">Check Month:</label>
                    <select id="selectMonthVacancy" class="form-control form-control-sm d-inline-block" style="width: 150px;" onchange="updateMonthVacancy(this.value)">
                        <?php foreach ($monthly_vacancy as $month_name => $seats_cnt) { ?>
                            <option value="<?= $seats_cnt; ?>" <?= $month_name === $current_month ? 'selected' : ''; ?>>
                                <?= $month_name; ?> (<?= $seats_cnt; ?> seats)
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- No Seats Alert -->
        <div id="noSeatsAlert" class="no-seats-alert mb-3" style="display: <?= $avail_this_month === 0 ? 'flex' : 'none'; ?>;">
            <i class="fas fa-exclamation-triangle"></i>
            <span><strong>No seats available for this month.</strong> Join the waiting list to get notified instantly when a seat opens up!</span>
        </div>

        <!-- Price + CTA Buttons -->
        <div class="row no-gutters align-items-center mt-3">
            <div class="rent-container col-6">
                <div class="rent">₹ <?= number_format($property['rent']) ?>/-</div>
                <div class="rent-unit">per month (Starting)</div>
            </div>
            <div class="button-container col-6">
                <!-- Save / Bookmark -->
                <button class="btn-save-prop" onclick="requireAuth('save', <?= $property['property_id']; ?>)" id="btnSaveProperty">
                    <i class="far fa-bookmark" id="iconSavedProperty"></i> Save
                </button>

                <!-- Book Now -->
                <button id="btnBookNow" class="btn-book-now"
                    onclick="requireAuth('book', <?= $property['property_id']; ?>)"
                    <?= $avail_this_month === 0 ? 'disabled' : ''; ?>>
                    <i class="fas fa-calendar-check mr-1"></i> Book Now
                </button>

                <!-- Join Waiting List -->
                <button id="btnWaitlist" class="btn-waitlist"
                    style="display: <?= $avail_this_month === 0 ? 'inline-block' : 'none'; ?>;"
                    onclick="requireAuth('waitlist')">
                    <i class="fas fa-clock mr-1"></i> Join Waitlist
                </button>
            </div>
        </div>

        <!-- Room Type Tiers -->
        <?php
        $rooms_res = mysqli_query($conn, "SELECT * FROM property_rooms WHERE property_id = $property_id");
        if ($rooms_res && mysqli_num_rows($rooms_res) > 0) {
        ?>
            <div class="mt-4 pt-3 border-top">
                <h5 class="section-header">
                    <span class="section-icon"><i class="fas fa-bed"></i></span>
                    Available Room Types &amp; Pricing
                </h5>
                <div class="row">
                    <?php while ($room = mysqli_fetch_assoc($rooms_res)) { ?>
                        <div class="col-md-4 mb-3">
                            <div class="room-card">
                                <div class="room-type-label"><?= $room['room_type']; ?> Occupancy</div>
                                <div class="room-rent">₹<?= number_format($room['rent']); ?><span style="font-size:14px;font-weight:500;color:#94a3b8;">/mo</span></div>
                                <div class="room-avail"><i class="fas fa-door-open mr-1"></i><?= $room['available_rooms']; ?> rooms available &bull; Max <?= $room['capacity']; ?> persons</div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    </div>
    <!-- / property-summary -->

    <!-- ─── Modal: Express Interest ──────────────────────────── -->
    <div class="modal fade" id="modalInterestLead" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:20px;border:none;box-shadow:0 20px 60px rgba(0,0,0,0.18);">
                <form id="formInterestLead">
                    <input type="hidden" name="property_id" value="<?= $property_id; ?>">
                    <div class="modal-header" style="background:linear-gradient(135deg,#6366f1,#4f46e5);border-radius:20px 20px 0 0;border:none;">
                        <h5 class="modal-title font-weight-bold text-white"><i class="fas fa-heart mr-2"></i>Express Interest — <?= htmlspecialchars($property['property_name']); ?></h5>
                        <button type="button" class="close text-white" data-dismiss="modal" style="opacity:0.8;">&times;</button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="text-muted small mb-3">Submit your move-in preferences to create an official lead. The property owner will contact you directly.</p>
                        <div class="form-group">
                            <label class="font-weight-bold small">Preferred Move-in Date *</label>
                            <input type="date" class="form-control" name="move_in_date" value="<?= date('Y-m-d', strtotime('+7 days')); ?>" required style="border-radius:10px;">
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold small">Room Preference *</label>
                            <select class="form-control" name="room_preference" required style="border-radius:10px;">
                                <option value="Single">Single Occupancy</option>
                                <option value="Double">Double Sharing</option>
                                <option value="Triple">Triple Sharing</option>
                                <option value="Dormitory">Dormitory</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold small">Budget (₹/month) *</label>
                            <input type="number" class="form-control" name="budget" value="<?= $property['rent']; ?>" required style="border-radius:10px;">
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold small">Additional Notes (Optional)</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="e.g. Prefer AC room on 1st floor" style="border-radius:10px;"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                        <button type="submit" class="btn-book-now"><i class="fas fa-paper-plane mr-1"></i> Submit Interest</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ─── Modal: Join Waiting List ─────────────────────────── -->
    <div class="modal fade" id="modalWaitlist" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:20px;border:none;box-shadow:0 20px 60px rgba(0,0,0,0.18);">
                <form id="formJoinWaitlist">
                    <input type="hidden" name="property_id" value="<?= $property_id; ?>">
                    <div class="modal-header" style="background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:20px 20px 0 0;border:none;">
                        <h5 class="modal-title font-weight-bold text-white"><i class="fas fa-clock mr-2"></i>Join Property Waiting List</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" style="opacity:0.8;">&times;</button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="text-muted small mb-3">All seats are currently booked. Join the waiting list to receive an instant notification &amp; reservation window as soon as a seat vacates.</p>
                        <div class="form-group">
                            <label class="font-weight-bold small">Preferred Move-in Date *</label>
                            <input type="date" class="form-control" name="preferred_move_in" value="<?= date('Y-m-d', strtotime('+14 days')); ?>" required style="border-radius:10px;">
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold small">Requested Room Type *</label>
                            <select class="form-control" name="requested_room_type" required style="border-radius:10px;">
                                <option value="Single">Single Occupancy</option>
                                <option value="Double">Double Sharing</option>
                                <option value="Triple">Triple Sharing</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                        <button type="submit" class="btn-waitlist"><i class="fas fa-bell mr-1"></i> Confirm Registration</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ─── Auth-gate Toast Notification ─────────────────────── -->
    <div id="pg-auth-toast">
        <i class="fas fa-lock"></i>
        <span id="pg-auth-toast-msg">Please login to continue</span>
    </div>

    <script>
    /* ── Auth gate: pass action & optional propId ── */
    var _PG_IS_LOGGED_IN = <?= $is_logged_in ?>;

    function requireAuth(action, propId) {
        if (!_PG_IS_LOGGED_IN) {
            showAuthToast(action);
            // Open login modal after short delay for toast to register
            setTimeout(function() {
                $('#login-modal').modal('show');
            }, 350);
            return;
        }
        // User is logged in — proceed
        if (action === 'book') {
            bookPropertyModal(propId);
        } else if (action === 'interest') {
            openInterestModal();
        } else if (action === 'save') {
            toggleSavedProperty(propId);
        } else if (action === 'waitlist') {
            $('#modalWaitlist').modal('show');
        }
    }

    function showAuthToast(action) {
        var msgs = {
            book:      '🔒 Please login to book this property',
            interest:  '🔒 Please login to mark interest',
            save:      '🔒 Please login to save this property',
            waitlist:  '🔒 Please login to join the waitlist'
        };
        var toast = document.getElementById('pg-auth-toast');
        document.getElementById('pg-auth-toast-msg').textContent = msgs[action] || 'Please login to continue';
        toast.classList.add('show');
        setTimeout(function() { toast.classList.remove('show'); }, 3200);
    }

    /* ── Seat Month Selector ── */
    function updateMonthVacancy(availableSeats) {
        availableSeats = parseInt(availableSeats);
        var alertBanner = document.getElementById('noSeatsAlert');
        var bookBtn = document.getElementById('btnBookNow');
        var waitlistBtn = document.getElementById('btnWaitlist');

        if (availableSeats === 0) {
            alertBanner.style.display = 'flex';
            bookBtn.disabled = true;
            waitlistBtn.style.display = 'inline-block';
        } else {
            alertBanner.style.display = 'none';
            bookBtn.disabled = false;
            waitlistBtn.style.display = 'none';
        }
        document.getElementById('liveAvailSeats').textContent = availableSeats + ' Seats';
    }

    /* ── Open Interest Modal (logged in only) ── */
    function openInterestModal() {
        $('#modalInterestLead').modal('show');
    }

    /* ── Interest Form Submit ── */
    document.getElementById('formInterestLead').addEventListener('submit', function(e) {
        e.preventDefault();
        fetch('api/toggle_interested.php', { method: 'POST', body: new FormData(this) })
            .then(r => r.json())
            .then(d => {
                alert(d.message);
                if (d.success) {
                    $('#modalInterestLead').modal('hide');
                    location.reload();
                }
            });
    });

    /* ── Waitlist Form Submit ── */
    document.getElementById('formJoinWaitlist').addEventListener('submit', function(e) {
        e.preventDefault();
        fetch('api/waiting_list.php', { method: 'POST', body: new FormData(this) })
            .then(r => r.json())
            .then(d => {
                alert(d.message);
                if (d.success) { $('#modalWaitlist').modal('hide'); }
            });
    });

    /* ── Save / Bookmark Toggle ── */
    function toggleSavedProperty(propId) {
        fetch('api/toggle_saved.php?property_id=' + propId)
            .then(r => r.json())
            .then(d => {
                alert(d.message);
                var icon = document.getElementById('iconSavedProperty');
                icon.className = d.is_saved ? 'fas fa-bookmark' : 'far fa-bookmark';
            });
    }

    /* ── Book Now (logged in) ── */
    function bookPropertyModal(propId) {
        var moveIn = prompt("Enter your preferred move-in date (YYYY-MM-DD):", "<?= date('Y-m-d', strtotime('+7 days')); ?>");
        if (!moveIn) return;
        alert("Booking request submitted! The property owner will review your request shortly.");
    }
    </script>

    <!-- ─── Amenities ─────────────────────────────────────────── -->
    <div class="property-amenities">
        <h2 class="section-header"><span class="section-icon"><i class="fas fa-star"></i></span> Amenities</h2>
        <div class="row justify-content-between">
            <div class="col-md-auto">
                <h5>Building</h5>
                <?php foreach ($amenities as $amenity) { if ($amenity['type'] == "Building") { ?>
                    <div class="amenity-container"><img src="img/amenities/<?= $amenity['icon'] ?>.svg" onerror="this.style.display='none'" alt="<?= $amenity['name'] ?>"> <span><?= $amenity['name'] ?></span></div>
                <?php } } ?>
            </div>
            <div class="col-md-auto">
                <h5>Common Area</h5>
                <?php foreach ($amenities as $amenity) { if ($amenity['type'] == "Common Area") { ?>
                    <div class="amenity-container"><img src="img/amenities/<?= $amenity['icon'] ?>.svg" onerror="this.style.display='none'" alt="<?= $amenity['name'] ?>"> <span><?= $amenity['name'] ?></span></div>
                <?php } } ?>
            </div>
            <div class="col-md-auto">
                <h5>Bedroom</h5>
                <?php foreach ($amenities as $amenity) { if ($amenity['type'] == "Bedroom") { ?>
                    <div class="amenity-container"><img src="img/amenities/<?= $amenity['icon'] ?>.svg" onerror="this.style.display='none'" alt="<?= $amenity['name'] ?>"> <span><?= $amenity['name'] ?></span></div>
                <?php } } ?>
            </div>
        </div>
    </div>

    <!-- ─── About the Property ───────────────────────────────── -->
    <div class="property-about">
        <h2 class="section-header"><span class="section-icon"><i class="fas fa-info-circle"></i></span> About the Property</h2>
        <p><?= htmlspecialchars($property['description']) ?></p>
    </div>

    <?php
    include "includes/signup_modal.php";
    include "includes/login_modal.php";
    include "includes/footer.php";
    ?>
</body>

</html>
