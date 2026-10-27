<div class="modal fade" id="signup-modal" tabindex="-1" role="dialog" aria-labelledby="signup-heading" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold" id="signup-heading"><i class="fas fa-user-plus text-primary"></i> Signup with PGLife</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body p-4">
                <form id="signup-form" class="form" onsubmit="handleSignupSubmit(event)">
                    <div class="form-group">
                        <label class="font-weight-bold small">Full Name *</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-user"></i></span></div>
                            <input type="text" class="form-control" name="full_name" placeholder="Full Name" maxlength="30" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">10-Digit Mobile Phone Number *</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-phone-alt"></i></span></div>
                            <input type="text" class="form-control" name="phone" placeholder="10-Digit Mobile Number" maxlength="10" minlength="10" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">Email Address *</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-envelope"></i></span></div>
                            <input type="email" class="form-control" name="email" placeholder="student@example.com" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">Password *</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-lock"></i></span></div>
                            <input type="password" class="form-control" name="password" placeholder="Password (Min 6 chars)" minlength="6" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">College / Institution Name *</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-university"></i></span></div>
                            <input type="text" class="form-control" name="college_name" placeholder="e.g. Delhi University" maxlength="150" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small mr-2">Gender:</label>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="gender-male" name="gender" value="male" class="custom-control-input" checked>
                            <label class="custom-control-label" for="gender-male">Male</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="gender-female" name="gender" value="female" class="custom-control-input">
                            <label class="custom-control-label" for="gender-female">Female</label>
                        </div>
                    </div>

                    <!-- Security Verification CAPTCHA -->
                    <div class="form-group">
                        <label class="font-weight-bold small">Security Verification (CAPTCHA) *</label>
                        <div class="d-flex align-items-center mb-2">
                            <img src="api/captcha.php" id="imgSignupCaptcha" style="border-radius: 8px; border: 1px solid #cbd5e1; height: 40px;" alt="CAPTCHA">
                            <button type="button" class="btn btn-sm btn-light ml-2 border" onclick="document.getElementById('imgSignupCaptcha').src='api/captcha.php?'+Math.random()" title="Refresh CAPTCHA">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <input type="text" class="form-control" name="captcha" placeholder="Enter CAPTCHA code shown above" autocomplete="off" required>
                    </div>

                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-block btn-primary font-weight-bold py-2"><i class="fas fa-user-check"></i> Register Account</button>
                    </div>
                </form>
            </div>

            <div class="modal-footer bg-light justify-content-center">
                <span class="small text-muted">
                    Already have an account?
                    <a href="#" class="font-weight-bold text-primary ml-1" data-dismiss="modal" data-toggle="modal" data-target="#login-modal">Login</a>
                </span>
            </div>
        </div>
    </div>
</div>

<script>
function handleSignupSubmit(e) {
    if (e) e.preventDefault();
    const form = document.getElementById('signup-form');
    if (!form) return false;

    // Show centered Terms and Conditions Modal first
    showTermsModal(function() {
        const loading = document.getElementById('loading');
        if (loading) loading.style.display = 'block';

        const formData = new FormData(form);
        formData.append('tc_accepted', '1');

        fetch('api/signup_submit.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(d => {
            if (loading) loading.style.display = 'none';
            alert(d.message);
            if (d.success) {
                $('#signup-modal').modal('hide');
                window.location.href = d.redirect || 'profile.php';
            } else {
                // Refresh captcha on failure
                const captchaImg = document.getElementById('imgSignupCaptcha');
                if (captchaImg) captchaImg.src = 'api/captcha.php?' + Math.random();
            }
        })
        .catch(err => {
            if (loading) loading.style.display = 'none';
            alert('An unexpected error occurred. Please try again.');
        });
    });

    return false;
}
</script>
