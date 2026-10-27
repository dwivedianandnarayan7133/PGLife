<!-- PGLife Centered Terms & Conditions Modal with Community Rules -->
<style>
#terms-modal {
    z-index: 1070 !important;
}
.modal-backdrop.terms-backdrop {
    z-index: 1065 !important;
}
</style>

<div class="modal fade" id="terms-modal" tabindex="-1" role="dialog" aria-labelledby="termsModalHeading" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content" style="border-radius: 20px; overflow: hidden; border: none; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45);">
            <!-- Modal Header -->
            <div class="modal-header bg-dark text-white p-4" style="background: linear-gradient(135deg, #0f172a, #1e293b) !important;">
                <div>
                    <h5 class="modal-title font-weight-bold text-white mb-1" id="termsModalHeading">
                        <i class="fas fa-file-contract text-warning mr-2"></i> PGLife Platform Terms & Community Rules
                    </h5>
                    <p class="text-muted small mb-0">Please read and accept our platform policies and resident rules before completing your registration.</p>
                </div>
                <button type="button" class="close text-white opacity-75" onclick="declineTermsModal()" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Modal Body: Rules & Terms Content -->
            <div class="modal-body p-4" style="max-height: 420px; overflow-y: auto; font-size: 13.5px; line-height: 1.6; color: #334155; background-color: #f8fafc;">
                
                <div class="alert alert-warning py-2 mb-3 font-weight-bold text-dark" style="font-size: 12.5px;">
                    <i class="fas fa-exclamation-triangle text-warning mr-1"></i> Registration Compliance Notice: You must agree to these rules to create an account on PGLife.
                </div>

                <!-- Section 1 -->
                <div class="bg-white p-3 rounded-lg border mb-3 shadow-sm">
                    <h6 class="font-weight-bold text-dark mb-2"><i class="fas fa-user-shield text-primary mr-1"></i> 1. Accurate Account Registration & Verification</h6>
                    <p class="mb-0">By registering as a Student Resident or Property Owner, you guarantee that all submitted details (full name, 10-digit mobile number, email, and identity proof documents) are 100% accurate, current, and legally yours. Provision of fake or fraudulent data will lead to immediate account rejection and legal reporting.</p>
                </div>

                <!-- Section 2: Hostel Rules & Regulations -->
                <div class="bg-white p-3 rounded-lg border mb-3 shadow-sm">
                    <h6 class="font-weight-bold text-dark mb-2"><i class="fas fa-gavel text-danger mr-1"></i> 2. Resident Code of Conduct & Hostel Rules</h6>
                    <ul class="mb-0 pl-3">
                        <li><strong>Quiet Hours:</strong> Silence must be maintained in rooms and corridors between 10:00 PM and 6:00 AM.</li>
                        <li><strong>Prohibited Items:</strong> Alcohol, smoking, drugs, and hazardous materials are strictly banned on property premises.</li>
                        <li><strong>Guest Policy:</strong> Visitors are permitted only in designated lounge areas until 8:00 PM with owner approval. Overnight guests require prior approval.</li>
                        <li><strong>Rent Timelines:</strong> Monthly accommodation rent must be cleared by the 5th day of every calendar month.</li>
                    </ul>
                </div>

                <!-- Section 3 -->
                <div class="bg-white p-3 rounded-lg border mb-3 shadow-sm">
                    <h6 class="font-weight-bold text-dark mb-2"><i class="fas fa-store text-info mr-1"></i> 3. Property Owner Verification & Listing Authenticity</h6>
                    <p class="mb-0">Property Owners agree to upload valid government identity cards, property ownership proofs, and true hostel photographs. Super Admin verifies all listings prior to publication. Misleading room pricing or false seat availability is strictly prohibited.</p>
                </div>

                <!-- Section 4 -->
                <div class="bg-white p-3 rounded-lg border mb-3 shadow-sm">
                    <h6 class="font-weight-bold text-dark mb-2"><i class="fas fa-file-signature text-success mr-1"></i> 4. Digital Rental Agreements & Super Admin Seal</h6>
                    <p class="mb-0">All PG bookings generate digital rental agreements authenticated and sealed by Super Admin. Digital signatures and official seals applied on agreements are legally binding under applicable tenancy standards.</p>
                </div>

                <!-- Section 5 -->
                <div class="bg-white p-3 rounded-lg border mb-3 shadow-sm">
                    <h6 class="font-weight-bold text-dark mb-2"><i class="fas fa-archive text-secondary mr-1"></i> 5. Account Deletion & Data Preservation Compliance</h6>
                    <p class="mb-0">Users and owners can submit account deletion requests through their profile dashboard. Deletion requires Super Admin review. Account records, agreement logs, and transaction audit trails will be preserved by the administration for audit, safety, and legal compliance.</p>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light p-4 flex-column align-items-stretch">
                <div class="custom-control custom-checkbox mb-3 px-3 py-2 bg-white rounded border">
                    <input type="checkbox" class="custom-control-input" id="chkAcceptTerms" onchange="toggleTermsAcceptBtn()">
                    <label class="custom-control-label font-weight-bold text-dark" for="chkAcceptTerms" style="font-size: 13.5px; cursor: pointer;">
                        I have read, understood, and agree to abide by all PGLife Terms, Conditions, Hostel Rules, and Data Preservation Policies. *
                    </label>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary px-4 font-weight-bold" onclick="declineTermsModal()">Decline & Exit</button>
                    <button type="button" id="btnAcceptTermsSubmit" class="btn btn-success px-4 font-weight-bold py-2" disabled onclick="confirmTermsAndRegister()">
                        <i class="fas fa-check-circle mr-1"></i> Accept Terms & Register
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let pendingFormCallback = null;

function toggleTermsAcceptBtn() {
    const chk = document.getElementById('chkAcceptTerms');
    const btn = document.getElementById('btnAcceptTermsSubmit');
    if (chk && btn) {
        btn.disabled = !chk.checked;
        if (chk.checked) {
            btn.classList.add('shadow');
        } else {
            btn.classList.remove('shadow');
        }
    }
}

function showTermsModal(onAcceptCallback) {
    pendingFormCallback = onAcceptCallback;
    const chk = document.getElementById('chkAcceptTerms');
    const btn = document.getElementById('btnAcceptTermsSubmit');
    if (chk) chk.checked = false;
    if (btn) btn.disabled = true;
    
    $('#terms-modal').modal('show');
    
    // Ensure backdrop sits properly above registration modals
    setTimeout(function() {
        $('.modal-backdrop').not('.terms-backdrop').last().addClass('terms-backdrop');
    }, 150);
}

function confirmTermsAndRegister() {
    const chk = document.getElementById('chkAcceptTerms');
    if (!chk || !chk.checked) return;
    $('#terms-modal').modal('hide');
    if (typeof pendingFormCallback === 'function') {
        const cb = pendingFormCallback;
        pendingFormCallback = null;
        cb();
    }
}

function declineTermsModal() {
    $('#terms-modal').modal('hide');
    pendingFormCallback = null;
}
</script>
