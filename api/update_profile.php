<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/database_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(array('success' => false, 'message' => 'Unauthorized: Please log in.'));
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action = isset($_POST['action']) ? $_POST['action'] : 'update_info';

if ($action === 'update_info') {
    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $college = mysqli_real_escape_string($conn, trim($_POST['college_name']));

    // Handle Profile Picture Upload
    $profile_img_sql = "";
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../img/profiles/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
        $target = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target)) {
            $web_path = 'img/profiles/' . $filename;
            $profile_img_sql = ", profile_image = '$web_path'";
        }
    }

    $sql = "UPDATE users SET full_name = '$full_name', phone = '$phone', email = '$email', address = '$address', college_name = '$college' $profile_img_sql WHERE id = $user_id";
    if (mysqli_query($conn, $sql)) {
        $_SESSION['user_name'] = $full_name;
        echo json_encode(array('success' => true, 'message' => 'Profile updated successfully!'));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Database error: ' . mysqli_error($conn)));
    }
    exit;
}

if ($action === 'change_password') {
    $old_pass = trim($_POST['old_password']);
    $new_pass = trim($_POST['new_password']);
    
    if (empty($old_pass) || empty($new_pass)) {
        echo json_encode(array('success' => false, 'message' => 'Both current and new password are required.'));
        exit;
    }

    $old_hash = sha1($old_pass);
    $res = mysqli_query($conn, "SELECT id FROM users WHERE id = $user_id AND (password = '$old_pass' OR password = '$old_hash')");
    if (mysqli_num_rows($res) === 0) {
        echo json_encode(array('success' => false, 'message' => 'Incorrect current password.'));
        exit;
    }

    $new_hash = sha1($new_pass);
    $sql = "UPDATE users SET password = '$new_hash' WHERE id = $user_id";
    if (mysqli_query($conn, $sql)) {
        echo json_encode(array('success' => true, 'message' => 'Password updated successfully!'));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to update password.'));
    }
    exit;
}

if ($action === 'upload_id_proof') {
    if (!isset($_FILES['id_proof']) || $_FILES['id_proof']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(array('success' => false, 'message' => 'Please select a valid document image or PDF.'));
        exit;
    }

    $upload_dir = __DIR__ . '/../img/docs/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $ext = pathinfo($_FILES['id_proof']['name'], PATHINFO_EXTENSION);
    $filename = 'id_user_' . $user_id . '_' . time() . '.' . $ext;
    $target = $upload_dir . $filename;

    if (move_uploaded_file($_FILES['id_proof']['tmp_name'], $target)) {
        $web_path = 'img/docs/' . $filename;
        $sql = "UPDATE users SET id_proof_doc = '$web_path', id_verified = 'pending' WHERE id = $user_id";
        mysqli_query($conn, $sql);
        echo json_encode(array('success' => true, 'message' => 'ID Proof uploaded! Verification status is now Pending Review.'));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to save document.'));
    }
    exit;
}

if ($action === 'update_notifications') {
    $prefs = isset($_POST['prefs']) ? mysqli_real_escape_string($conn, $_POST['prefs']) : '{"email":true,"push":true,"sms":false}';
    $sql = "UPDATE users SET notification_prefs = '$prefs' WHERE id = $user_id";
    if (mysqli_query($conn, $sql)) {
        echo json_encode(array('success' => true, 'message' => 'Notification preferences saved!'));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Database update error.'));
    }
    exit;
}

echo json_encode(array('success' => false, 'message' => 'Invalid action.'));
