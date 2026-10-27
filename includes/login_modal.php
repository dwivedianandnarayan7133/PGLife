<!-- PGLife Login Modal with OTP Step -->
<div class="modal fade" id="login-modal" tabindex="-1" role="dialog"
     aria-labelledby="login-heading" aria-hidden="true"
     data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold" id="login-heading">
                    <i class="fas fa-user-circle text-primary"></i> Login with PGLife
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body p-4">
                <form id="login-form" onsubmit="handleLoginSubmit(event)" autocomplete="off">
                    <div class="form-group">
                        <label class="font-weight-bold small">Email Address or Mobile Phone Number</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-user"></i></span></div>
                            <input type="text" class="form-control" name="email" id="loginEmail" placeholder="Email or Mobile Number" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold small">Password</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-lock"></i></span></div>
                            <input type="password" class="form-control" name="password" placeholder="Password" minlength="6" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold small">Security Verification (CAPTCHA) *</label>
                        <div class="d-flex align-items-center mb-2">
                            <img src="api/captcha.php" id="imgLoginCaptcha" style="border-radius:8px;border:1px solid #cbd5e1;height:40px;" alt="CAPTCHA">
                            <button type="button" class="btn btn-sm btn-light ml-2 border"
                                onclick="document.getElementById('imgLoginCaptcha').src='api/captcha.php?'+Math.random()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <input type="text" class="form-control" name="captcha" id="loginCaptchaInput"
                            placeholder="Enter CAPTCHA code shown above" autocomplete="off" required>
                    </div>
                    <div class="form-group mt-3">
                        <button type="submit" id="loginSubmitBtn" class="btn btn-block btn-primary font-weight-bold py-2">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                    </div>
                </form>
            </div>

            <div class="modal-footer bg-light justify-content-center">
                <span class="small text-muted">
                    Don't have an account?
                    <a href="#" class="font-weight-bold text-primary ml-1"
                        data-dismiss="modal" data-toggle="modal" data-target="#signup-modal">Register a new account</a>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     OTP OVERLAY — Completely separate from Bootstrap modal system.
     Cannot be closed by Bootstrap backdrop, Escape key, or any modal event.
     ═══════════════════════════════════════════════════════════════════════════ -->
<div id="pglife-otp-overlay" style="
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(15,23,42,0.82);
    z-index: 99999;
    align-items: center;
    justify-content: center;
">
    <div style="
        background: #fff;
        border-radius: 20px;
        padding: 36px 32px;
        max-width: 420px;
        width: 92%;
        box-shadow: 0 25px 60px rgba(0,0,0,0.4);
        text-align: center;
        position: relative;
    ">
        <div style="font-size:52px;line-height:1;margin-bottom:12px;">📧</div>
        <h4 style="font-weight:800;color:#0f172a;margin-bottom:6px;">OTP Sent!</h4>
        <p style="color:#64748b;font-size:14px;margin-bottom:4px;">A 6-digit code was sent to:</p>
        <p id="otpOverlayEmail" style="color:#0284c7;font-weight:700;font-size:15px;margin-bottom:4px;"></p>
        <span id="otpOverlayTimer" style="
            display:inline-block;
            background:#fef3c7;
            color:#92400e;
            padding:4px 14px;
            border-radius:20px;
            font-size:13px;
            font-weight:600;
            margin-bottom:20px;
        ">⏱ Expires in 10:00</span>

        <form id="otp-verify-form" onsubmit="submitOtpOverlay(event)" autocomplete="off">
            <input type="text"
                id="otpOverlayInput"
                name="otp_code"
                placeholder="_ _ _ _ _ _"
                maxlength="6" minlength="6"
                inputmode="numeric"
                autocomplete="one-time-code"
                style="
                    width:100%;
                    font-size:30px;
                    letter-spacing:10px;
                    text-align:center;
                    font-weight:800;
                    border:2px solid #0284c7;
                    border-radius:12px;
                    padding:12px 8px;
                    margin-bottom:16px;
                    outline:none;
                    color:#0f172a;
                "
                required>

            <p style="font-size:12px;color:#94a3b8;margin-bottom:20px;">
                Check your inbox and spam/junk folder
            </p>

            <button type="submit" style="
                width:100%;
                background:linear-gradient(135deg,#16a34a,#15803d);
                color:#fff;
                border:none;
                border-radius:12px;
                padding:13px;
                font-size:16px;
                font-weight:700;
                cursor:pointer;
                margin-bottom:12px;
            ">
                ✅ Verify OTP &amp; Login
            </button>
        </form>

        <div style="display:flex;gap:10px;justify-content:center;margin-top:4px;">
            <button onclick="resendOtpOverlay()" style="
                background:none;border:1px solid #cbd5e1;
                border-radius:8px;padding:7px 16px;
                font-size:13px;cursor:pointer;color:#475569;
            ">↻ Resend OTP</button>
            <button onclick="closeOtpOverlay()" style="
                background:none;border:1px solid #cbd5e1;
                border-radius:8px;padding:7px 16px;
                font-size:13px;cursor:pointer;color:#475569;
            ">← Back</button>
        </div>

        <p id="otpOverlayMsg" style="
            margin-top:14px;font-size:13px;
            color:#dc2626;font-weight:600;
            min-height:18px;
        "></p>
    </div>
</div>

<script>
var _otpCountdownInterval = null;

/* ── Open OTP Overlay ──────────────────────────────────────────────────────── */
function openOtpOverlay(maskedEmail) {
    // Hide Bootstrap modal first (without triggering its close events)
    $('#login-modal').modal('hide');

    var overlay = document.getElementById('pglife-otp-overlay');
    overlay.style.display = 'flex';
    document.getElementById('otpOverlayEmail').textContent = maskedEmail || '';
    document.getElementById('otpOverlayInput').value = '';
    document.getElementById('otpOverlayMsg').textContent = '';

    // Start countdown
    startOtpCountdown(600);

    setTimeout(function() {
        var inp = document.getElementById('otpOverlayInput');
        if (inp) inp.focus();
    }, 100);
}

/* ── Close OTP Overlay ─────────────────────────────────────────────────────── */
function closeOtpOverlay() {
    clearInterval(_otpCountdownInterval);
    document.getElementById('pglife-otp-overlay').style.display = 'none';
    document.getElementById('otpOverlayInput').value = '';
    document.getElementById('otpOverlayMsg').textContent = '';
    // Refresh captcha
    var cap = document.getElementById('imgLoginCaptcha');
    if (cap) cap.src = 'api/captcha.php?' + Math.random();
    var capInput = document.getElementById('loginCaptchaInput');
    if (capInput) capInput.value = '';
    document.getElementById('loginSubmitBtn').disabled = false;
}

/* ── Countdown Timer ───────────────────────────────────────────────────────── */
function startOtpCountdown(seconds) {
    clearInterval(_otpCountdownInterval);
    var remaining = seconds;
    var el = document.getElementById('otpOverlayTimer');
    function tick() {
        var m = Math.floor(remaining / 60);
        var s = remaining % 60;
        if (el) el.textContent = '⏱ Expires in ' + m + ':' + (s < 10 ? '0' : '') + s;
        if (remaining <= 0) {
            clearInterval(_otpCountdownInterval);
            if (el) { el.textContent = '❌ OTP Expired'; el.style.background = '#fee2e2'; el.style.color = '#991b1b'; }
        }
        remaining--;
    }
    tick();
    _otpCountdownInterval = setInterval(tick, 1000);
}

/* ── Handle Login Form Submit ──────────────────────────────────────────────── */
function handleLoginSubmit(e) {
    if (e) e.preventDefault();
    var form = document.getElementById('login-form');
    if (!form) return false;

    var btn = document.getElementById('loginSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending OTP...';

    fetch('api/login_submit.php', { method: 'POST', body: new FormData(form) })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Login';
        if (d.success && d.otp_required) {
            openOtpOverlay(d.masked_email);
        } else if (d.success) {
            window.location.href = d.redirect || 'index.php';
        } else {
            btn.disabled = false;
            alert(d.message);
            var cap = document.getElementById('imgLoginCaptcha');
            if (cap) cap.src = 'api/captcha.php?' + Math.random();
            document.getElementById('loginCaptchaInput').value = '';
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Login';
        alert('Network error. Please try again.');
    });
    return false;
}

/* ── Handle OTP Overlay Submit ─────────────────────────────────────────────── */
function submitOtpOverlay(e) {
    if (e) e.preventDefault();
    var otp = document.getElementById('otpOverlayInput').value.trim();
    var msgEl = document.getElementById('otpOverlayMsg');

    if (!otp || otp.length !== 6 || !/^\d{6}$/.test(otp)) {
        msgEl.textContent = '⚠ Please enter the 6-digit numeric OTP.';
        return false;
    }

    msgEl.textContent = 'Verifying...';
    msgEl.style.color = '#0284c7';

    var fd = new FormData();
    fd.append('otp_code', otp);

    fetch('api/login_otp_verify.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            clearInterval(_otpCountdownInterval);
            msgEl.style.color = '#16a34a';
            msgEl.textContent = '✅ Verified! Redirecting...';
            setTimeout(function() {
                window.location.href = d.redirect || 'index.php';
            }, 600);
        } else {
            msgEl.style.color = '#dc2626';
            msgEl.textContent = '❌ ' + (d.message || 'Wrong OTP. Try again.');
            document.getElementById('otpOverlayInput').value = '';
            document.getElementById('otpOverlayInput').focus();
        }
    })
    .catch(function() {
        msgEl.style.color = '#dc2626';
        msgEl.textContent = '❌ Network error. Please try again.';
    });
    return false;
}

/* ── Resend OTP ────────────────────────────────────────────────────────────── */
function resendOtpOverlay() {
    var msgEl = document.getElementById('otpOverlayMsg');
    msgEl.style.color = '#0284c7';
    msgEl.textContent = 'Sending new OTP...';

    var form = document.getElementById('login-form');
    fetch('api/login_submit.php', { method: 'POST', body: new FormData(form) })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success && d.otp_required) {
            msgEl.style.color = '#16a34a';
            msgEl.textContent = '✅ New OTP sent! Check your email.';
            document.getElementById('otpOverlayInput').value = '';
            document.getElementById('otpOverlayInput').focus();
            startOtpCountdown(600);
        } else {
            msgEl.style.color = '#dc2626';
            msgEl.textContent = '❌ ' + (d.message || 'Could not resend OTP.');
        }
    });
}

/* ── Reset login modal when closed ────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function() {
    var loginModal = document.getElementById('login-modal');
    if (loginModal) {
        loginModal.addEventListener('hidden.bs.modal', function() {
            var btn = document.getElementById('loginSubmitBtn');
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Login'; }
            var cap = document.getElementById('imgLoginCaptcha');
            if (cap) cap.src = 'api/captcha.php?' + Math.random();
            var capInput = document.getElementById('loginCaptchaInput');
            if (capInput) capInput.value = '';
        });
    }
});
</script>
