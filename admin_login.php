<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/database_connect.php';
require_once 'includes/notification_helper.php';

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CAPTCHA Validation
    $user_captcha = isset($_POST['captcha']) ? strtolower(trim($_POST['captcha'])) : '';
    $session_captcha = isset($_SESSION['captcha_code']) ? strtolower($_SESSION['captcha_code']) : '';

    if (empty($user_captcha) || $user_captcha !== $session_captcha) {
        $error_msg = "Security verification failed! Invalid CAPTCHA code. Please try again.";
    } else {
        $email = mysqli_real_escape_string($conn, trim($_POST['email']));
        $password = trim($_POST['password']);
        $pass_hash = sha1($password);

        $res = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email' AND (password = '$password' OR password = '$pass_hash') AND role = 'admin'");
        
        if ($res && mysqli_num_rows($res) > 0) {
            $admin = mysqli_fetch_assoc($res);

            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['full_name'] = $admin['full_name'];
            $_SESSION['email'] = $admin['email'];
            $_SESSION['role'] = 'admin';

            // Send Email & SMS Notification to Admin's Respective Email/Phone
            $login_time = date('d M Y, h:i A');
            create_notification(
                $admin['id'],
                'admin_login_alert',
                'Security Alert: Super Admin Login',
                "Hello {$admin['full_name']}, Super Admin portal login was authenticated successfully on $login_time.",
                'admin_portal.php'
            );

            header("Location: admin_portal.php");
            exit;
        } else {
            $error_msg = "Invalid Super Admin credentials or unauthorized access.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Secure Login | PGLife</title>
    <?php include 'includes/head_links.php'; ?>
    <style>
        body { background: linear-gradient(135deg, #0f172a, #1e293b); color: white; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .admin-login-card { background: #ffffff; color: #1e293b; width: 100%; max-width: 440px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); overflow: hidden; }
        .admin-login-header { background: #0f172a; color: white; padding: 24px; text-align: center; border-bottom: 3px solid #ef4444; }
    </style>
</head>
<body>
    <div class="admin-login-card">
        <div class="admin-login-header">
            <i class="fas fa-user-shield fa-3x text-danger mb-2"></i>
            <h4 class="font-weight-bold mb-1">Super Admin Portal</h4>
            <p class="text-muted small mb-0">Platform Administration Secure Access</p>
        </div>

        <div class="p-4">
            <?php if ($error_msg) { ?>
                <div class="alert alert-danger py-2" style="font-size: 13px;"><?php echo $error_msg; ?></div>
            <?php } ?>

            <form method="POST" action="admin_login.php">
                <div class="form-group">
                    <label class="font-weight-bold small">Admin Email Address</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-envelope"></i></span></div>
                        <input type="email" class="form-control" name="email" value="dwivedianandnarayan@gmail.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold small">Password</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-lock"></i></span></div>
                        <input type="password" class="form-control" name="password" value="Admin@2026!" required>
                    </div>
                </div>

                <!-- Security Verification CAPTCHA -->
                <div class="form-group">
                    <label class="font-weight-bold small">Security Verification (CAPTCHA) *</label>
                    <div class="d-flex align-items-center mb-2">
                        <img src="api/captcha.php" id="imgAdminCaptcha" style="border-radius: 8px; border: 1px solid #cbd5e1; height: 40px;" alt="CAPTCHA">
                        <button type="button" class="btn btn-sm btn-light ml-2 border" onclick="document.getElementById('imgAdminCaptcha').src='api/captcha.php?'+Math.random()" title="Refresh CAPTCHA">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                    <input type="text" class="form-control" name="captcha" placeholder="Enter CAPTCHA code shown above" autocomplete="off" required>
                </div>

                <button type="submit" class="btn btn-danger btn-block font-weight-bold mt-4 py-2">
                    <i class="fas fa-shield-alt"></i> Authenticate & Enter Admin Portal
                </button>
            </form>

            <div class="text-center mt-3">
                <a href="index.php" class="text-muted small"><i class="fas fa-arrow-left"></i> Return to PGLife Homepage</a>
            </div>
        </div>
    </div>
</body>
</html>
