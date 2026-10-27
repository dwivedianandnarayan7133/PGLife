<?php
session_start();
require_once 'includes/database_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Owner Portal | PGLife</title>
    <?php include 'includes/head_links.php'; ?>
    <style>
        .owner-hero { background: linear-gradient(135deg, #0f172a, #1e293b); color: white; padding: 60px 0; border-bottom: 4px solid #0284c7; }
        .auth-card { background: white; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1); }
    </style>
</head>
<body style="background-color: #f8fafc;">
    <?php include 'includes/header.php'; ?>

    <div class="owner-hero text-center">
        <div class="container">
            <h2 class="font-weight-bold text-white mb-2"><i class="fas fa-building text-warning"></i> Partner with PGLife</h2>
            <p class="text-muted" style="font-size: 16px;">List your hostels and PG accommodations, manage bookings, and connect with thousands of student residents.</p>
        </div>
    </div>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="auth-card p-4 p-md-5">
                    <ul class="nav nav-pills nav-justified mb-4" id="ownerAuthTabs">
                        <li class="nav-item">
                            <a class="nav-link active font-weight-bold" data-toggle="pill" href="#tab-owner-register"><i class="fas fa-user-plus"></i> Become a Property Owner</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold" data-toggle="pill" href="#tab-owner-login"><i class="fas fa-sign-in-alt"></i> Existing Owner Login</a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- Owner Registration -->
                        <div class="tab-pane fade show active" id="tab-owner-register">
                            <form id="formOwnerRegisterPage" enctype="multipart/form-data">
                                <h5 class="font-weight-bold text-dark mb-3">Owner Registration Form</h5>
                                <p class="text-muted small">Fill out your details & upload government ID proof. Accounts are verified by Super Admin before activation.</p>
                                
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label class="font-weight-bold small">Full Name *</label>
                                        <input type="text" class="form-control" name="full_name" placeholder="e.g. Rajesh Sharma" required>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label class="font-weight-bold small">Mobile Number *</label>
                                        <input type="text" class="form-control" name="phone" placeholder="10-digit mobile number" required>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label class="font-weight-bold small">Email Address *</label>
                                        <input type="email" class="form-control" name="email" placeholder="owner@domain.com" required>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label class="font-weight-bold small">Password *</label>
                                        <input type="password" class="form-control" name="password" required>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label class="font-weight-bold small">Property Name *</label>
                                        <input type="text" class="form-control" name="property_name" placeholder="e.g. Sharma PG" required>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label class="font-weight-bold small">City *</label>
                                        <select class="form-control" name="city_id" required>
                                            <option value="1">Delhi</option>
                                            <option value="2">Mumbai</option>
                                            <option value="3">Bengaluru</option>
                                            <option value="4">Hyderabad</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="font-weight-bold small">Property Address *</label>
                                    <input type="text" class="form-control" name="property_address" required>
                                </div>
                                <div class="form-group">
                                    <label class="font-weight-bold small">Government ID Proof (Aadhaar / Passport) *</label>
                                    <input type="file" class="form-control-file" name="govt_id_doc" required>
                                </div>
                                <div class="form-group">
                                    <label class="font-weight-bold small">Security Verification (CAPTCHA) *</label>
                                    <div class="d-flex align-items-center mb-2">
                                        <img src="api/captcha.php" id="imgOwnerPageRegCaptcha" style="border-radius: 8px; border: 1px solid #cbd5e1; height: 40px;" alt="CAPTCHA">
                                        <button type="button" class="btn btn-sm btn-light ml-2 border" onclick="document.getElementById('imgOwnerPageRegCaptcha').src='api/captcha.php?'+Math.random()" title="Refresh CAPTCHA">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </div>
                                    <input type="text" class="form-control" name="captcha" placeholder="Enter CAPTCHA code shown above" autocomplete="off" required>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block font-weight-bold"><i class="fas fa-paper-plane"></i> Submit Registration</button>
                            </form>
                        </div>

                        <!-- Owner Login -->
                        <div class="tab-pane fade" id="tab-owner-login">
                            <form id="formOwnerLoginPage">
                                <h5 class="font-weight-bold text-dark mb-3">Owner Login</h5>
                                <div class="form-group">
                                    <label class="font-weight-bold small">Email Address</label>
                                    <input type="email" class="form-control" name="email" placeholder="owner@pglife.com" required>
                                </div>
                                <div class="form-group">
                                    <label class="font-weight-bold small">Password</label>
                                    <input type="password" class="form-control" name="password" required>
                                </div>
                                <div class="form-group">
                                    <label class="font-weight-bold small">Security Verification (CAPTCHA) *</label>
                                    <div class="d-flex align-items-center mb-2">
                                        <img src="api/captcha.php" id="imgOwnerPageLoginCaptcha" style="border-radius: 8px; border: 1px solid #cbd5e1; height: 40px;" alt="CAPTCHA">
                                        <button type="button" class="btn btn-sm btn-light ml-2 border" onclick="document.getElementById('imgOwnerPageLoginCaptcha').src='api/captcha.php?'+Math.random()" title="Refresh CAPTCHA">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </div>
                                    <input type="text" class="form-control" name="captcha" placeholder="Enter CAPTCHA code shown above" autocomplete="off" required>
                                </div>
                                <button type="submit" class="btn btn-warning btn-block font-weight-bold text-dark"><i class="fas fa-sign-in-alt"></i> Login to Owner Portal</button>
                            </form>

                            <!-- OTP Verification Section (shown after credentials accepted) -->
                            <div id="ownerLoginOtpSection" style="display:none;" class="mt-4 p-3 bg-light rounded border">
                                <div class="text-center mb-3">
                                    <span style="font-size:36px;">📧</span>
                                    <h6 class="font-weight-bold mt-2">Enter OTP from Email</h6>
                                    <small class="text-muted">Sent to: <strong id="ownerOtpMaskedEmail"></strong></small>
                                </div>
                                <form id="formOwnerLoginOtp">
                                    <div class="form-group">
                                        <input type="text" class="form-control text-center font-weight-bold" id="ownerLoginOtpInput" name="otp_code"
                                            placeholder="• • • • • •" maxlength="6" minlength="6" pattern="[0-9]{6}"
                                            autocomplete="one-time-code" style="font-size:22px; letter-spacing:6px;" required>
                                    </div>
                                    <button type="submit" class="btn btn-success btn-block font-weight-bold">
                                        <i class="fas fa-check-circle"></i> Verify OTP & Login
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
    document.getElementById('formOwnerRegisterPage')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;

        showTermsModal(function() {
            const formData = new FormData(form);
            formData.append('tc_accepted', '1');

            fetch('api/owner_register.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(d => {
                    alert(d.message);
                    if (d.success) {
                        form.reset();
                    } else {
                        const captchaImg = document.getElementById('imgOwnerPageRegCaptcha');
                        if (captchaImg) captchaImg.src = 'api/captcha.php?' + Math.random();
                    }
                });
        });
    });

    document.getElementById('formOwnerLoginPage')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        fetch('api/login_submit.php', { method: 'POST', body: new FormData(form) })
            .then(r => r.json())
            .then(d => {
                if (d.success && d.otp_required) {
                    // Show inline OTP section
                    const otpSection = document.getElementById('ownerLoginOtpSection');
                    const maskedEl   = document.getElementById('ownerOtpMaskedEmail');
                    if (otpSection) otpSection.style.display = 'block';
                    if (maskedEl)   maskedEl.textContent = d.masked_email || '';
                    const otpInput = document.getElementById('ownerLoginOtpInput');
                    if (otpInput) { otpInput.value = ''; otpInput.focus(); }
                } else if (d.success) {
                    window.location.href = d.redirect || 'owner_dashboard.php';
                } else {
                    alert(d.message);
                    const captchaImg = document.getElementById('imgOwnerPageLoginCaptcha');
                    if (captchaImg) captchaImg.src = 'api/captcha.php?' + Math.random();
                }
            });
    });

    document.getElementById('formOwnerLoginOtp')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const otp = document.getElementById('ownerLoginOtpInput').value.trim();
        if (!otp || otp.length !== 6) { alert('Please enter the 6-digit OTP from your email.'); return; }
        const fd = new FormData(); fd.append('otp_code', otp);
        fetch('api/login_otp_verify.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    window.location.href = d.redirect || 'owner_dashboard.php';
                } else {
                    alert(d.message);
                    document.getElementById('ownerLoginOtpInput').value = '';
                }
            });
    });
    </script>
</body>
</html>
