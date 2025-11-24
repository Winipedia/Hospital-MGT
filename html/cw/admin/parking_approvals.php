<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['staffno'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../db.inc.php';

// Check if user is admin
$staffno = $_SESSION['staffno'];
$admin_check_sql = "SELECT is_admin FROM doctor WHERE staffno = ?";
$admin_check_stmt = $conn->prepare($admin_check_sql);
$admin_check_stmt->bind_param("s", $staffno);
$admin_check_stmt->execute();
$admin_result = $admin_check_stmt->get_result();
$admin_data = $admin_result->fetch_assoc();
$admin_check_stmt->close();

if (!$admin_data || $admin_data['is_admin'] != 1) {
    header('Location: ../dashboard.php');
    exit();
}

$success = '';
$error = '';

// Handle approve request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_permit'])) {
    $permit_id = intval($_POST['permit_id'] ?? 0);
    $permit_number = strtoupper(trim($_POST['permit_number'] ?? ''));
    $activation_date = date('Y-m-d');

    if ($permit_id > 0 && !empty($permit_number)) {
        // Check if permit number already exists
        $check_permit_sql = "SELECT permit_id FROM parking_permit WHERE permit_number = ?";
        $check_permit_stmt = $conn->prepare($check_permit_sql);
        $check_permit_stmt->bind_param("s", $permit_number);
        $check_permit_stmt->execute();
        $check_permit_result = $check_permit_stmt->get_result();

        if ($check_permit_result->num_rows > 0) {
            $error = "Permit number '$permit_number' already exists. Please use a unique permit number.";
        } else {
            // Get permit details
            $permit_sql = "SELECT permit_choice FROM parking_permit WHERE permit_id = ? AND status = 'pending'";
            $permit_stmt = $conn->prepare($permit_sql);
            $permit_stmt->bind_param("i", $permit_id);
            $permit_stmt->execute();
            $permit_result = $permit_stmt->get_result();

            if ($permit_result->num_rows > 0) {
                $permit_data = $permit_result->fetch_assoc();

                // Calculate end date based on permit choice
                if ($permit_data['permit_choice'] === 'monthly') {
                    $end_date = date('Y-m-d', strtotime('+30 days'));
                } else {
                    $end_date = date('Y-m-d', strtotime('+365 days'));
                }

                // Update permit status
                $update_sql = "UPDATE parking_permit
                              SET status = 'approved',
                                  approved_by = ?,
                                  activation_date = ?,
                                  end_date = ?,
                                  permit_number = ?
                              WHERE permit_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ssssi", $staffno, $activation_date, $end_date, $permit_number, $permit_id);

                if ($update_stmt->execute()) {
                    $success = "Parking permit #$permit_number approved successfully!";

                    // Audit log
                    $audit_sql = "INSERT INTO audit_log (user_id, action, table_name, record_id, new_value, ip_address)
                                  VALUES (?, 'UPDATE', 'parking_permit', ?, ?, ?)";
                    $audit_stmt = $conn->prepare($audit_sql);
                    $ip_address = $_SERVER['REMOTE_ADDR'];
                    $audit_value = "Approved parking permit #$permit_number";
                    $audit_stmt->bind_param("siss", $staffno, $permit_id, $audit_value, $ip_address);
                    $audit_stmt->execute();
                    $audit_stmt->close();
                } else {
                    $error = 'Failed to approve permit';
                }
                $update_stmt->close();
            } else {
                $error = 'Permit not found or already processed';
            }
            $permit_stmt->close();
        }
        $check_permit_stmt->close();
    } else {
        $error = 'Permit number is required';
    }
}

// Handle reject request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_permit'])) {
    $permit_id = intval($_POST['permit_id'] ?? 0);
    $rejection_reason = trim($_POST['rejection_reason'] ?? '');

    if ($permit_id > 0 && !empty($rejection_reason)) {
        // Update permit status
        $update_sql = "UPDATE parking_permit
                      SET status = 'rejected',
                          approved_by = ?,
                          rejection_reason = ?
                      WHERE permit_id = ? AND status = 'pending'";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssi", $staffno, $rejection_reason, $permit_id);

        if ($update_stmt->execute()) {
            if ($update_stmt->affected_rows > 0) {
                $success = "Parking permit request rejected";

                // Audit log
                $audit_sql = "INSERT INTO audit_log (user_id, action, table_name, record_id, new_value, ip_address)
                              VALUES (?, 'UPDATE', 'parking_permit', ?, ?, ?)";
                $audit_stmt = $conn->prepare($audit_sql);
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $audit_value = "Rejected parking permit: $rejection_reason";
                $audit_stmt->bind_param("siss", $staffno, $permit_id, $audit_value, $ip_address);
                $audit_stmt->execute();
                $audit_stmt->close();
            } else {
                $error = 'Permit not found or already processed';
            }
        } else {
            $error = 'Failed to reject permit';
        }
        $update_stmt->close();
    } else {
        $error = 'Rejection reason is required';
    }
}

// Get all pending parking permit requests
$pending_sql = "SELECT pp.*,
                d.firstname, d.lastname, d.staffno as doctor_staffno,
                s.specialisation_name
                FROM parking_permit pp
                JOIN doctor d ON pp.doctor_id = d.staffno
                LEFT JOIN specialisation s ON d.specialisation_id = s.specialisation_id
                WHERE pp.status = 'pending'
                ORDER BY pp.request_date ASC";
$pending_result = $conn->query($pending_sql);
$pending_permits = $pending_result->fetch_all(MYSQLI_ASSOC);

// Get recently processed permits (last 10)
$processed_sql = "SELECT pp.*,
                  d.firstname, d.lastname, d.staffno as doctor_staffno,


                  a.firstname as approved_by_firstname, a.lastname as approved_by_lastname
                  FROM parking_permit pp
                  JOIN doctor d ON pp.doctor_id = d.staffno
                  LEFT JOIN doctor a ON pp.approved_by = a.staffno
                  WHERE pp.status IN ('approved', 'rejected')
                  ORDER BY pp.request_date DESC
                  LIMIT 10";
$processed_result = $conn->query($processed_sql);
$processed_permits = $processed_result->fetch_all(MYSQLI_ASSOC);

// Set page variables for header
$page_title = 'Parking Permit Approvals - QMC Hospital Management System';
$extra_css = [];
$css_path_prefix = '../'; // For admin subdirectory

// Include header
require_once '../includes/header.php';

// Include navbar
require_once '../includes/navbar.php';
?>

<div class="container">
    <div class="card">
        <h1>🅿️ Parking Permit Approvals</h1>
        <p>Review and process parking permit requests</p>
        <span class="badge badge-info" style="font-size: 14px;">Admin Panel</span>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Pending Requests -->
    <div class="card">
        <h2>⏳ Pending Requests (<?php echo count($pending_permits); ?>)</h2>

        <?php if (count($pending_permits) > 0): ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Request Date</th>
                            <th>Doctor</th>
                            <th>Staff No</th>
                            <th>Specialisation</th>
                            <th>Car Registration</th>
                            <th>Permit Type</th>
                            <th>Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_permits as $permit): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($permit['request_date'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($permit['firstname'] . ' ' . $permit['lastname']); ?></strong></td>
                                <td><?php echo htmlspecialchars($permit['doctor_staffno']); ?></td>
                                <td><?php echo htmlspecialchars($permit['specialisation_name'] ?? 'N/A'); ?></td>
                                <td><strong><?php echo htmlspecialchars($permit['car_registration']); ?></strong></td>
                                <td>
                                    <span class="badge <?php echo $permit['permit_choice'] === 'monthly' ? 'badge-info' : 'badge-primary'; ?>">
                                        <?php echo ucfirst($permit['permit_choice']); ?>
                                    </span>
                                </td>
                                <td><strong>£<?php echo number_format($permit['amount'], 2); ?></strong></td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <!-- Approve Button -->
                                        <button type="button" class="btn btn-success"
                                                style="padding: 5px 10px; font-size: 12px;"
                                                onclick="showApproveModal(<?php echo $permit['permit_id']; ?>, '<?php echo htmlspecialchars($permit['firstname'] . ' ' . $permit['lastname']); ?>', '<?php echo htmlspecialchars($permit['car_registration']); ?>')">
                                            ✓ Approve
                                        </button>

                                        <!-- Reject Button -->
                                        <button type="button" class="btn btn-danger"
                                                style="padding: 5px 10px; font-size: 12px;"
                                                onclick="showRejectModal(<?php echo $permit['permit_id']; ?>, '<?php echo htmlspecialchars($permit['firstname'] . ' ' . $permit['lastname']); ?>')">
                                            ✗ Reject
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <strong>No pending requests</strong><br>
                All parking permit requests have been processed.
            </div>
        <?php endif; ?>
    </div>

    <!-- Recently Processed -->
    <div class="card">
        <h2>📋 Recently Processed (Last 10)</h2>

        <?php if (count($processed_permits) > 0): ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Request Date</th>
                            <th>Doctor</th>
                            <th>Car Registration</th>
                            <th>Permit Type</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Processed By</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($processed_permits as $permit): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($permit['request_date'])); ?></td>
                                <td><?php echo htmlspecialchars($permit['firstname'] . ' ' . $permit['lastname']); ?></td>
                                <td><strong><?php echo htmlspecialchars($permit['car_registration']); ?></strong></td>
                                <td><?php echo ucfirst($permit['permit_choice']); ?></td>
                                <td>£<?php echo number_format($permit['amount'], 2); ?></td>
                                <td>
                                    <?php if ($permit['status'] === 'approved'): ?>
                                        <span class="badge badge-success">Approved</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    if ($permit['approved_by_firstname']) {
                                        echo htmlspecialchars($permit['approved_by_firstname'] . ' ' . $permit['approved_by_lastname']);
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($permit['status'] === 'approved'): ?>
                                        <small>
                                            Permit: <strong><?php echo htmlspecialchars($permit['permit_number']); ?></strong><br>
                                            Valid: <?php echo date('d/m/Y', strtotime($permit['activation_date'])); ?> -
                                                   <?php echo date('d/m/Y', strtotime($permit['end_date'])); ?>
                                        </small>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-secondary"
                                                style="padding: 3px 8px; font-size: 11px;"
                                                onclick="alert('Rejection Reason:\n<?php echo htmlspecialchars($permit['rejection_reason'] ?? 'No reason provided'); ?>')">
                                            View Reason
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>

            <div class="alert alert-info">
                <strong>No processed requests</strong><br>
                No parking permits have been approved or rejected yet.
            </div>
        <?php endif; ?>
    </div>

    <div class="text-center mt-3">
        <a href="../dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>
</div>

<!-- Approve Modal -->
<div id="approveModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 10px; max-width: 500px; width: 90%;">
        <h2 style="margin-top: 0; color: #28a745;">✓ Approve Parking Permit</h2>
        <p id="approveDoctorName" style="color: #666; margin-bottom: 5px;"></p>
        <p id="approveCarReg" style="color: #666; margin-bottom: 20px; font-size: 14px;"></p>

        <form method="POST" action="" id="approveForm">
            <input type="hidden" name="permit_id" id="approvePermitId">

            <div class="form-group">
                <label for="permit_number">Parking Permit Number *</label>
                <input type="text" id="permit_number" name="permit_number"
                       placeholder="e.g., PP-2024-00001"
                       required
                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; text-transform: uppercase;">
                <small style="color: #666; font-size: 12px;">
                    Suggested format: PP-YEAR-NUMBER (e.g., PP-2024-00001)
                </small>
            </div>

            <div style="padding: 15px; background: #e7f3ff; border-left: 4px solid #007bff; border-radius: 5px; margin-bottom: 20px;">
                <strong>ℹ️ Note:</strong> The permit will be activated today and valid for:
                <ul style="margin: 10px 0 0 20px; line-height: 1.8;">
                    <li><strong>Monthly:</strong> 30 days from today</li>
                    <li><strong>Yearly:</strong> 365 days from today</li>
                </ul>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeApproveModal()">Cancel</button>
                <button type="submit" name="approve_permit" class="btn btn-success">Approve Permit</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 10px; max-width: 500px; width: 90%;">
        <h2 style="margin-top: 0;">Reject Parking Permit</h2>
        <p id="rejectDoctorName" style="color: #666; margin-bottom: 20px;"></p>

        <form method="POST" action="" id="rejectForm">
            <input type="hidden" name="permit_id" id="rejectPermitId">

            <div class="form-group">
                <label for="rejection_reason">Rejection Reason *</label>
                <textarea id="rejection_reason" name="rejection_reason" rows="4"
                          placeholder="Enter reason for rejection..."
                          required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" name="reject_permit" class="btn btn-danger">Reject Permit</button>
            </div>
        </form>
    </div>
</div>

<script>
// Approve Modal Functions
function showApproveModal(permitId, doctorName, carReg) {
    document.getElementById('approvePermitId').value = permitId;
    document.getElementById('approveDoctorName').textContent = 'Approving request from: ' + doctorName;
    document.getElementById('approveCarReg').textContent = 'Car Registration: ' + carReg;

    // Generate suggested permit number
    const year = new Date().getFullYear();
    const suggestedNumber = 'PP-' + year + '-' + String(permitId).padStart(5, '0');
    document.getElementById('permit_number').value = suggestedNumber;

    document.getElementById('approveModal').style.display = 'flex';
}

function closeApproveModal() {
    document.getElementById('approveModal').style.display = 'none';
}

// Close approve modal when clicking outside
document.getElementById('approveModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeApproveModal();
    }
});

// Reject Modal Functions
function showRejectModal(permitId, doctorName) {
    document.getElementById('rejectPermitId').value = permitId;
    document.getElementById('rejectDoctorName').textContent = 'Rejecting request from: ' + doctorName;
    document.getElementById('rejection_reason').value = '';
    document.getElementById('rejectModal').style.display = 'flex';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}

// Close reject modal when clicking outside
document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRejectModal();
    }
});
</script>

<?php
$conn->close();
require_once '../includes/footer.php';
?>
