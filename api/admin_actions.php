<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/database_connect.php';
require_once __DIR__ . '/../includes/notification_helper.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(array('success' => false, 'message' => 'Unauthorized: Please log in.'));
    exit;
}

$admin_id = (int)$_SESSION['user_id'];
$admin_res = mysqli_query($conn, "SELECT role FROM users WHERE id = $admin_id");
$admin_data = mysqli_fetch_assoc($admin_res);

if ($admin_data['role'] !== 'admin') {
    echo json_encode(array('success' => false, 'message' => 'Access Denied: Super Admin role required.'));
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

// 1. Property Review (Approve / Reject / Suspend / Restore)
if ($action === 'review_property') {
    $prop_id = (int)$_POST['property_id'];
    $status = mysqli_real_escape_string($conn, $_POST['status']); // 'approved', 'rejected', 'suspended'
    $notes = isset($_POST['notes']) ? mysqli_real_escape_string($conn, trim($_POST['notes'])) : '';

    $res = mysqli_query($conn, "SELECT owner_id, name FROM properties WHERE id = $prop_id");
    if (mysqli_num_rows($res) > 0) {
        $p = mysqli_fetch_assoc($res);
        mysqli_query($conn, "UPDATE properties SET status = '$status', admin_notes = '$notes' WHERE id = $prop_id");

        if ($p['owner_id'] > 0) {
            $msg = "Your property '{$p['name']}' has been $status by Super Admin.";
            if ($notes) $msg .= " Notes: $notes";
            create_notification($p['owner_id'], 'property_review', "Property Status: " . ucfirst($status), $msg, 'owner_dashboard.php');
        }

        log_audit_action($admin_id, "property_$status", "Property ID #$prop_id set to $status. Notes: $notes");
        echo json_encode(array('success' => true, 'message' => "Property status updated to '$status'. Owner notified."));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Property not found.'));
    }
    exit;
}

// 2. Agreement Verification (Approve / Seal / Reject)
if ($action === 'verify_agreement') {
    $booking_id = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
    $agr_id = isset($_POST['agreement_id']) ? (int)$_POST['agreement_id'] : 0;
    $status = mysqli_real_escape_string($conn, $_POST['status']); // 'approved', 'rejected', 'pending_review'
    $remark = isset($_POST['admin_remark']) ? mysqli_real_escape_string($conn, trim($_POST['admin_remark'])) : (isset($_POST['reason']) ? mysqli_real_escape_string($conn, trim($_POST['reason'])) : '');

    if ($agr_id > 0) {
        $a_res = mysqli_query($conn, "SELECT id, booking_id, owner_id, user_id FROM agreements WHERE id = $agr_id");
    } elseif ($booking_id > 0) {
        $a_res = mysqli_query($conn, "SELECT id, booking_id, owner_id, user_id FROM agreements WHERE booking_id = $booking_id");
    } else {
        echo json_encode(array('success' => false, 'message' => 'Missing booking or agreement ID.'));
        exit;
    }

    if ($a_res && mysqli_num_rows($a_res) > 0) {
        $a = mysqli_fetch_assoc($a_res);
        $target_agr_id = (int)$a['id'];
        $b_id = (int)$a['booking_id'];
    } elseif ($booking_id > 0) {
        // Auto create agreement record if missing
        $bk_res = mysqli_query($conn, "SELECT b.user_id, p.owner_id FROM bookings b INNER JOIN properties p ON b.property_id = p.id WHERE b.id = $booking_id");
        if (!$bk_res || mysqli_num_rows($bk_res) === 0) {
            echo json_encode(array('success' => false, 'message' => 'Booking record not found.'));
            exit;
        }
        $bk = mysqli_fetch_assoc($bk_res);
        $doc_url = "api/generate_agreement_pdf.php?booking_id=$booking_id";
        $owner_id = (int)$bk['owner_id'];
        $user_id = (int)$bk['user_id'];
        mysqli_query($conn, "INSERT INTO agreements (booking_id, owner_id, user_id, agreement_doc, status) VALUES ($booking_id, $owner_id, $user_id, '$doc_url', '$status')");
        $target_agr_id = mysqli_insert_id($conn);
        $a = array('id' => $target_agr_id, 'booking_id' => $booking_id, 'owner_id' => $owner_id, 'user_id' => $user_id);
        $b_id = $booking_id;
    } else {
        echo json_encode(array('success' => false, 'message' => 'Agreement record not found.'));
        exit;
    }

    $sealed_sql = ($status === 'approved') ? ", sealed_at = NOW()" : "";
    mysqli_query($conn, "UPDATE agreements SET status = '$status', admin_remark = '$remark', rejection_reason = '$remark' $sealed_sql WHERE id = $target_agr_id");

    if ($status === 'approved') {
        mysqli_query($conn, "UPDATE bookings SET status = 'accepted' WHERE id = $b_id");
        create_notification($a['user_id'], 'agreement_approved', '🛡️ Rental Agreement Approved & Sealed!', "Your rental agreement for Booking #$b_id has been officially approved & sealed by Super Admin. Remarks: $remark", 'profile.php');
        create_notification($a['owner_id'], 'agreement_approved', '🛡️ Rental Agreement Approved & Sealed!', "Rental agreement for Booking #$b_id was approved & sealed by Super Admin. Remarks: $remark", 'owner_dashboard.php');
    } else {
        mysqli_query($conn, "UPDATE bookings SET status = 'rejected' WHERE id = $b_id");
        create_notification($a['owner_id'], 'agreement_rejected', 'Rental Agreement Revision Required', "Agreement for Booking #$b_id requires revision. Super Admin Remarks: $remark", 'owner_dashboard.php');
        create_notification($a['user_id'], 'agreement_rejected', 'Rental Agreement Under Revision', "Rental agreement for Booking #$b_id requires revision. Super Admin Remarks: $remark", 'profile.php');
    }

    log_audit_action($admin_id, "agreement_$status", "Agreement ID #$target_agr_id (Booking #$b_id) set to $status. Remark: $remark");
    echo json_encode(array('success' => true, 'message' => "Rental agreement status updated to '$status' with Super Admin seal and remarks!"));
    exit;
}


// 3. Owner ID Verification (Verify / Reject)
if ($action === 'verify_owner_doc') {
    $target_user_id = (int)$_POST['user_id'];
    $status = mysqli_real_escape_string($conn, $_POST['status']); // 'verified', 'rejected'

    mysqli_query($conn, "UPDATE users SET id_verified = '$status' WHERE id = $target_user_id");
    create_notification($target_user_id, 'id_verification', 'Identity Verification Update', "Your identity document verification status is now: " . ucfirst($status), 'profile.php');

    log_audit_action($admin_id, "user_id_verification_$status", "User ID #$target_user_id identity set to $status.");
    echo json_encode(array('success' => true, 'message' => "User identity set to '$status'."));
    exit;
}

// 5. Account Deletion Request Moderation (Approve & Soft-Delete Archival / Reject)
if ($action === 'review_account_deletion') {
    $target_user_id = (int)$_POST['user_id'];
    $status = mysqli_real_escape_string($conn, $_POST['status']); // 'approved' or 'rejected'
    $remark = isset($_POST['remark']) ? mysqli_real_escape_string($conn, trim($_POST['remark'])) : '';

    $u_res = mysqli_query($conn, "SELECT full_name, email, role FROM users WHERE id = $target_user_id");
    if (!$u_res || mysqli_num_rows($u_res) === 0) {
        echo json_encode(array('success' => false, 'message' => 'User account not found.'));
        exit;
    }

    $u = mysqli_fetch_assoc($u_res);

    if ($status === 'approved') {
        // Soft-delete: Mark deletion_status = 'approved' and id_verified = 'deactivated_archived'. Data remains in DB for audit compliance.
        $sql = "UPDATE users SET deletion_status = 'approved', id_verified = 'deactivated_archived', deletion_admin_remark = '$remark' WHERE id = $target_user_id";
        mysqli_query($conn, $sql);

        create_notification(
            $target_user_id,
            'deletion_approved',
            'Account Deletion Request Approved',
            "Your account deletion request has been approved by Super Admin. Your account is deactivated and historical records preserved per compliance policy. Admin Remarks: $remark",
            'index.php'
        );

        log_audit_action($admin_id, 'account_deletion_approved', "User #$target_user_id ({$u['email']}) account deactivated & archived. Remark: $remark");
        echo json_encode(array('success' => true, 'message' => "Account deletion approved. User #$target_user_id deactivated and records archived for audit."));
    } else {
        $sql = "UPDATE users SET deletion_status = 'rejected', deletion_admin_remark = '$remark' WHERE id = $target_user_id";
        mysqli_query($conn, $sql);

        create_notification(
            $target_user_id,
            'deletion_rejected',
            'Account Deletion Request Rejected',
            "Your account deletion request was reviewed and rejected by Super Admin. Admin Remarks: $remark",
            'profile.php'
        );

        log_audit_action($admin_id, 'account_deletion_rejected', "User #$target_user_id ({$u['email']}) deletion request rejected. Remark: $remark");
        echo json_encode(array('success' => true, 'message' => "Account deletion request rejected. User notified."));
    }
    exit;
}

echo json_encode(array('success' => false, 'message' => 'Invalid admin action request.'));
