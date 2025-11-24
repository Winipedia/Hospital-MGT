<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['staffno'])) {
    header('Location: index.php');
    exit();
}

require_once 'db.inc.php';

$success = '';
$error = '';
$staffno = $_SESSION['staffno'];

// Handle new parking permit request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_permit'])) {
    $car_registration = strtoupper(trim($_POST['car_registration'] ?? ''));
    $permit_choice = $_POST['permit_choice'] ?? '';

    // Validation
    if (empty($car_registration)) {
        $error = 'Car registration is required';
    } elseif (!in_array($permit_choice, ['monthly', 'yearly'])) {
        $error = 'Please select a valid permit type';
    } else {
        // Check if there's already a pending request
        $check_sql = "SELECT permit_id FROM parking_permit
                      WHERE doctor_id = ? AND status = 'pending'";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $staffno);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = 'You already have a pending parking permit request';
        } else {
            // Calculate amount based on permit choice
            $amount = ($permit_choice === 'monthly') ? 50.00 : 500.00;

            // Insert new parking permit request
            $insert_sql = "INSERT INTO parking_permit (doctor_id, car_registration, permit_choice, amount, status)
                          VALUES (?, ?, ?, ?, 'pending')";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sssd", $staffno, $car_registration, $permit_choice, $amount);

            if ($insert_stmt->execute()) {
                // Log the request in audit trail
                $audit_sql = "INSERT INTO audit_log (user_id, action, table_name, record_id, new_value, ip_address)
                              VALUES (?, 'INSERT', 'parking_permit', ?, ?, ?)";
                $audit_stmt = $conn->prepare($audit_sql);
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $permit_id = $insert_stmt->insert_id;
                $audit_value = "Parking permit request: $permit_choice - $car_registration";
                $audit_stmt->bind_param("siss", $staffno, $permit_id, $audit_value, $ip_address);
                $audit_stmt->execute();
                $audit_stmt->close();

                $success = 'Parking permit request submitted successfully! Awaiting admin approval.';
            } else {
                $error = 'Failed to submit parking permit request. Please try again.';
            }

            $insert_stmt->close();
        }

        $check_stmt->close();
    }
}

// Handle cancel request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_request'])) {
    $permit_id = $_POST['permit_id'] ?? 0;

    // Verify the permit belongs to this doctor and is pending
    $verify_sql = "SELECT permit_id FROM parking_permit
                   WHERE permit_id = ? AND doctor_id = ? AND status = 'pending'";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("is", $permit_id, $staffno);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();

    if ($verify_result->num_rows > 0) {
        // Delete the pending request
        $delete_sql = "DELETE FROM parking_permit WHERE permit_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $permit_id);

        if ($delete_stmt->execute()) {
            // Log the cancellation
            $audit_sql = "INSERT INTO audit_log (user_id, action, table_name, record_id, new_value, ip_address)
                          VALUES (?, 'DELETE', 'parking_permit', ?, 'Cancelled parking permit request', ?)";
            $audit_stmt = $conn->prepare($audit_sql);
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $audit_stmt->bind_param("sis", $staffno, $permit_id, $ip_address);
            $audit_stmt->execute();
            $audit_stmt->close();

            $success = 'Parking permit request cancelled successfully.';
        } else {
            $error = 'Failed to cancel request. Please try again.';
        }

        $delete_stmt->close();
    } else {
        $error = 'Invalid request or request cannot be cancelled.';
    }

    $verify_stmt->close();
}

// Get all parking permit requests for this doctor
$permits_sql = "SELECT pp.*, d.firstname, d.lastname,
                CONCAT(d.firstname, ' ', d.lastname) as approved_by_name
                FROM parking_permit pp
                LEFT JOIN doctor d ON pp.approved_by = d.staffno
                WHERE pp.doctor_id = ?
                ORDER BY pp.request_date DESC";
$permits_stmt = $conn->prepare($permits_sql);
$permits_stmt->bind_param("s", $staffno);
$permits_stmt->execute();
$permits_result = $permits_stmt->get_result();
$permits = $permits_result->fetch_all(MYSQLI_ASSOC);
$permits_stmt->close();

// Set page variables for header
$page_title = 'Parking Permit - QMC Hospital Management System';
$extra_css = [];

// Include header
require_once 'includes/header.php';

// Include navbar
require_once 'includes/navbar.php';
?>

<div class="container">
    <div class="card">
        <h1>🅿️ Parking Permit Management</h1>
        <p>Request and manage your parking permits</p>
    </div>




    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="grid grid-2">
        <!-- Request New Permit -->
        <div class="card">
            <h2>Request New Parking Permit</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="car_registration">Car Registration Number *</label>
                    <input type="text" id="car_registration" name="car_registration"
                           placeholder="e.g., AB12 CDE" required
                           style="text-transform: uppercase;">
                    <small style="color: #666; font-size: 12px;">Enter your vehicle registration number</small>
                </div>

                <div class="form-group">
                    <label for="permit_choice">Permit Type *</label>
                    <select id="permit_choice" name="permit_choice" required>
                        <option value="">-- Select Permit Type --</option>
                        <option value="monthly">Monthly (£50.00)</option>
                        <option value="yearly">Yearly (£500.00)</option>
                    </select>
                </div>

                <div class="alert alert-info">
                    <strong>Note:</strong> Your request will be reviewed by an administrator.
                    You will be notified once it's approved or rejected.
                </div>

                <button type="submit" name="request_permit" class="btn btn-primary btn-full">
                    Submit Request
                </button>
            </form>
        </div>

        <!-- Pricing Information -->
        <div class="card">
            <h2>Pricing Information</h2>
            <table>
                <tr>
                    <th>Permit Type</th>
                    <th>Price</th>
                    <th>Duration</th>
                </tr>
                <tr>
                    <td><strong>Monthly</strong></td>
                    <td>£50.00</td>
                    <td>30 days</td>
                </tr>
                <tr>
                    <td><strong>Yearly</strong></td>
                    <td>£500.00</td>
                    <td>365 days</td>
                </tr>
            </table>

            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin-bottom: 10px; font-size: 16px;">Benefits:</h3>
                <ul style="margin-left: 20px; line-height: 1.8;">
                    <li>24/7 access to hospital parking</li>
                    <li>Reserved parking spaces</li>
                    <li>Covered parking available</li>
                    <li>Security monitored</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Parking Permit History -->
    <div class="card mt-3">
        <h2>My Parking Permit Requests</h2>

        <?php if (count($permits) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Request Date</th>
                        <th>Car Registration</th>
                        <th>Permit Type</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Activation Date</th>
                        <th>End Date</th>
                        <th>Permit Number</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($permits as $permit): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($permit['request_date'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($permit['car_registration']); ?></strong></td>
                            <td><?php echo ucfirst($permit['permit_choice']); ?></td>
                            <td>£<?php echo number_format($permit['amount'], 2); ?></td>
                            <td>
                                <?php if ($permit['status'] === 'pending'): ?>
                                    <span class="badge badge-warning">Pending</span>
                                <?php elseif ($permit['status'] === 'approved'): ?>
                                    <span class="badge badge-success">Approved</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $permit['activation_date'] ? date('d/m/Y', strtotime($permit['activation_date'])) : 'N/A'; ?>
                            </td>
                            <td>
                                <?php echo $permit['end_date'] ? date('d/m/Y', strtotime($permit['end_date'])) : 'N/A'; ?>
                            </td>
                            <td>
                                <?php echo $permit['permit_number'] ? htmlspecialchars($permit['permit_number']) : 'N/A'; ?>
                            </td>
                            <td>
                                <?php if ($permit['status'] === 'pending'): ?>
                                    <form method="POST" action="" style="display: inline;"
                                          onsubmit="return confirm('Are you sure you want to cancel this request?');">
                                        <input type="hidden" name="permit_id" value="<?php echo $permit['permit_id']; ?>">
                                        <button type="submit" name="cancel_request" class="btn btn-danger"
                                                style="padding: 5px 10px; font-size: 12px;">
                                            Cancel
                                        </button>
                                    </form>
                                <?php elseif ($permit['status'] === 'rejected'): ?>
                                    <button type="button" class="btn btn-secondary"
                                            style="padding: 5px 10px; font-size: 12px;"
                                            onclick="alert('Rejection Reason: <?php echo htmlspecialchars($permit['rejection_reason'] ?? 'No reason provided'); ?>')">
                                        View Reason
                                    </button>
                                <?php else: ?>
                                    <span style="color: #28a745;">✓ Active</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info">
                You have not submitted any parking permit requests yet.
            </div>
        <?php endif; ?>
    </div>

    <div class="text-center mt-3">
        <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>
</div>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
