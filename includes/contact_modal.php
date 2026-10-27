<!-- Contact Us & Callback Modal -->
<div class="modal fade" id="modalContact" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-headset"></i> PGLife Support & Callback Center</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body p-4">
                <ul class="nav nav-pills nav-justified mb-4" id="contactTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active font-weight-bold" id="tab-callback-link" data-toggle="pill" href="#tab-callback" role="tab"><i class="fas fa-phone-volume text-warning mr-1"></i> Request Callback</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold" id="tab-ticket-link" data-toggle="pill" href="#tab-ticket" role="tab"><i class="fas fa-ticket-alt text-info mr-1"></i> Raise Support Ticket</a>
                    </li>
                </ul>

                <div class="tab-content" id="contactTabContent">
                    <!-- Tab 1: Request Callback -->
                    <div class="tab-pane fade show active" id="tab-callback" role="tabpanel">
                        <form id="formRequestCallback">
                            <input type="hidden" name="action" value="request_callback">
                            <p class="text-muted small mb-3">Leave your details below and our housing advisor will call you back within 15 minutes!</p>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="font-weight-bold small">Your Full Name *</label>
                                    <input type="text" class="form-control" name="user_name" value="<?php echo isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : ''; ?>" placeholder="e.g. Anand Narayan" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="font-weight-bold small">Mobile Phone Number *</label>
                                    <input type="tel" class="form-control" name="phone" placeholder="e.g. 9876543210" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label class="font-weight-bold small">Applicant / User Type *</label>
                                    <select class="form-control" name="user_type" required>
                                        <option value="Student Resident">Student Resident</option>
                                        <option value="Property Owner / Landlord">Property Owner / Landlord</option>
                                        <option value="Working Professional">Working Professional</option>
                                        <option value="Parent / Guardian">Parent / Guardian</option>
                                        <option value="General Inquiry">General Inquiry</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="font-weight-bold small">Preferred Time Slot *</label>
                                    <select class="form-control" name="time_slot" required>
                                        <option value="Immediately (Within 15 mins)">Immediately (Within 15 mins)</option>
                                        <option value="Morning (9 AM - 12 PM)">Morning (9 AM - 12 PM)</option>
                                        <option value="Afternoon (12 PM - 4 PM)">Afternoon (12 PM - 4 PM)</option>
                                        <option value="Evening (4 PM - 8 PM)">Evening (4 PM - 8 PM)</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="font-weight-bold small">Inquiry Topic *</label>
                                    <select class="form-control" name="topic" required>
                                        <option value="Hostel Booking Assistance">Hostel Booking Assistance</option>
                                        <option value="Room Rent & Deposit Query">Room Rent & Deposit Query</option>
                                        <option value="Property Owner Registration">Property Owner Registration</option>
                                        <option value="Agreement & Document Help">Agreement & Document Help</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-warning font-weight-bold btn-block text-dark mt-3">
                                <i class="fas fa-phone-alt"></i> Request Instant Callback
                            </button>
                        </form>
                    </div>

                    <!-- Tab 2: Raise Support Ticket -->
                    <div class="tab-pane fade" id="tab-ticket" role="tabpanel">
                        <form id="formSubmitTicket">
                            <input type="hidden" name="action" value="create">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="font-weight-bold small">Category *</label>
                                    <select class="form-control" name="ticket_type" required>
                                        <option value="maintenance">Maintenance & Repairs</option>
                                        <option value="billing">Billing & Rent Refund</option>
                                        <option value="complaint">Owner / Resident Complaint</option>
                                        <option value="other">Other Inquiry</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="font-weight-bold small">Subject *</label>
                                    <input type="text" class="form-control" name="subject" placeholder="Brief issue description" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold small">Detailed Message *</label>
                                <textarea class="form-control" name="message" rows="3" placeholder="Describe your issue or question in detail..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary font-weight-bold btn-block mt-3">
                                <i class="fas fa-paper-plane"></i> Submit Support Ticket
                            </button>
                        </form>
                    </div>
                </div>

                <hr class="my-4">

                <div class="row text-center">
                    <div class="col-md-6 mb-2">
                        <div class="font-weight-bold text-dark small"><i class="fas fa-phone-alt text-primary mr-1"></i> Helpline</div>
                        <div class="text-muted small">+91 1800-123-7454 (Toll-Free 24x7)</div>
                    </div>
                    <div class="col-md-6 mb-2">
                        <div class="font-weight-bold text-dark small"><i class="fas fa-envelope text-success mr-1"></i> Email</div>
                        <div class="text-muted small">support@pglife.com</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const callbackForm = document.getElementById('formRequestCallback');
    if (callbackForm) {
        callbackForm.addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('api/tickets.php', { method: 'POST', body: new FormData(this) })
                .then(r => r.json())
                .then(d => {
                    alert(d.message);
                    if (d.success) $('#modalContact').modal('hide');
                });
        });
    }

    const ticketForm = document.getElementById('formSubmitTicket');
    if (ticketForm) {
        ticketForm.addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('api/tickets.php', { method: 'POST', body: new FormData(this) })
                .then(r => r.json())
                .then(d => {
                    alert(d.message);
                    if (d.success) $('#modalContact').modal('hide');
                });
        });
    }
});
</script>
