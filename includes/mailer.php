<?php
require_once __DIR__ . '/database_connect.php';

// ─── Gmail SMTP Configuration ─────────────────────────────────────────────────
define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_USER',     'dwivedianandnarayan@gmail.com');
define('SMTP_PASS',     'iesxmcucbuvveenh');
define('SMTP_FROM',     'dwivedianandnarayan@gmail.com');
define('SMTP_FROM_NAME','PGLife Portal');

/**
 * Send HTML email via direct SMTP socket to Gmail.
 * Bypasses php.ini / sendmail entirely — works on any XAMPP installation.
 *
 * @param string $to_email    Recipient email address
 * @param string $subject     Email subject line
 * @param string $html_body   Full HTML body string
 * @param int    $user_id     (Optional) User ID for DB logging
 * @return bool               True on success, false on failure
 */
function smtp_send($to_email, $subject, $html_body, $user_id = 0) {
    global $conn;

    $to_email = trim($to_email);
    if (empty($to_email) || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    // ── MIME message assembly ──────────────────────────────────────────────────
    $boundary  = md5(uniqid(time(), true));
    $date      = date('r');

    $raw  = "Date: {$date}\r\n";
    $raw .= "To: <{$to_email}>\r\n";
    $raw .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    $raw .= "Reply-To: " . SMTP_FROM . "\r\n";
    $raw .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $raw .= "MIME-Version: 1.0\r\n";
    $raw .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
    $raw .= "\r\n";
    $raw .= "--{$boundary}\r\n";
    $raw .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $raw .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $raw .= chunk_split(base64_encode(strip_tags(str_replace(['<br>','<br/>','<br />'], "\n", $html_body))));
    $raw .= "\r\n--{$boundary}\r\n";
    $raw .= "Content-Type: text/html; charset=UTF-8\r\n";
    $raw .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $raw .= chunk_split(base64_encode($html_body));
    $raw .= "\r\n--{$boundary}--\r\n";

    // ── Open SMTP socket ───────────────────────────────────────────────────────
    $errno = 0; $errstr = '';
    $socket = @fsockopen('ssl://smtp.gmail.com', 465, $errno, $errstr, 10);

    // Try TLS/STARTTLS on 587 if SSL 465 fails
    if (!$socket) {
        $socket = @fsockopen('tcp://smtp.gmail.com', 587, $errno, $errstr, 10);
        if (!$socket) {
            _smtp_log_db($conn, $user_id, $to_email, $subject, "FAILED: $errstr");
            return false;
        }
        $use_starttls = true;
    } else {
        $use_starttls = false;
    }

    stream_set_timeout($socket, 10);

    // ── SMTP handshake ─────────────────────────────────────────────────────────
    $resp = fgets($socket, 515);
    if (substr($resp, 0, 3) !== '220') { fclose($socket); _smtp_log_db($conn, $user_id, $to_email, $subject, "FAILED: bad greeting"); return false; }

    fputs($socket, "EHLO localhost\r\n");
    $ehlo = '';
    while ($line = fgets($socket, 515)) {
        $ehlo .= $line;
        if (substr($line, 3, 1) === ' ') break;
    }

    if ($use_starttls) {
        fputs($socket, "STARTTLS\r\n");
        $resp = fgets($socket, 515);
        if (substr($resp, 0, 3) !== '220') { fclose($socket); return false; }
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        fputs($socket, "EHLO localhost\r\n");
        while ($line = fgets($socket, 515)) {
            if (substr($line, 3, 1) === ' ') break;
        }
    }

    // AUTH LOGIN
    fputs($socket, "AUTH LOGIN\r\n");
    $resp = fgets($socket, 515);
    if (substr($resp, 0, 3) !== '334') { fclose($socket); return false; }

    fputs($socket, base64_encode(SMTP_USER) . "\r\n");
    $resp = fgets($socket, 515);
    if (substr($resp, 0, 3) !== '334') { fclose($socket); return false; }

    fputs($socket, base64_encode(SMTP_PASS) . "\r\n");
    $resp = fgets($socket, 515);
    if (substr($resp, 0, 3) !== '235') { fclose($socket); _smtp_log_db($conn, $user_id, $to_email, $subject, "FAILED: Auth failed"); return false; }

    // MAIL FROM / RCPT TO / DATA
    fputs($socket, "MAIL FROM: <" . SMTP_FROM . ">\r\n");
    fgets($socket, 515);

    fputs($socket, "RCPT TO: <{$to_email}>\r\n");
    $resp = fgets($socket, 515);
    if (substr($resp, 0, 3) !== '250') { fclose($socket); _smtp_log_db($conn, $user_id, $to_email, $subject, "FAILED: RCPT rejected"); return false; }

    fputs($socket, "DATA\r\n");
    $resp = fgets($socket, 515);
    if (substr($resp, 0, 3) !== '354') { fclose($socket); return false; }

    fputs($socket, $raw . "\r\n.\r\n");
    $resp = fgets($socket, 515);

    fputs($socket, "QUIT\r\n");
    fclose($socket);

    $success = (substr($resp, 0, 3) === '250');
    _smtp_log_db($conn, $user_id, $to_email, $subject, $success ? 'Delivered' : 'Failed: ' . trim($resp));

    // Archive HTML email file to disk
    $dir = __DIR__ . '/../outbound_emails';
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    $safe = preg_replace('/[^a-zA-Z0-9_@.-]/', '_', $to_email);
    @file_put_contents($dir . '/email_' . time() . '_' . rand(100, 999) . '_' . $safe . '.html', $html_body);

    return $success;
}

/** Log outbound email to DB */
function _smtp_log_db($conn, $user_id, $to_email, $subject, $status) {
    if (!$conn) return;
    $uid   = (int)$user_id;
    $em    = mysqli_real_escape_string($conn, $to_email);
    $subj  = mysqli_real_escape_string($conn, $subject);
    $stat  = mysqli_real_escape_string($conn, $status);
    mysqli_query($conn, "INSERT INTO outbound_notifications (user_id, recipient_email, channel, title, message, status)
                         VALUES ($uid, '$em', 'Email', '$subj', '$subj', '$stat')");
}

/**
 * Build & send a styled PGLife HTML email.
 *
 * @param string $to_email
 * @param string $subject
 * @param string $body_content  Can include HTML or plain text (will be nl2br'd)
 * @param string $recipient_name
 * @param int    $user_id
 */
function send_email($to_email, $subject, $body_content, $recipient_name = 'User', $user_id = 0) {
    $recipient_name = !empty($recipient_name) ? htmlspecialchars($recipient_name) : 'User';

    // Detect if body already contains HTML tags; otherwise convert
    $body_html = (strip_tags($body_content) === $body_content)
        ? nl2br(htmlspecialchars($body_content))
        : $body_content;

    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>' . htmlspecialchars($subject) . '</title>
        <style>
            body { font-family: "Segoe UI",Arial,sans-serif; background:#f1f5f9; margin:0; padding:20px; color:#1e293b; }
            .email-container { max-width:600px; margin:0 auto; background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 10px 25px rgba(0,0,0,.08); border:1px solid #e2e8f0; }
            .email-header { background:linear-gradient(135deg,#0f172a,#1e293b); padding:26px 28px; text-align:center; }
            .email-header h2 { margin:0; font-size:22px; font-weight:700; color:#38bdf8; }
            .email-header p  { margin:4px 0 0; font-size:12px; color:#94a3b8; }
            .email-body { padding:32px 28px; line-height:1.7; }
            .email-body h4 { color:#0f172a; margin-top:0; font-size:18px; }
            .otp-box { background:#f0f9ff; border:2px dashed #0284c7; border-radius:12px; padding:20px; text-align:center; margin:22px 0; }
            .otp-code { font-size:34px; font-weight:900; letter-spacing:8px; color:#0284c7; font-family:monospace; }
            .otp-note { font-size:12px; color:#64748b; margin-top:8px; }
            .email-footer { background:#f8fafc; padding:16px 28px; text-align:center; border-top:1px solid #e2e8f0; font-size:12px; color:#64748b; }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                <h2>🏠 PGLife Portal</h2>
                <p>PG Accommodation &amp; Hostel Management Platform</p>
            </div>
            <div class="email-body">
                <h4>Hello ' . $recipient_name . ',</h4>
                <div>' . $body_html . '</div>
            </div>
            <div class="email-footer">
                <p>This is an automated message from PGLife. Do not reply to this email.<br>
                &copy; ' . date('Y') . ' PGLife. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>';

    return smtp_send($to_email, $subject, $html, $user_id);
}

/**
 * Send an OTP email with large styled passcode display.
 */
function send_otp_email($to_email, $otp_code, $recipient_name = 'User', $user_id = 0, $purpose = 'Verification') {
    $subject = "🔐 Your PGLife OTP: {$otp_code}";
    $body = '
        <p>You requested a one-time password (OTP) for <strong>' . htmlspecialchars($purpose) . '</strong> on PGLife.</p>
        <div class="otp-box">
            <div class="otp-code">' . htmlspecialchars($otp_code) . '</div>
            <div class="otp-note">⏱ This OTP is valid for <strong>10 minutes</strong>. Do not share it with anyone.</div>
        </div>
        <p>If you did not request this OTP, please ignore this email and your account will remain secure.</p>
    ';
    return send_email($to_email, $subject, $body, $recipient_name, $user_id);
}
