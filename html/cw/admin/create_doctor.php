<?php
session_start();

// check if user logged in
if (!isset($_SESSION['staffno'])) {
    header('Location: ../index.php');
    exit();
}

// make sure user is admin, only admins can create doctors
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../dashboard.php');
    exit();
}

require_once '../db.inc.php';

// setup variables
$staffno = $_SESSION['staffno'];
$success = '';
$error = '';

// handle form submission for creating new doctor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_doctor'])) {
    // grab all the form fields
    $new_staffno = strtoupper(trim($_POST['staffno'] ?? ''));
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $specialisation_id = intval($_POST['specialisation_id'] ?? 0);
    $qualification = trim($_POST['qualification'] ?? '');
    $pay = intval($_POST['pay'] ?? 0);
    $gender_id = intval($_POST['gender_id'] ?? 0);
    $consultantstatus = isset($_POST['consultantstatus']) ? 1 : 0;
    $ward_id = !empty($_POST['ward_id']) ? intval($_POST['ward_id']) : null;
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    // address fields are optional
    $street = trim($_POST['street'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postcode = trim($_POST['postcode'] ?? '');

    // basic validation - need staff number, name, and pay
    if (empty($new_staffno) || empty($firstname) || $pay <= 0) {
        $error = 'Staff Number, First Name, and Pay are required';
    } else {
        // check if staff number already in use
        $check_staff_sql = 'SELECT staffno FROM doctor WHERE staffno = ?';
        $check_staff_stmt = $conn->prepare($check_staff_sql);
        $check_staff_stmt->bind_param('s', $new_staffno);
        $check_staff_stmt->execute();
        $check_staff_result = $check_staff_stmt->get_result();

        if ($check_staff_result->num_rows > 0) {
            $error = "Staff number '$new_staffno' already exists";
        } else {
            // also check if username is taken if they provided one
            if (!empty($username)) {
                $check_user_sql = 'SELECT username FROM doctor WHERE username = ?';
                $check_user_stmt = $conn->prepare($check_user_sql);
                $check_user_stmt->bind_param('s', $username);
                $check_user_stmt->execute();
                $check_user_result = $check_user_stmt->get_result();

                if ($check_user_result->num_rows > 0) {
                    $error = "Username '$username' already exists";
                }
                $check_user_stmt->close();
            }

            // if no errors so far, proceed with creating doctor
            if (empty($error)) {
                // Create address if provided
                $address_id = null;

                if (!empty($street) || !empty($city) || !empty($postcode)) {
                    $address_sql = 'INSERT INTO address (street, city, postcode) VALUES (?, ?, ?)';
                    $address_stmt = $conn->prepare($address_sql);
                    $address_stmt->bind_param('sss', $street, $city, $postcode);

                    if ($address_stmt->execute()) {
                        $address_id = $conn->insert_id;
                    }
                    $address_stmt->close();
                }

                // Insert new doctor
                $insert_sql = 'INSERT INTO doctor (staffno, username, password, firstname, lastname, specialisation_id, 
                              qualification, pay, gender_id, consultantstatus, address_id, ward_id, is_admin) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
                $insert_stmt = $conn->prepare($insert_sql);

                // Handle nullable fields
                $username_val = !empty($username) ? $username : null;
                $password_val = !empty($password) ? $password : null;
                $lastname_val = !empty($lastname) ? $lastname : null;
                $specialisation_val = $specialisation_id > 0 ? $specialisation_id : null;
                $qualification_val = !empty($qualification) ? $qualification : null;
                $gender_val = $gender_id > 0 ? $gender_id : null;

                $insert_stmt->bind_param('sssssisiiiiii',
                    $new_staffno, $username_val, $password_val, $firstname, $lastname_val,
                    $specialisation_val, $qualification_val, $pay, $gender_val,
                    $consultantstatus, $address_id, $ward_id, $is_admin);

                if ($insert_stmt->execute()) {
                    $success = "Doctor account created successfully! Staff No: $new_staffno";

                    // Audit log
                    $audit_sql = "INSERT INTO audit_log (user_id, action, table_name, record_id, new_value, ip_address) 
                                  VALUES (?, 'INSERT', 'doctor', ?, ?, ?)";
                    $audit_stmt = $conn->prepare($audit_sql);
                    $ip_address = $_SERVER['REMOTE_ADDR'];
                    $audit_value = "Created doctor account: $new_staffno - $firstname $lastname_val";
                    $audit_stmt->bind_param('siss', $staffno, $new_staffno, $audit_value, $ip_address);
                    $audit_stmt->execute();
                    $audit_stmt->close();

                    // Clear form
                    $_POST = [];
                } else {
                    $error = 'Failed to create doctor account: ' . $conn->error;
                }
                $insert_stmt->close();
            }
        }
        $check_staff_stmt->close();
    }
}

// Fetch specialisations
$specialisations_sql = 'SELECT specialisation_id, specialisation_name FROM specialisation ORDER BY specialisation_name';
$specialisations_result = $conn->query($specialisations_sql);
$specialisations = [];

while ($row = $specialisations_result->fetch_assoc()) {
    $specialisations[] = $row;
}

// Fetch genders
$genders_sql = 'SELECT gender_id, gender_name FROM gender ORDER BY gender_id';
$genders_result = $conn->query($genders_sql);
$genders = [];

while ($row = $genders_result->fetch_assoc()) {
    $genders[] = $row;
}

// Fetch wards
$wards_sql = 'SELECT wardid, wardname FROM ward ORDER BY wardname';
$wards_result = $conn->query($wards_sql);
$wards = [];

while ($row = $wards_result->fetch_assoc()) {
    $wards[] = $row;
}

// Fetch all doctors for display
$doctors_sql = 'SELECT d.staffno, d.username, d.firstname, d.lastname, d.pay, d.consultantstatus, d.is_admin,
                       s.specialisation_name, g.gender_name, w.wardname
                FROM doctor d
                LEFT JOIN specialisation s ON d.specialisation_id = s.specialisation_id
                LEFT JOIN gender g ON d.gender_id = g.gender_id
                LEFT JOIN ward w ON d.ward_id = w.wardid
                ORDER BY d.staffno';
$doctors_result = $conn->query($doctors_sql);
$doctors = [];

while ($row = $doctors_result->fetch_assoc()) {
    $doctors[] = $row;
}

// setup page title
$page_title = 'Create Doctor Account - QMC Hospital Management System';
$extra_css = [];
$css_path_prefix = '../'; // for admin subdirectory

// load templates
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>👨‍⚕️ Create New Doctor Account</h1>
        <p>Add a new doctor to the QMC Hospital Management System</p>
    </div>

    <?php if ($success) { ?>
        <div class="alert alert-success">
            <strong>✓ Success!</strong> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php } ?>

    <?php if ($error) { ?>
        <div class="alert alert-error">
            <strong>✗ Error!</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php } ?>

    <!-- Create Doctor Form -->
    <div class="card">
        <div class="card-header">
            <h2>Doctor Information</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-row">
                    <!-- Staff Number -->
                    <div class="form-group" style="flex: 1;">
                        <label for="staffno">Staff Number *</label>
                        <input type="text" id="staffno" name="staffno"
                               placeholder="e.g., QM999"
                               required
                               style="text-transform: uppercase;"
                               value="<?php echo htmlspecialchars($_POST['staffno'] ?? ''); ?>">
                        <small>Unique identifier for the doctor</small>
                    </div>

                    <!-- Username -->
                    <div class="form-group" style="flex: 1;">
                        <label for="username">Username (Optional)</label>
                        <input type="text" id="username" name="username"
                               placeholder="e.g., jsmith"
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        <small>For login access</small>
                    </div>

                    <!-- Password -->
                    <div class="form-group" style="flex: 1;">
                        <label for="password">Password (Optional)</label>
                        <input type="text" id="password" name="password"
                               placeholder="Enter password"
                               value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>">
                        <small>Required if username is provided</small>
                    </div>
                </div>

                <div class="form-row">
                    <!-- First Name -->
                    <div class="form-group" style="flex: 1;">
                        <label for="firstname">First Name *</label>
                        <input type="text" id="firstname" name="firstname"
                               placeholder="Enter first name"
                               required
                               value="<?php echo htmlspecialchars($_POST['firstname'] ?? ''); ?>">
                    </div>

                    <!-- Last Name -->
                    <div class="form-group" style="flex: 1;">
                        <label for="lastname">Last Name (Optional)</label>
                        <input type="text" id="lastname" name="lastname"
                               placeholder="Enter last name"
                               value="<?php echo htmlspecialchars($_POST['lastname'] ?? ''); ?>">
                    </div>

                    <!-- Gender -->
                    <div class="form-group" style="flex: 1;">
                        <label for="gender_id">Gender (Optional)</label>
                        <select id="gender_id" name="gender_id">
                            <option value="">-- Select Gender --</option>
                            <?php foreach ($genders as $gender) { ?>
                                <option value="<?php echo $gender['gender_id']; ?>"
                                    <?php echo (isset($_POST['gender_id']) && $_POST['gender_id'] == $gender['gender_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($gender['gender_name']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <!-- Specialisation -->
                    <div class="form-group" style="flex: 1;">
                        <label for="specialisation_id">Specialisation (Optional)</label>
                        <select id="specialisation_id" name="specialisation_id">
                            <option value="">-- Select Specialisation --</option>
                            <?php foreach ($specialisations as $spec) { ?>
                                <option value="<?php echo $spec['specialisation_id']; ?>"
                                    <?php echo (isset($_POST['specialisation_id']) && $_POST['specialisation_id'] == $spec['specialisation_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($spec['specialisation_name']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <!-- Qualification -->
                    <div class="form-group" style="flex: 1;">
                        <label for="qualification">Qualification (Optional)</label>
                        <input type="text" id="qualification" name="qualification"
                               placeholder="e.g., CCT, MBBS"
                               value="<?php echo htmlspecialchars($_POST['qualification'] ?? ''); ?>">
                    </div>

                    <!-- Ward -->
                    <div class="form-group" style="flex: 1;">
                        <label for="ward_id">Ward (Optional)</label>
                        <select id="ward_id" name="ward_id">
                            <option value="">-- Select Ward --</option>
                            <?php foreach ($wards as $ward) { ?>
                                <option value="<?php echo $ward['wardid']; ?>"
                                    <?php echo (isset($_POST['ward_id']) && $_POST['ward_id'] == $ward['wardid']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ward['wardname']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <!-- Pay -->
                    <div class="form-group" style="flex: 1;">
                        <label for="pay">Annual Pay (£) *</label>
                        <input type="number" id="pay" name="pay"
                               placeholder="e.g., 50000"
                               min="0"
                               required
                               value="<?php echo htmlspecialchars($_POST['pay'] ?? ''); ?>">
                    </div>

                    <!-- Consultant Status -->
                    <div class="form-group" style="flex: 1;">
                        <label style="display: block; margin-bottom: 5px;">Status</label>
                        <label style="display: inline-flex; align-items: center; cursor: pointer;">
                            <input type="checkbox" id="consultantstatus" name="consultantstatus"
                                   style="margin-right: 8px;"
                                   <?php echo (isset($_POST['consultantstatus'])) ? 'checked' : ''; ?>>
                            <span>Consultant Status</span>
                        </label>
                    </div>

                    <!-- Admin Status -->
                    <div class="form-group" style="flex: 1;">
                        <label style="display: block; margin-bottom: 5px;">&nbsp;</label>
                        <label style="display: inline-flex; align-items: center; cursor: pointer;">
                            <input type="checkbox" id="is_admin" name="is_admin"
                                   style="margin-right: 8px;"
                                   <?php echo (isset($_POST['is_admin'])) ? 'checked' : ''; ?>>
                            <span>Administrator Access</span>
                        </label>
                    </div>
                </div>

                <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">

                <h3 style="margin-bottom: 15px; color: #333;">Address (Optional)</h3>

                <div class="form-row">
                    <!-- Street -->
                    <div class="form-group" style="flex: 2;">
                        <label for="street">Street</label>
                        <input type="text" id="street" name="street"
                               placeholder="Enter street address"
                               value="<?php echo htmlspecialchars($_POST['street'] ?? ''); ?>">
                    </div>

                    <!-- City -->
                    <div class="form-group" style="flex: 1;">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city"
                               placeholder="Enter city"
                               value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                    </div>

                    <!-- Postcode -->
                    <div class="form-group" style="flex: 1;">
                        <label for="postcode">Postcode</label>
                        <input type="text" id="postcode" name="postcode"
                               placeholder="e.g., NG7 2UH"
                               value="<?php echo htmlspecialchars($_POST['postcode'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="create_doctor" class="btn btn-primary">
                        ✓ Create Doctor Account
                    </button>
                    <a href="../dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Existing Doctors List -->
    <div class="card mt-3">
        <div class="card-header">
            <h2>📋 All Doctors (<?php echo count($doctors); ?>)</h2>
        </div>
        <div class="card-body">
            <?php if (count($doctors) > 0) { ?>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Staff No</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Specialisation</th>
                                <th>Ward</th>
                                <th>Gender</th>
                                <th>Pay</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($doctors as $doctor) { ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($doctor['staffno']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($doctor['firstname'] . ' ' . ($doctor['lastname'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars($doctor['username'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($doctor['specialisation_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($doctor['wardname'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($doctor['gender_name'] ?? 'N/A'); ?></td>
                                    <td>£<?php echo number_format($doctor['pay'], 0); ?></td>
                                    <td>
                                        <?php if ($doctor['is_admin']) { ?>
                                            <span class="badge badge-admin">ADMIN</span>
                                        <?php } ?>
                                        <?php if ($doctor['consultantstatus']) { ?>
                                            <span class="badge badge-success">Consultant</span>
                                        <?php } ?>
                                        <?php if (!$doctor['is_admin'] && !$doctor['consultantstatus']) { ?>
                                            <span class="badge badge-info">Doctor</span>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } else { ?>
                <div class="info-message">
                    <p>ℹ️ No doctors found in the system.</p>
                </div>
            <?php } ?>
        </div>
    </div>

    <div class="text-center mt-3">
        <a href="../dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>
</div>

<style>
.form-row {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-start;
    margin-top: 20px;
}

@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
    }
}
</style>

<?php
$conn->close();
require_once '../includes/footer.php';
?>

