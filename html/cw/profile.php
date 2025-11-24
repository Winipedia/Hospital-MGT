<?php
session_start();

// check if user logged in
if (!isset($_SESSION['staffno'])) {
    header('Location: index.php');
    exit();
}

require_once 'db.inc.php';

// setup message variables
$success = '';
$error = '';

// get current users full profile info from database
$staffno = $_SESSION['staffno'];
$sql = 'SELECT d.*, s.specialisation_name, w.wardname as ward_name
        FROM doctor d
        LEFT JOIN specialisation s ON d.specialisation_id = s.specialisation_id
        LEFT JOIN ward w ON d.ward_id = w.wardid
        WHERE d.staffno = ?';
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $staffno);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();
$stmt->close();

// handle password change form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // validate password change request
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All password fields are required';
    } elseif ($current_password !== $doctor['password']) {
        $error = 'Current password is incorrect';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters long';
    } else {
        // all validation passed, update the password
        $update_sql = 'UPDATE doctor SET password = ? WHERE staffno = ?';
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('ss', $new_password, $staffno);

        if ($update_stmt->execute()) {
            // log password change in audit trail
            $audit_sql = "INSERT INTO audit_log (user_id, action, table_name, record_id, old_value, new_value, ip_address)
                          VALUES (?, 'UPDATE', 'doctor', ?, 'password', 'Password changed', ?)";
            $audit_stmt = $conn->prepare($audit_sql);
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $audit_stmt->bind_param('sss', $staffno, $staffno, $ip_address);
            $audit_stmt->execute();
            $audit_stmt->close();

            $success = 'Password changed successfully!';

            // update doctor array so current password check works if they change again
            $doctor['password'] = $new_password;
        } else {
            $error = 'Failed to update password. Please try again.';
        }

        $update_stmt->close();
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');

    // Validation
    if (empty($firstname) || empty($lastname)) {
        $error = 'First name and last name are required';
    } else {
        // Update profile
        $update_sql = 'UPDATE doctor SET firstname = ?, lastname = ? WHERE staffno = ?';
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('sss', $firstname, $lastname, $staffno);

        if ($update_stmt->execute()) {
            // log profile update in audit trail
            $audit_sql = "INSERT INTO audit_log (user_id, action, table_name, record_id, new_value, ip_address)
                          VALUES (?, 'UPDATE', 'doctor', ?, 'Profile updated', ?)";
            $audit_stmt = $conn->prepare($audit_sql);
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $audit_stmt->bind_param('sss', $staffno, $staffno, $ip_address);
            $audit_stmt->execute();
            $audit_stmt->close();

            $success = 'Profile updated successfully!';

            // Update session variables
            $_SESSION['firstname'] = $firstname;
            $_SESSION['lastname'] = $lastname;

            // Refresh doctor data
            $doctor['firstname'] = $firstname;
            $doctor['lastname'] = $lastname;
        } else {
            $error = 'Failed to update profile. Please try again.';
        }

        $update_stmt->close();
    }
}

// setup page title
$page_title = 'My Profile - QMC Hospital Management System';
$extra_css = [];

// load header
require_once 'includes/header.php';

// load navbar
require_once 'includes/navbar.php';
?>

<div class="container">
    <div class="card">
        <h1>👤 My Profile</h1>
        <p>Manage your account information and settings</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="grid grid-2">
        <!-- Profile Information -->
        <div class="card">
            <h2>Profile Information</h2>


            <form method="POST" action="">
                <div class="form-group">
                    <label for="firstname">First Name *</label>
                    <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($doctor['firstname']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="lastname">Last Name *</label>
                    <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($doctor['lastname']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" value="<?php echo htmlspecialchars($doctor['username']); ?>" disabled>
                    <small style="color: #666; font-size: 12px;">Username cannot be changed</small>
                </div>

                <div class="form-group">
                    <label for="staffno">Staff Number</label>
                    <input type="text" id="staffno" value="<?php echo htmlspecialchars($doctor['staffno']); ?>" disabled>
                    <small style="color: #666; font-size: 12px;">Staff number cannot be changed</small>
                </div>

                <div class="form-group">
                    <label for="specialisation">Specialisation</label>
                    <input type="text" id="specialisation" value="<?php echo htmlspecialchars($doctor['specialisation_name'] ?? 'N/A'); ?>" disabled>
                    <small style="color: #666; font-size: 12px;">Contact admin to change specialisation</small>
                </div>

                <div class="form-group">
                    <label for="ward">Ward</label>
                    <input type="text" id="ward" value="<?php echo htmlspecialchars($doctor['ward_name'] ?? 'N/A'); ?>" disabled>
                    <small style="color: #666; font-size: 12px;">Contact admin to change ward assignment</small>
                </div>

                <button type="submit" name="update_profile" class="btn btn-primary btn-full">Update Profile</button>
            </form>
        </div>

        <!-- Change Password -->
        <div class="card">
            <h2>Change Password</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="current_password">Current Password *</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password *</label>
                    <input type="password" id="new_password" name="new_password" required minlength="6">
                    <small style="color: #666; font-size: 12px;">Minimum 6 characters</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>

                <button type="submit" name="change_password" class="btn btn-primary btn-full">Change Password</button>
            </form>
        </div>
    </div>

    <!-- Account Information -->
    <div class="card mt-3">
        <h2>Account Information</h2>
        <table>
            <tr>
                <th>Account Type</th>
                <td>
                    <?php echo $doctor['is_admin'] ? 'Administrator' : 'Doctor'; ?>
                    <?php if ($doctor['is_admin']): ?>
                        <span class="badge badge-admin">ADMIN</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Staff Number</th>
                <td><?php echo htmlspecialchars($doctor['staffno']); ?></td>
            </tr>
            <tr>
                <th>Username</th>
                <td><?php echo htmlspecialchars($doctor['username']); ?></td>
            </tr>
            <tr>
                <th>Specialisation</th>
                <td><?php echo htmlspecialchars($doctor['specialisation_name'] ?? 'N/A'); ?></td>
            </tr>
            <tr>
                <th>Ward Assignment</th>
                <td><?php echo htmlspecialchars($doctor['ward_name'] ?? 'N/A'); ?></td>
            </tr>
            <tr>
                <th>Qualification</th>
                <td><?php echo htmlspecialchars($doctor['qualification'] ?? 'N/A'); ?></td>
            </tr>
            <tr>
                <th>Consultant Status</th>
                <td><?php echo $doctor['consultantstatus'] ? 'Yes' : 'No'; ?></td>
            </tr>
        </table>
    </div>

    <div class="text-center mt-3">
        <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>
</div>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
