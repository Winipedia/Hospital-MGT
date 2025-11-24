<?php
session_start();

// make sure user is actually logged in before they can do anything
if (!isset($_SESSION['staffno'])) {
    header('Location: index.php');
    exit();
}

require_once 'db.inc.php';

// setup message variables for feedback
$success = '';
$error = '';
$step = 1; // Step 1: Choose action, Step 2: Add patient, Step 3: Prescribe test

// so this handles when someone wants to create a new test type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_test'])) {
    $testname = trim($_POST['testname'] ?? '');

    if (empty($testname)) {
        $error = 'Test name is required';
    } else {
        // gotta check if test already exists, dont want duplicates
        $check_sql = "SELECT testid FROM test WHERE testname = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $testname);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = 'A test with this name already exists';
        } else {
            // ok test doesnt exist, lets add it to database
            $insert_sql = "INSERT INTO test (testname) VALUES (?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("s", $testname);

            if ($insert_stmt->execute()) {
                $new_test_id = $insert_stmt->insert_id;
                $success = "Test '$testname' created successfully! (Test ID: $new_test_id)";

                // log this action for audit trail
                $audit_sql = "INSERT INTO audit_log (user_id, action, table_name, record_id, new_value, ip_address)
                              VALUES (?, 'INSERT', 'test', ?, ?, ?)";
                $audit_stmt = $conn->prepare($audit_sql);
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $audit_value = "Created test: $testname";
                $audit_stmt->bind_param("ssss", $_SESSION['staffno'], $new_test_id, $audit_value, $ip_address);
                $audit_stmt->execute();
                $audit_stmt->close();
            } else {
                $error = 'Failed to create test';
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}

// this part handles adding a new patient to the system
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_patient'])) {
    // grab all the form data and clean it up
    $nhs_no = strtoupper(trim($_POST['nhs_no'] ?? ''));
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $age = intval($_POST['age'] ?? 0);
    $gender_id = intval($_POST['gender_id'] ?? 0);
    $emergencyphone = trim($_POST['emergencyphone'] ?? '');
    $street = trim($_POST['street'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postcode = trim($_POST['postcode'] ?? '');

    // basic validation - cant add patient without these fields
    if (empty($nhs_no) || empty($firstname) || empty($phone) || $age <= 0) {
        $error = 'NHS Number, First Name, Phone, and Age are required';
    } else {
        // check if patient already in system, NHS number should be unique
        $check_sql = "SELECT NHSno FROM patient WHERE NHSno = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $nhs_no);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "Patient with NHS Number $nhs_no already exists in the system";
        } else {
            // need to insert address first cuz patient table references it
            $address_sql = "INSERT INTO address (street, city, postcode) VALUES (?, ?, ?)";
            $address_stmt = $conn->prepare($address_sql);
            $address_stmt->bind_param("sss", $street, $city, $postcode);
            $address_stmt->execute();
            $address_id = $address_stmt->insert_id;
            $address_stmt->close();

            // now insert the patient record with the address id
            $patient_sql = "INSERT INTO patient (NHSno, firstname, lastname, phone, address_id, age, gender_id, emergencyphone)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $patient_stmt = $conn->prepare($patient_sql);
            $gender_id_null = $gender_id > 0 ? $gender_id : null;
            $emergency_null = !empty($emergencyphone) ? $emergencyphone : null;
            $patient_stmt->bind_param("ssssiiss", $nhs_no, $firstname, $lastname, $phone, $address_id, $age, $gender_id_null, $emergency_null);

            if ($patient_stmt->execute()) {
                $success = "Patient '$firstname $lastname' (NHS: $nhs_no) added successfully!";

                // log the patient creation for audit purposes
                $audit_sql = "INSERT INTO audit_log (user_id, action, table_name, record_id, new_value, ip_address)
                              VALUES (?, 'INSERT', 'patient', ?, ?, ?)";
                $audit_stmt = $conn->prepare($audit_sql);
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $audit_value = "Added patient: $firstname $lastname";
                $audit_stmt->bind_param("ssss", $_SESSION['staffno'], $nhs_no, $audit_value, $ip_address);
                $audit_stmt->execute();
                $audit_stmt->close();
            } else {
                $error = 'Failed to add patient';
            }
            $patient_stmt->close();
        }
        $check_stmt->close();
    }
}

// this handles prescribing a test to an existing patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prescribe_test'])) {
    $patient_nhs = trim($_POST['patient_nhs'] ?? '');
    $test_id = intval($_POST['test_id'] ?? 0);
    $test_date = $_POST['test_date'] ?? date('Y-m-d');

    // make sure we got both patient and test selected
    if (empty($patient_nhs) || $test_id <= 0) {
        $error = 'Please select both a patient and a test';
    } else {
        // verify patient actually exists before prescribing anything
        $check_patient_sql = "SELECT NHSno, firstname, lastname FROM patient WHERE NHSno = ?";
        $check_patient_stmt = $conn->prepare($check_patient_sql);
        $check_patient_stmt->bind_param("s", $patient_nhs);
        $check_patient_stmt->execute();
        $patient_result = $check_patient_stmt->get_result();

        if ($patient_result->num_rows === 0) {
            $error = 'Patient not found';
        } else {
            $patient_data = $patient_result->fetch_assoc();



            // ok patient exists, lets prescribe the test
            $prescribe_sql = "INSERT INTO patient_test (pid, testid, date, doctorid) VALUES (?, ?, ?, ?)";
            $prescribe_stmt = $conn->prepare($prescribe_sql);
            $prescribe_stmt->bind_param("siss", $patient_nhs, $test_id, $test_date, $_SESSION['staffno']);

            if ($prescribe_stmt->execute()) {
                // need to get test name for the success mesage
                $test_name_sql = "SELECT testname FROM test WHERE testid = ?";
                $test_name_stmt = $conn->prepare($test_name_sql);
                $test_name_stmt->bind_param("i", $test_id);
                $test_name_stmt->execute();
                $test_name_result = $test_name_stmt->get_result();
                $test_name = $test_name_result->fetch_assoc()['testname'];
                $test_name_stmt->close();

                $success = "Test '$test_name' prescribed to {$patient_data['firstname']} {$patient_data['lastname']} (NHS: $patient_nhs) for $test_date";

                // log this prescription in audit trail
                $audit_sql = "INSERT INTO audit_log (user_id, action, table_name, record_id, new_value, ip_address)
                              VALUES (?, 'INSERT', 'patient_test', ?, ?, ?)";
                $audit_stmt = $conn->prepare($audit_sql);
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $audit_value = "Prescribed test $test_id to patient $patient_nhs";
                $audit_stmt->bind_param("ssss", $_SESSION['staffno'], $patient_nhs, $audit_value, $ip_address);
                $audit_stmt->execute();
                $audit_stmt->close();
            } else {
                $error = 'Failed to prescribe test';
            }
            $prescribe_stmt->close();
        }
        $check_patient_stmt->close();
    }
}

// fetch all tests from database for the dropdown menu
$tests_sql = "SELECT testid, testname FROM test ORDER BY testname";
$tests_result = $conn->query($tests_sql);
$all_tests = $tests_result->fetch_all(MYSQLI_ASSOC);

// get gender options for patient form
$genders_sql = "SELECT gender_id, gender_name FROM gender ORDER BY gender_name";
$genders_result = $conn->query($genders_sql);
$all_genders = $genders_result->fetch_all(MYSQLI_ASSOC);

// setup page title
$page_title = 'Add Test & Prescribe - QMC Hospital Management System';
$extra_css = [];

// load header
require_once 'includes/header.php';

// load navbar
require_once 'includes/navbar.php';
?>

<div class="container">
    <div class="card">
        <h1>🧪 Add Test & Prescribe</h1>
        <p>Create new tests and prescribe them to patients</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="grid grid-2">
        <!-- Create New Test -->
        <div class="card">
            <h2>1️⃣ Create New Test</h2>
            <p style="color: #666; font-size: 14px; margin-bottom: 20px;">
                Add a new test type to the system
            </p>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="testname">Test Name *</label>
                    <input type="text" id="testname" name="testname"
                           placeholder="e.g., MRI Scan, X-Ray, Blood Test"
                           required>
                </div>
                <button type="submit" name="create_test" class="btn btn-primary btn-full">
                    Create Test
                </button>
            </form>

            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h3 style="margin-bottom: 10px; font-size: 14px; font-weight: bold;">Existing Tests:</h3>
                <ul style="margin-left: 20px; line-height: 1.8; font-size: 13px;">
                    <?php foreach ($all_tests as $test): ?>
                        <li><?php echo htmlspecialchars($test['testname']); ?> (ID: <?php echo $test['testid']; ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Add New Patient -->
        <div class="card">
            <h2>2️⃣ Add New Patient</h2>
            <p style="color: #666; font-size: 14px; margin-bottom: 20px;">
                Add a patient if they're not in the system
            </p>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="nhs_no">NHS Number *</label>
                    <input type="text" id="nhs_no" name="nhs_no"
                           placeholder="e.g., W20616"
                           style="text-transform: uppercase;"
                           required>
                </div>

                <div class="grid grid-2" style="gap: 10px;">
                    <div class="form-group">
                        <label for="firstname">First Name *</label>
                        <input type="text" id="firstname" name="firstname" required>
                    </div>
                    <div class="form-group">
                        <label for="lastname">Last Name</label>
                        <input type="text" id="lastname" name="lastname">
                    </div>
                </div>

                <div class="grid grid-2" style="gap: 10px;">
                    <div class="form-group">
                        <label for="age">Age *</label>
                        <input type="number" id="age" name="age" min="0" max="150" required>
                    </div>
                    <div class="form-group">
                        <label for="gender_id">Gender</label>
                        <select id="gender_id" name="gender_id">
                            <option value="0">-- Select Gender --</option>
                            <?php foreach ($all_genders as $gender): ?>
                                <option value="<?php echo $gender['gender_id']; ?>">
                                    <?php echo htmlspecialchars($gender['gender_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>


                <div class="grid grid-2" style="gap: 10px;">
                    <div class="form-group">
                        <label for="phone">Phone *</label>
                        <input type="tel" id="phone" name="phone"
                               placeholder="e.g., 07656999653"
                               required>
                    </div>
                    <div class="form-group">
                        <label for="emergencyphone">Emergency Phone</label>
                        <input type="tel" id="emergencyphone" name="emergencyphone"
                               placeholder="Optional">
                    </div>
                </div>

                <div class="form-group">
                    <label for="street">Street Address</label>
                    <input type="text" id="street" name="street"
                           placeholder="e.g., 123 Main Street">
                </div>

                <div class="grid grid-2" style="gap: 10px;">
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city"
                               placeholder="e.g., Nottingham">
                    </div>
                    <div class="form-group">
                        <label for="postcode">Postcode</label>
                        <input type="text" id="postcode" name="postcode"
                               placeholder="e.g., NG1 1AA">
                    </div>
                </div>

                <button type="submit" name="add_patient" class="btn btn-primary btn-full">
                    Add Patient
                </button>
            </form>
        </div>
    </div>

    <!-- Prescribe Test to Patient -->
    <div class="card">
        <h2>3️⃣ Prescribe Test to Patient</h2>
        <p style="color: #666; font-size: 14px; margin-bottom: 20px;">
            Assign a test to an existing patient
        </p>

        <form method="POST" action="">
            <div class="grid grid-2" style="gap: 15px;">
                <div class="form-group">
                    <label for="patient_nhs">Patient NHS Number *</label>
                    <input type="text" id="patient_nhs" name="patient_nhs"
                           placeholder="Enter NHS number (e.g., W20616)"
                           style="text-transform: uppercase;"
                           required>
                    <small style="color: #666; font-size: 12px;">
                        Or <a href="patient_search.php" target="_blank" style="color: #007bff;">search for patient</a>
                    </small>
                </div>

                <div class="form-group">
                    <label for="test_id">Select Test *</label>
                    <select id="test_id" name="test_id" required>
                        <option value="">-- Select Test --</option>
                        <?php foreach ($all_tests as $test): ?>
                            <option value="<?php echo $test['testid']; ?>">
                                <?php echo htmlspecialchars($test['testname']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="test_date">Test Date *</label>
                <input type="date" id="test_date" name="test_date"
                       value="<?php echo date('Y-m-d'); ?>"
                       required>
            </div>

            <button type="submit" name="prescribe_test" class="btn btn-primary btn-full">
                Prescribe Test
            </button>
        </form>

        <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px;">
            <strong>💡 Tip:</strong> Make sure the patient exists in the system before prescribing a test.
            If they don't exist, add them using the form above first.
        </div>
    </div>

    <div class="text-center mt-3">
        <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>
</div>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
