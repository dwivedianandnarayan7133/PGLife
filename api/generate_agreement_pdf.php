<?php
session_start();
require_once __DIR__ . '/../includes/database_connect.php';
require_once __DIR__ . '/../includes/notification_helper.php';

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 1;

$sql = "SELECT b.*, p.owner_id, p.name AS property_name, p.address AS property_address, p.rent AS prop_rent, c.name AS city_name,
               u.full_name AS tenant_name, u.phone AS tenant_phone, u.email AS tenant_email, u.college_name,
               o.full_name AS owner_name, o.phone AS owner_phone, o.email AS owner_email
        FROM bookings b
        INNER JOIN properties p ON b.property_id = p.id
        INNER JOIN cities c ON p.city_id = c.id
        INNER JOIN users u ON b.user_id = u.id
        LEFT JOIN users o ON p.owner_id = o.id
        WHERE b.id = $booking_id";

$res = mysqli_query($conn, $sql);
if (!$res || mysqli_num_rows($res) === 0) {
    die("Booking record #$booking_id not found.");
}

$b = mysqli_fetch_assoc($res);
$owner_id = !empty($b['owner_id']) ? (int)$b['owner_id'] : 1;
$user_id = (int)$b['user_id'];
$doc_url = "api/generate_agreement_pdf.php?booking_id=" . $booking_id;

// Check or Auto-Create Agreement Record in Database
$agr_check = mysqli_query($conn, "SELECT * FROM agreements WHERE booking_id = $booking_id");
if (mysqli_num_rows($agr_check) === 0) {
    $ins_agr = "INSERT INTO agreements (booking_id, owner_id, user_id, agreement_doc, status) 
                VALUES ($booking_id, $owner_id, $user_id, '$doc_url', 'pending_review')";
    mysqli_query($conn, $ins_agr);
    $agr_check = mysqli_query($conn, "SELECT * FROM agreements WHERE booking_id = $booking_id");

    // Notify Super Admin of new document queued for approval
    $admin_res = mysqli_query($conn, "SELECT id FROM users WHERE role = 'admin'");
    while ($admin = mysqli_fetch_assoc($admin_res)) {
        create_notification($admin['id'], 'agreement_review', '📋 Rental Agreement Needs Approval', "Rental agreement generated for Booking #$booking_id. Pending Super Admin verification & seal.", 'admin_portal.php?tab=booked');
    }
}

$agr = mysqli_fetch_assoc($agr_check);
$agr_status = !empty($agr['status']) ? $agr['status'] : 'pending_review';
$admin_remark = !empty($agr['admin_remark']) ? $agr['admin_remark'] : (!empty($agr['rejection_reason']) ? $agr['rejection_reason'] : '');
$sealed_at = !empty($agr['sealed_at']) ? date('d F Y, h:i A', strtotime($agr['sealed_at'])) : '';

$owner_name = !empty($b['owner_name']) ? $b['owner_name'] : 'Rajesh Sharma (Property Owner)';
$owner_phone = !empty($b['owner_phone']) ? $b['owner_phone'] : '9876543210';
$owner_email = !empty($b['owner_email']) ? $b['owner_email'] : 'owner@pglife.com';

$rent_amount = !empty($b['prop_rent']) ? (int)$b['prop_rent'] : 9500;
$deposit_amount = $rent_amount * 2;
$start_date = !empty($b['move_in_date']) ? $b['move_in_date'] : date('Y-m-d');
$end_date = date('Y-m-d', strtotime('+11 months', strtotime($start_date)));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rental Agreement #AGR-BK<?= $b['id']; ?> | PGLife</title>
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; color: #1e293b; line-height: 1.6; margin: 0; padding: 40px; background: #f8fafc; }
        .agreement-box { max-width: 800px; margin: auto; background: white; padding: 50px; border-radius: 12px; border: 2px solid #cbd5e1; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .header-stamp { text-align: center; border-bottom: 3px double #0284c7; padding-bottom: 20px; margin-bottom: 30px; }
        .title { font-size: 26px; font-weight: bold; color: #0f172a; text-transform: uppercase; letter-spacing: 1px; }
        .subtitle { font-size: 14px; color: #0284c7; font-weight: bold; margin-top: 5px; }
        .section-title { font-size: 16px; font-weight: bold; color: #0369a1; border-left: 4px solid #0284c7; padding-left: 10px; margin-top: 25px; margin-bottom: 12px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table td { padding: 8px 12px; border: 1px solid #e2e8f0; font-size: 14px; }
        .info-table td.label { font-weight: bold; background: #f1f5f9; width: 35%; }
        .stamps-box { display: flex; justify-content: space-between; margin-top: 40px; pt-3; border-top: 1px dashed #cbd5e1; }
        .stamp-col { text-align: center; width: 30%; font-size: 13px; font-weight: bold; }
        .signature-line { border-bottom: 2px solid #334155; margin-top: 40px; margin-bottom: 5px; }
        
        .seal-approved { display: inline-block; padding: 10px 20px; border: 3px double #16a34a; background: #f0fdf4; color: #15803d; font-weight: bold; font-size: 13px; border-radius: 8px; text-transform: uppercase; margin-top: 10px; }
        .seal-pending { display: inline-block; padding: 10px 20px; border: 3px double #d97706; background: #fffbeb; color: #b45309; font-weight: bold; font-size: 13px; border-radius: 8px; text-transform: uppercase; margin-top: 10px; }
        .seal-rejected { display: inline-block; padding: 10px 20px; border: 3px double #dc2626; background: #fef2f2; color: #b91c1c; font-weight: bold; font-size: 13px; border-radius: 8px; text-transform: uppercase; margin-top: 10px; }
        
        .admin-remark-box { background: #f1f5f9; border-left: 4px solid #0284c7; padding: 15px; margin-top: 20px; border-radius: 6px; }
        @media print {
            body { background: white; padding: 0; }
            .agreement-box { border: none; box-shadow: none; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="background: #0284c7; color: white; border: none; padding: 10px 24px; font-size: 14px; font-weight: bold; border-radius: 8px; cursor: pointer;">
            🖨️ Print / Download Official PDF Agreement
        </button>
    </div>

    <div class="agreement-box">
        <div class="header-stamp">
            <div class="title">Official Residential Rental Agreement</div>
            <div class="subtitle">PGLife Housing Verification & Tenant Compliance Registry</div>
            <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Agreement Reference Code: #AGR-BK<?= $b['id']; ?>-2026</div>
        </div>

        <!-- Super Admin Status Banner -->
        <?php if ($agr_status === 'approved') { ?>
            <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 12px 18px; border-radius: 8px; font-weight: bold; margin-bottom: 20px; font-size: 14px;">
                ✓ VERIFIED & APPROVED BY SUPER ADMIN: Official Seal Applied<?= $sealed_at ? " on $sealed_at" : ''; ?>.
            </div>
        <?php } elseif ($agr_status === 'rejected') { ?>
            <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px 18px; border-radius: 8px; font-weight: bold; margin-bottom: 20px; font-size: 14px;">
                ✖ REJECTED BY SUPER ADMIN: Revision required before final approval.
            </div>
        <?php } else { ?>
            <div style="background: #fef3c7; border: 1px solid #fde68a; color: #92400e; padding: 12px 18px; border-radius: 8px; font-weight: bold; margin-bottom: 20px; font-size: 14px;">
                ⏳ PENDING SUPER ADMIN APPROVAL: Submitted to Super Admin for verification, seal & remarks.
            </div>
        <?php } ?>

        <p style="font-size: 14px;">This Rental Agreement is executed on <strong><?= date('d F, Y', strtotime($b['created_at'])); ?></strong> by and between the Landlord (Property Owner) and the Tenant (Student Resident) listed below under standard housing compliance rules.</p>

        <div class="section-title">1. LANDLORD & TENANT DETAILS</div>
        <table class="info-table">
            <tr>
                <td class="label">Landlord Name (Owner)</td>
                <td><strong><?= htmlspecialchars($owner_name); ?></strong></td>
            </tr>
            <tr>
                <td class="label">Landlord Contact</td>
                <td>📞 <?= htmlspecialchars($owner_phone); ?> | ✉️ <?= htmlspecialchars($owner_email); ?></td>
            </tr>
            <tr>
                <td class="label">Tenant Name (Resident)</td>
                <td><strong><?= htmlspecialchars($b['tenant_name']); ?></strong></td>
            </tr>
            <tr>
                <td class="label">Tenant Contact</td>
                <td>📞 <?= htmlspecialchars($b['tenant_phone']); ?> | ✉️ <?= htmlspecialchars($b['tenant_email']); ?></td>
            </tr>
            <tr>
                <td class="label">College / Institution</td>
                <td><?= htmlspecialchars($b['college_name'] ? $b['college_name'] : 'Enrolled Student'); ?></td>
            </tr>
        </table>

        <div class="section-title">2. PREMISES & LEASE TERMS</div>
        <table class="info-table">
            <tr>
                <td class="label">Hostel / PG Property</td>
                <td><strong><?= htmlspecialchars($b['property_name']); ?></strong> (<?= htmlspecialchars($b['city_name']); ?>)</td>
            </tr>
            <tr>
                <td class="label">Property Full Address</td>
                <td><?= htmlspecialchars($b['property_address']); ?></td>
            </tr>
            <tr>
                <td class="label">Allocated Room Tier</td>
                <td><?= htmlspecialchars($b['room_type']); ?> Occupancy</td>
            </tr>
            <tr>
                <td class="label">Monthly Rent Amount</td>
                <td><strong>₹<?= number_format($rent_amount); ?> / month</strong> (Payable by 5th of each month)</td>
            </tr>
            <tr>
                <td class="label">Refundable Security Deposit</td>
                <td>₹<?= number_format($deposit_amount); ?> (Two months rent)</td>
            </tr>
            <tr>
                <td class="label">Agreement Tenure</td>
                <td>11 Months (From <strong><?= $start_date; ?></strong> to <strong><?= $end_date; ?></strong>)</td>
            </tr>
        </table>

        <div class="section-title">3. STANDARD HOUSE RULES & CONDITIONS</div>
        <ol style="font-size: 13px; color: #334155; padding-left: 20px; line-height: 1.8;">
            <li>The tenant agrees to use the premises strictly for residential accommodation.</li>
            <li>No hazardous materials, illegal substances, or unauthorized guests allowed overnight without prior owner notice.</li>
            <li>Rent must be paid on or before the 5th of every calendar month via PGLife portal or landlord bank transfer.</li>
            <li>30 days prior written notice is required from either party before terminating or vacating the room.</li>
        </ol>

        <?php if (!empty($admin_remark)) { ?>
            <div class="admin-remark-box">
                <div style="font-weight: bold; color: #0f172a; font-size: 13px; text-transform: uppercase;"><i class="fas fa-comment-alt"></i> Super Admin Official Remarks & Verification Notes:</div>
                <div style="font-size: 13px; color: #334155; margin-top: 4px; font-style: italic;">"<?= htmlspecialchars($admin_remark); ?>"</div>
            </div>
        <?php } ?>

        <div class="stamps-box">
            <div class="stamp-col">
                <div class="signature-line"></div>
                <div>Landlord Signature</div>
                <div style="font-size: 11px; color: #64748b;"><?= htmlspecialchars($owner_name); ?></div>
            </div>
            <div class="stamp-col">
                <?php if ($agr_status === 'approved') { ?>
                    <div class="seal-approved">🛡️ SEALED BY PGLIFE SUPER ADMIN</div>
                    <div style="font-size: 11px; color: #166534; margin-top: 4px;">Verified & Registry Sealed</div>
                <?php } elseif ($agr_status === 'rejected') { ?>
                    <div class="seal-rejected">✖ REJECTED BY ADMIN</div>
                    <div style="font-size: 11px; color: #991b1b; margin-top: 4px;">Revision Requested</div>
                <?php } else { ?>
                    <div class="seal-pending">⏳ PENDING ADMIN SEAL</div>
                    <div style="font-size: 11px; color: #92400e; margin-top: 4px;">Awaiting Super Admin Approval</div>
                <?php } ?>
            </div>
            <div class="stamp-col">
                <div class="signature-line"></div>
                <div>Tenant Signature</div>
                <div style="font-size: 11px; color: #64748b;"><?= htmlspecialchars($b['tenant_name']); ?></div>
            </div>
        </div>
    </div>
</body>
</html>
