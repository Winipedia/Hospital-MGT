<?php
session_start();

// check if user logged in
if (!isset($_SESSION['staffno'])) {
    header('Location: index.php');
    exit();
}

require_once 'db.inc.php';

// setup variables for patient data
$patient = null;
$ward_admissions = [];
$tests = [];
$error = '';

// get NHS number from url parameter
$nhs_no = $_GET['nhs'] ?? '';

// if no NHS number provided, send back to search page
if (empty($nhs_no)) {
    header('Location: patient_search.php');
    exit();
}

// fetch patient info from database
$patient_sql = "SELECT p.*, g.gender_name, a.street, a.city, a.postcode
                FROM patient p
                LEFT JOIN gender g ON p.gender_id = g.gender_id
                LEFT JOIN address a ON p.address_id = a.address_id
                WHERE p.NHSno = ?";

$patient_stmt = $conn->prepare($patient_sql);
$patient_stmt->bind_param("s", $nhs_no);
$patient_stmt->execute();
$patient_result = $patient_stmt->get_result();

// check if patient exists
if ($patient_result->num_rows === 0) {
    $error = 'Patient not found';
} else {
    $patient = $patient_result->fetch_assoc();

    // get all ward admissions for this patient
    $ward_sql = "SELECT w.*, wa.wardname, wa.phone as ward_phone,
                 d.firstname as consultant_firstname, d.lastname as consultant_lastname,
                 DATEDIFF(CURDATE(), w.date) as days_admitted
                 FROM wardpatientaddmission w
                 JOIN ward wa ON w.wardid = wa.wardid
                 LEFT JOIN doctor d ON w.consultantid = d.staffno
                 WHERE w.pid = ?
                 ORDER BY w.date DESC";

    $ward_stmt = $conn->prepare($ward_sql);
    $ward_stmt->bind_param("s", $nhs_no);
    $ward_stmt->execute();
    $ward_result = $ward_stmt->get_result();
    $ward_admissions = $ward_result->fetch_all(MYSQLI_ASSOC);
    $ward_stmt->close();

    // get all tests prescribed to this patient
    $test_sql = "SELECT pt.*, t.testname,
                 d.firstname as doctor_firstname, d.lastname as doctor_lastname
                 FROM patient_test pt
                 JOIN test t ON pt.testid = t.testid
                 LEFT JOIN doctor d ON pt.doctorid = d.staffno
                 WHERE pt.pid = ?
                 ORDER BY pt.date DESC";

    $test_stmt = $conn->prepare($test_sql);
    $test_stmt->bind_param("s", $nhs_no);
    $test_stmt->execute();
    $test_result = $test_stmt->get_result();
    $tests = $test_result->fetch_all(MYSQLI_ASSOC);
    $test_stmt->close();

    // log the view in audit trail
    $audit_sql = "INSERT INTO audit_log (user_id, action, table_name, record_id, new_value, ip_address)
                  VALUES (?, 'SELECT', 'patient', ?, ?, ?)";
    $audit_stmt = $conn->prepare($audit_sql);
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $audit_value = "Viewed patient details";
    $audit_stmt->bind_param("ssss", $_SESSION['staffno'], $nhs_no, $audit_value, $ip_address);
    $audit_stmt->execute();
    $audit_stmt->close();
}

$patient_stmt->close();

// setup page title
$page_title = 'Patient Information - QMC Hospital Management System';
$extra_css = [];

// load header
require_once 'includes/header.php';

// load navbar
require_once 'includes/navbar.php';
?>

<div class="container">
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <div class="text-center mt-3">
            <a href="patient_search.php" class="btn btn-secondary">← Back to Patient Search</a>
        </div>
    <?php else: ?>
        <div class="card">
            <h1>👤 Patient Information</h1>
            <p>Detailed information for <?php echo htmlspecialchars($patient['firstname'] . ' ' . $patient['lastname']); ?></p>
        </div>

        <!-- Patient Information -->
        <div class="card">
            <h2>Personal Information</h2>
            <div class="grid grid-2">
                <div>
                    <table>
                        <tr>
                            <th>NHS Number</th>
                            <td><strong><?php echo htmlspecialchars($patient['NHSno']); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Full Name</th>
                            <td><?php echo htmlspecialchars($patient['firstname'] . ' ' . $patient['lastname']); ?></td>
                        </tr>
                        <tr>
                            <th>Age</th>
                            <td><?php echo htmlspecialchars($patient['age']); ?> years</td>
                        </tr>
                        <tr>
                            <th>Gender</th>
                            <td><?php echo htmlspecialchars($patient['gender_name'] ?? 'N/A'); ?></td>
                        </tr>
                    </table>
                </div>
                <div>
                    <table>
                        <tr>
                            <th>Phone</th>
                            <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                        </tr>


                        <tr>
                            <th>Emergency Phone</th>
                            <td><?php echo htmlspecialchars($patient['emergencyphone'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td>
                                <?php
                                if ($patient['street']) {
                                    echo htmlspecialchars($patient['street']) . '<br>';
                                    echo htmlspecialchars($patient['city']) . '<br>';
                                    echo htmlspecialchars($patient['postcode']);
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Ward Admissions -->
        <div class="card">
            <h2>Ward Admission History</h2>
            <?php if (count($ward_admissions) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Ward</th>
                            <th>Admission Date</th>
                            <th>Admission Time</th>
                            <th>Duration (Days)</th>
                            <th>Status</th>
                            <th>Consultant</th>
                            <th>Ward Phone</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ward_admissions as $admission): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($admission['wardname']); ?></strong></td>
                                <td><?php echo date('d/m/Y', strtotime($admission['date'])); ?></td>
                                <td><?php echo $admission['time'] ? date('H:i', strtotime($admission['time'])) : 'N/A'; ?></td>
                                <td>
                                    <?php
                                    if ($admission['status'] === 'admitted') {
                                        echo $admission['days_admitted'] . ' days (ongoing)';
                                    } else {
                                        echo 'Discharged';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($admission['status'] === 'admitted'): ?>
                                        <span class="badge badge-success">Admitted</span>
                                    <?php else: ?>
                                        <span class="badge badge-info">Discharged</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    Dr. <?php echo htmlspecialchars($admission['consultant_firstname'] . ' ' . $admission['consultant_lastname']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($admission['ward_phone']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">
                    <strong>No ward admissions found.</strong><br>
                    This patient has not been admitted to any ward.
                </div>
            <?php endif; ?>
        </div>

        <!-- Tests Performed -->
        <div class="card">
            <h2>Tests Performed</h2>
            <?php if (count($tests) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Test Name</th>
                            <th>Test Date</th>
                            <th>Prescribed By</th>
                            <th>Report</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tests as $test): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($test['testname']); ?></strong></td>
                                <td><?php echo date('d/m/Y', strtotime($test['date'])); ?></td>
                                <td>
                                    <?php
                                    if ($test['doctor_firstname']) {
                                        echo 'Dr. ' . htmlspecialchars($test['doctor_firstname'] . ' ' . $test['doctor_lastname']);
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($test['report'] ?? 'Pending'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">
                    <strong>No tests found.</strong><br>
                    No tests have been performed for this patient.
                </div>
            <?php endif; ?>
        </div>

        <!-- Summary Statistics -->
        <div class="card">
            <h2>Summary</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Ward Admissions</div>
                    <div class="stat-value"><?php echo count($ward_admissions); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Currently Admitted</div>
                    <div class="stat-value">
                        <?php
                        $currently_admitted = 0;
                        foreach ($ward_admissions as $admission) {
                            if ($admission['status'] === 'admitted') {
                                $currently_admitted++;
                            }
                        }
                        echo $currently_admitted;
                        ?>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Tests Performed</div>
                    <div class="stat-value"><?php echo count($tests); ?></div>
                </div>
            </div>
        </div>

        <div class="text-center mt-3">
            <a href="patient_search.php" class="btn btn-secondary">← Back to Patient Search</a>
        </div>
    <?php endif; ?>
</div>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
