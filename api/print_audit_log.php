<?php
session_start();
require_once __DIR__ . '/../includes/database_connect.php';
require_once __DIR__ . '/../includes/rbac.php';

$admin = check_role('admin');

$action_filter = isset($_GET['action_filter']) ? mysqli_real_escape_string($conn, trim($_GET['action_filter'])) : '';
$start_date = isset($_GET['start_date']) ? mysqli_real_escape_string($conn, trim($_GET['start_date'])) : '';
$end_date = isset($_GET['end_date']) ? mysqli_real_escape_string($conn, trim($_GET['end_date'])) : '';

$where_clauses = array("1=1");
if (!empty($action_filter)) {
    $where_clauses[] = "a.action LIKE '%$action_filter%'";
}
if (!empty($start_date)) {
    $where_clauses[] = "DATE(a.created_at) >= '$start_date'";
}
if (!empty($end_date)) {
    $where_clauses[] = "DATE(a.created_at) <= '$end_date'";
}

$where_sql = implode(' AND ', $where_clauses);
$sql = "SELECT a.*, u.full_name AS actor_name, u.email AS actor_email, u.role AS actor_role 
        FROM audit_logs a 
        LEFT JOIN users u ON a.actor_id = u.id 
        WHERE $where_sql 
        ORDER BY a.id DESC LIMIT 500";

$res = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Official System Audit Log Report | PGLife Enterprise</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
    <style>
        body { font-family: 'Open Sans', Arial, sans-serif; background: #f8fafc; color: #0f172a; padding: 30px; }
        .report-header { border-bottom: 3px double #ef4444; padding-bottom: 20px; margin-bottom: 30px; }
        .table-audit th { background: #0f172a; color: white; border: none; font-size: 13px; text-transform: uppercase; }
        .table-audit td { font-size: 13px; vertical-align: middle; }
        @media print {
            .no-print { display: none !important; }
            body { background: white; padding: 0; }
            .card { border: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print mb-4 d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm border">
        <div>
            <h5 class="font-weight-bold mb-0 text-danger"><i class="fas fa-history"></i> System Audit Log Print Controls</h5>
            <small class="text-muted">Filter audit entries by date or action keyword before printing.</small>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-danger font-weight-bold mr-2"><i class="fas fa-print"></i> Print Official Audit Record</button>
            <button onclick="window.close()" class="btn btn-secondary font-weight-bold">Close Window</button>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="no-print card mb-4 border-0 shadow-sm p-3">
        <form method="GET" class="form-row align-items-end">
            <div class="form-group col-md-3 mb-0">
                <label class="font-weight-bold small">Action Keyword</label>
                <input type="text" name="action_filter" class="form-control form-control-sm" value="<?= htmlspecialchars($action_filter); ?>" placeholder="e.g. login, review, update">
            </div>
            <div class="form-group col-md-3 mb-0">
                <label class="font-weight-bold small">Start Date</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($start_date); ?>">
            </div>
            <div class="form-group col-md-3 mb-0">
                <label class="font-weight-bold small">End Date</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($end_date); ?>">
            </div>
            <div class="form-group col-md-3 mb-0">
                <button type="submit" class="btn btn-sm btn-dark font-weight-bold btn-block"><i class="fas fa-filter"></i> Apply Filter</button>
            </div>
        </form>
    </div>

    <div class="card p-4 border-0 shadow-sm bg-white">
        <div class="report-header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="font-weight-bold text-dark mb-1">PGLife Housing System Audit Trail Report</h3>
                <div class="text-muted small">Generated on <?= date('d F Y, h:i A'); ?> | Super Admin Identity: <?= htmlspecialchars($admin['full_name']); ?></div>
            </div>
            <div class="text-right">
                <span class="badge badge-danger p-2 font-weight-bold" style="font-size: 13px;">CONFIDENTIAL & AUDIT CERTIFIED</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-audit">
                <thead>
                    <tr>
                        <th>Log ID</th>
                        <th>Timestamp</th>
                        <th>Actor / User</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th>Details / Payload</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res && mysqli_num_rows($res) > 0) {
                        while ($row = mysqli_fetch_assoc($res)) { ?>
                        <tr>
                            <td>#LOG-<?= $row['id']; ?></td>
                            <td><?= $row['created_at']; ?></td>
                            <td class="font-weight-bold">
                                <?= htmlspecialchars($row['actor_name'] ? $row['actor_name'] : 'System Process'); ?>
                                <?php if (!empty($row['actor_email'])) { ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($row['actor_email']); ?></small>
                                <?php } ?>
                            </td>
                            <td><span class="badge badge-light border"><?= strtoupper($row['actor_role'] ? $row['actor_role'] : 'System'); ?></span></td>
                            <td><strong class="text-danger"><?= htmlspecialchars($row['action']); ?></strong></td>
                            <td><?= htmlspecialchars($row['details']); ?></td>
                            <td><code><?= htmlspecialchars($row['ip_address'] ? $row['ip_address'] : '127.0.0.1'); ?></code></td>
                        </tr>
                    <?php } } else { ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted p-4">No audit log entries matching filter.</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-3 border-top d-flex justify-content-between text-muted small">
            <div>PGLife Enterprise Governance & Compliance Framework</div>
            <div>Total Audit Records Returned: <?= $res ? mysqli_num_rows($res) : 0; ?></div>
        </div>
    </div>
</body>
</html>
