<!-- Become a Property Owner Modal -->
<div class="modal fade" id="modalOwnerRegister" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title font-weight-bold text-dark"><i class="fas fa-building text-primary"></i> Become a Property Owner on PGLife</h5>
                    <p class="text-muted mb-0" style="font-size: 12px;">Submit your owner details & property information. Accounts are reviewed by Super Admin before activation.</p>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
            </div>
            <form id="formOwnerRegister" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <h6 class="font-weight-bold text-primary mb-3"><i class="fas fa-user font-weight-normal"></i> 1. Owner Personal Information</h6>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold small">Full Name *</label>
                            <input type="text" class="form-control" name="full_name" placeholder="e.g. Rajesh Sharma" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold small">Mobile Phone Number *</label>
                            <input type="text" class="form-control" name="phone" placeholder="10-digit mobile number" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold small">Email Address *</label>
                            <input type="email" class="form-control" name="email" placeholder="owner@example.com" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold small">Create Account Password *</label>
                            <input type="password" class="form-control" name="password" placeholder="Min 6 characters" required>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h6 class="font-weight-bold text-primary mb-3"><i class="fas fa-store font-weight-normal"></i> 2. Property Information</h6>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold small">Primary Property Name *</label>
                            <input type="text" class="form-control" name="property_name" placeholder="e.g. Sharma PG & Residency" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold small">City Location *</label>
                            <select class="form-control" name="city_id" required>
                                <option value="1">Delhi</option>
                                <option value="2">Mumbai</option>
                                <option value="3">Bengaluru</option>
                                <option value="4">Hyderabad</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold small">Property Full Address *</label>
                        <input type="text" class="form-control" name="property_address" placeholder="House No, Street, Landmark" required>
                    </div>

                    <hr class="my-4">

                    <h6 class="font-weight-bold text-primary mb-3"><i class="fas fa-file-upload font-weight-normal"></i> 3. Verification Documents (Required for Super Admin Review)</h6>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold small">Government ID Proof (Aadhaar / Passport) *</label>
                            <input type="file" class="form-control-file" name="govt_id_doc" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold small">Property Ownership Doc / Electricity Bill *</label>
                            <input type="file" class="form-control-file" name="property_doc" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold small">Business License (Optional)</label>
                            <input type="file" class="form-control-file" name="business_license_doc">
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold small">Bank Account Number (Optional)</label>
                            <input type="text" class="form-control" name="bank_account" placeholder="A/C Number & IFSC Code">
                        </div>
                    </div>

                    <hr class="my-4">

                    <h6 class="font-weight-bold text-primary mb-3"><i class="fas fa-shield-alt font-weight-normal"></i> 4. Security Verification</h6>
                    <div class="form-group">
                        <label class="font-weight-bold small">Security Verification (CAPTCHA) *</label>
                        <div class="d-flex align-items-center mb-2">
                            <img src="api/captcha.php" id="imgOwnerRegisterCaptcha" style="border-radius: 8px; border: 1px solid #cbd5e1; height: 40px;" alt="CAPTCHA">
                            <button type="button" class="btn btn-sm btn-light ml-2 border" onclick="document.getElementById('imgOwnerRegisterCaptcha').src='api/captcha.php?'+Math.random()" title="Refresh CAPTCHA">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <input type="text" class="form-control" name="captcha" placeholder="Enter CAPTCHA code shown above" autocomplete="off" required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary font-weight-bold"><i class="fas fa-paper-plane"></i> Submit Owner Registration</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('formOwnerRegister')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;

    // Trigger centered Terms & Conditions modal before submitting
    showTermsModal(function() {
        const formData = new FormData(form);
        formData.append('tc_accepted', '1');

        fetch('api/owner_register.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(d => {
                alert(d.message);
                if (d.success) {
                    $('#modalOwnerRegister').modal('hide');
                    form.reset();
                } else {
                    const captchaImg = document.getElementById('imgOwnerRegisterCaptcha');
                    if (captchaImg) captchaImg.src = 'api/captcha.php?' + Math.random();
                }
            });
    });
});
</script>
