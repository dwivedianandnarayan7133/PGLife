<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/database_connect.php';
require_once __DIR__ . '/../includes/notification_helper.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(array('success' => false, 'message' => 'Unauthorized: Please log in as property owner.'));
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$user_res = mysqli_query($conn, "SELECT role FROM users WHERE id = $user_id");
$user_data = mysqli_fetch_assoc($user_res);

if ($user_data['role'] !== 'owner' && $user_data['role'] !== 'admin') {
    echo json_encode(array('success' => false, 'message' => 'Access Denied: Owner role required.'));
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

// 1. Add Property (With Image Attachments Support)
if ($action === 'add_property') {
    $city_id = (int)$_POST['city_id'];
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $gender = mysqli_real_escape_string($conn, trim($_POST['gender']));
    $rent = (int)$_POST['rent'];
    $near_college = mysqli_real_escape_string($conn, trim($_POST['near_college']));
    $is_pet = isset($_POST['is_pet_friendly']) ? 1 : 0;

    $sql = "INSERT INTO properties (city_id, owner_id, name, address, description, gender, rent, rating_clean, rating_food, rating_safety, status, is_published, near_college, is_pet_friendly, total_seats, available_seats) 
            VALUES ($city_id, $user_id, '$name', '$address', '$description', '$gender', $rent, 4.5, 4.2, 4.8, 'pending', 1, '$near_college', $is_pet, 20, 20)";
    
    if (mysqli_query($conn, $sql)) {
        $prop_id = mysqli_insert_id($conn);

        // Handle Cover / Gallery Image upload if provided
        if (isset($_FILES['property_image']) && $_FILES['property_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . "/../img/properties/$prop_id/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $ext = pathinfo($_FILES['property_image']['name'], PATHINFO_EXTENSION);
            $filename = "img_" . time() . ".$ext";
            if (move_uploaded_file($_FILES['property_image']['tmp_name'], $upload_dir . $filename)) {
                $img_url = "img/properties/$prop_id/$filename";
                mysqli_query($conn, "INSERT INTO property_images (property_id, image_url, is_cover) VALUES ($prop_id, '$img_url', 1)");
            }
        }

        // Add default rooms
        mysqli_query($conn, "INSERT INTO property_rooms (property_id, room_type, rent, capacity, available_rooms) VALUES 
                            ($prop_id, 'Single', $rent, 1, 5),
                            ($prop_id, 'Double', GREATEST(5000, $rent - 2000), 2, 5)");

        // Save selected Amenities & Extra Facilities
        if (isset($_POST['amenities']) && is_array($_POST['amenities'])) {
            foreach ($_POST['amenities'] as $a_id) {
                $a_id = (int)$a_id;
                if ($a_id > 0) {
                    mysqli_query($conn, "INSERT INTO properties_amenities (property_id, amenity_id) VALUES ($prop_id, $a_id)");
                }
            }
        } else {
            // Default essential amenities
            mysqli_query($conn, "INSERT INTO properties_amenities (property_id, amenity_id) VALUES ($prop_id, 1), ($prop_id, 2), ($prop_id, 5), ($prop_id, 8), ($prop_id, 11), ($prop_id, 12)");
        }

        // Notify Super Admin of new property submission
        $admin_res = mysqli_query($conn, "SELECT id FROM users WHERE role = 'admin'");
        while ($admin = mysqli_fetch_assoc($admin_res)) {
            create_notification($admin['id'], 'property_submission', 'New Property Submitted for Review', "Owner submitted '$name' (ID: #$prop_id) for approval.", 'admin_portal.php?tab=pending');
        }

        echo json_encode(array('success' => true, 'property_id' => $prop_id, 'message' => 'Property created successfully with selected amenities! Submitted for Super Admin review.'));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Database error: ' . mysqli_error($conn)));
    }
    exit;
}

// 2. Edit Property Details
if ($action === 'edit_property') {
    $prop_id = (int)$_POST['property_id'];
    $city_id = (int)$_POST['city_id'];
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $gender = mysqli_real_escape_string($conn, trim($_POST['gender']));
    $rent = (int)$_POST['rent'];
    $near_college = mysqli_real_escape_string($conn, trim($_POST['near_college']));

    // Verify ownership
    $is_admin_flag = ($user_data['role'] === 'admin') ? 1 : 0;
    $check = mysqli_query($conn, "SELECT id FROM properties WHERE id = $prop_id AND (owner_id = $user_id OR $is_admin_flag = 1)");
    if (mysqli_num_rows($check) === 0) {
        echo json_encode(array('success' => false, 'message' => 'Unauthorized property edit.'));
        exit;
    }

    $sql = "UPDATE properties 
            SET city_id = $city_id, name = '$name', address = '$address', description = '$description', 
                gender = '$gender', rent = $rent, near_college = '$near_college' 
            WHERE id = $prop_id";

    if (mysqli_query($conn, $sql)) {
        // Update Amenities & Extra Facilities
        if (isset($_POST['amenities']) && is_array($_POST['amenities'])) {
            mysqli_query($conn, "DELETE FROM properties_amenities WHERE property_id = $prop_id");
            foreach ($_POST['amenities'] as $a_id) {
                $a_id = (int)$a_id;
                if ($a_id > 0) {
                    mysqli_query($conn, "INSERT INTO properties_amenities (property_id, amenity_id) VALUES ($prop_id, $a_id)");
                }
            }
        }

        log_audit_action($user_id, 'edit_property', "Updated property details & amenities for #$prop_id ($name)");
        echo json_encode(array('success' => true, 'message' => 'Property details and amenities updated successfully!'));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to update property.'));
    }
    exit;
}


// 3. Upload Property Photos
if ($action === 'upload_property_photos') {
    $prop_id = (int)$_POST['property_id'];
    if (!isset($_FILES['photo_file']) || $_FILES['photo_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(array('success' => false, 'message' => 'Please select a valid image file.'));
        exit;
    }

    $upload_dir = __DIR__ . "/../img/properties/$prop_id/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $ext = pathinfo($_FILES['photo_file']['name'], PATHINFO_EXTENSION);
    $filename = "img_" . time() . '_' . rand(100, 999) . ".$ext";
    $target = $upload_dir . $filename;

    if (move_uploaded_file($_FILES['photo_file']['tmp_name'], $target)) {
        $img_url = "img/properties/$prop_id/$filename";
        $is_cover = isset($_POST['is_cover']) && $_POST['is_cover'] == '1' ? 1 : 0;

        if ($is_cover) {
            mysqli_query($conn, "UPDATE property_images SET is_cover = 0 WHERE property_id = $prop_id");
        }

        mysqli_query($conn, "INSERT INTO property_images (property_id, image_url, is_cover) VALUES ($prop_id, '$img_url', $is_cover)");

        log_audit_action($user_id, 'upload_photo', "Uploaded gallery image for Property #$prop_id");
        echo json_encode(array('success' => true, 'image_url' => $img_url, 'message' => 'Property photo uploaded successfully!'));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to upload photo.'));
    }
    exit;
}

// 4. Toggle Publish State
if ($action === 'toggle_publish') {
    $prop_id = (int)$_POST['property_id'];
    $res = mysqli_query($conn, "SELECT is_published FROM properties WHERE id = $prop_id AND owner_id = $user_id");
    if (mysqli_num_rows($res) > 0) {
        $p = mysqli_fetch_assoc($res);
        $new_state = $p['is_published'] ? 0 : 1;
        mysqli_query($conn, "UPDATE properties SET is_published = $new_state WHERE id = $prop_id");
        echo json_encode(array('success' => true, 'is_published' => $new_state, 'message' => 'Property visibility updated.'));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Property not found or unauthorized.'));
    }
    exit;
}

// 5. Update Booking Status (Accept / Reject)
if ($action === 'update_booking_status') {
    $booking_id = (int)$_POST['booking_id'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $b_res = mysqli_query($conn, "SELECT b.id, b.user_id, p.name AS property_name FROM bookings b INNER JOIN properties p ON b.property_id = p.id WHERE b.id = $booking_id AND p.owner_id = $user_id");
    if (mysqli_num_rows($b_res) > 0) {
        $b = mysqli_fetch_assoc($b_res);
        mysqli_query($conn, "UPDATE bookings SET status = '$status' WHERE id = $booking_id");

        $title = $status === 'accepted' || $status === 'Approved' ? 'Booking Accepted!' : 'Booking Request Rejected';
        $msg = "Owner has updated your booking request for '{$b['property_name']}' to: $status.";
        create_notification($b['user_id'], 'booking_status', $title, $msg, 'profile.php?tab=bookings');

        echo json_encode(array('success' => true, 'message' => "Booking marked as $status. User notified."));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Booking not found or unauthorized.'));
    }
    exit;
}

// 6. Upload Agreement Document
if ($action === 'upload_agreement') {
    $booking_id = (int)$_POST['booking_id'];
    if (!isset($_FILES['agreement_doc']) || $_FILES['agreement_doc']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(array('success' => false, 'message' => 'Please select a valid agreement document.'));
        exit;
    }

    $upload_dir = __DIR__ . '/../img/agreements/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $ext = pathinfo($_FILES['agreement_doc']['name'], PATHINFO_EXTENSION);
    $filename = 'agr_b' . $booking_id . '_' . time() . '.' . $ext;
    $target = $upload_dir . $filename;

    if (move_uploaded_file($_FILES['agreement_doc']['tmp_name'], $target)) {
        $web_path = 'img/agreements/' . $filename;
        $b_res = mysqli_query($conn, "SELECT user_id, property_id FROM bookings WHERE id = $booking_id");
        $b = mysqli_fetch_assoc($b_res);
        $u_id = (int)$b['user_id'];

        $sql = "INSERT INTO agreements (booking_id, owner_id, user_id, agreement_doc, status) 
                VALUES ($booking_id, $user_id, $u_id, '$web_path', 'pending_review')";
        mysqli_query($conn, $sql);

        // Notify Admin
        $admin_res = mysqli_query($conn, "SELECT id FROM users WHERE role = 'admin'");
        while ($admin = mysqli_fetch_assoc($admin_res)) {
            create_notification($admin['id'], 'agreement_review', 'New Rental Agreement Submitted', 'Owner uploaded agreement for Booking #' . $booking_id . '. Pending Super Admin verification.', 'admin_portal.php?tab=agreements');
        }

        echo json_encode(array('success' => true, 'message' => 'Rental agreement uploaded! Submitted to Super Admin for verification.'));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to save document.'));
    }
    exit;
}

echo json_encode(array('success' => false, 'message' => 'Invalid action request.'));
