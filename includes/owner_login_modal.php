<!-- Property Owner Login Modal -->
<div class="modal fade" id="modalOwnerLogin" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 420px;">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <div>
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-store text-warning"></i> Property Owner Login</h5>
                    <p class="text-muted mb-0 small" style="font-size: 11px;">Access your owner dashboard to manage listings & bookings.</p>
                </div>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="formOwnerLogin">
                <div class="modal-body p-4">
                    <div class="form-group">
                        <label class="font-weight-bold small">Email Address or Mobile Phone Number</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-user"></i></span></div>
                            <input type="text" class="form-control" name="email" placeholder="Email or 10-digit Mobile Number" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold small">Password</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-lock"></i></span></div>
                            <input type="password" class="form-control" name="password" placeholder="••••••••" required>
                        </div>
                    </div>
                    <!-- Security Verification CAPTCHA -->
                    <div class="form-group">
                        <label class="font-weight-bold small">Security Verification (CAPTCHA) *</label>
                        <div class="d-flex align-items-center mb-2">
                            <img src="api/captcha.php" id="imgOwnerLoginCaptcha" style="border-radius: 8px; border: 1px solid #cbd5e1; height: 40px;" alt="CAPTCHA">
                            <button type="button" class="btn btn-sm btn-light ml-2 border" onclick="document.getElementById('imgOwnerLoginCaptcha').src='api/captcha.php?'+Math.random()" title="Refresh CAPTCHA">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <input type="text" class="form-control" name="captcha" placeholder="Enter CAPTCHA code shown above" autocomplete="off" required>
                    </div>
                    <button type="submit" class="btn btn-warning btn-block font-weight-bold text-dark mt-4 py-2"><i class="fas fa-sign-in-alt"></i> Login to Owner Portal</button>
                </div>
                <div class="modal-footer bg-light justify-content-center">
                    <span class="small text-muted">Don't have an owner account yet?</span>
                    <a href="#" class="text-primary font-weight-bold small ml-1" onclick="$('#modalOwnerLogin').modal('hide'); $('#modalOwnerRegister').modal('show');">Become a Property Owner</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('formOwnerLogin')?.addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = this.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending OTP...';
    }

    fetch('api/login_submit.php', { method: 'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(d => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Login to Owner Portal';
            }

            if (d.success && d.otp_required) {
                $('#modalOwnerLogin').modal('hide');
                if (typeof openOtpOverlay === 'function') {
                    openOtpOverlay(d.masked_email);
                } else {
                    alert('OTP required. Redirecting to owner auth portal.');
                    window.location.href = 'owner_auth.php';
                }
            } else if (d.success) {
                window.location.href = d.redirect || 'owner_dashboard.php';
            } else {
                alert(d.message);
                const captchaImg = document.getElementById('imgOwnerLoginCaptcha');
                if (captchaImg) captchaImg.src = 'api/captcha.php?' + Math.random();
            }
        })
        .catch(() => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Login to Owner Portal';
            }
            alert('Network error. Please try again.');
        });
});
</script>
