<?php
session_start();

// check if user logged in
if (!isset($_SESSION['staffno'])) {
    header('Location: index.php');
    exit();
}

require_once 'db.inc.php';

$staffno = $_SESSION['staffno'];
$ward_id = isset($_GET['wardid']) ? intval($_GET['wardid']) : 0;

// if no ward id provided, go back to ward dashboard
if ($ward_id === 0) {
    header('Location: ward_dashboard.php');
    exit();
}

// get ward information from database
$ward_sql = 'SELECT w.*, a.street, a.city, a.postcode, d.name as department_name
             FROM ward w
             LEFT JOIN address a ON w.address_id = a.address_id
             LEFT JOIN department d ON w.department_id = d.department_id
             WHERE w.wardid = ?';
$ward_stmt = $conn->prepare($ward_sql);
$ward_stmt->bind_param('i', $ward_id);
$ward_stmt->execute();
$ward_result = $ward_stmt->get_result();

// if ward doesnt exist, redirect back
if ($ward_result->num_rows === 0) {
    header('Location: ward_dashboard.php');
    exit();
}

$ward = $ward_result->fetch_assoc();
$ward_stmt->close();

// get all patients currently admitted to this ward
$patients_sql = "SELECT wpa.*, p.firstname, p.lastname, p.NHSno, p.phone,
                 d.firstname as consultant_firstname, d.lastname as consultant_lastname,
                 DATEDIFF(CURDATE(), wpa.date) as days_admitted
                 FROM wardpatientaddmission wpa
                 JOIN patient p ON wpa.pid = p.NHSno
                 LEFT JOIN doctor d ON wpa.consultantid = d.staffno
                 WHERE wpa.wardid = ? AND wpa.status = 'admitted'
                 ORDER BY wpa.date DESC";
$patients_stmt = $conn->prepare($patients_sql);
$patients_stmt->bind_param('i', $ward_id);
$patients_stmt->execute();
$patients_result = $patients_stmt->get_result();
$current_patients = [];
while ($row = $patients_result->fetch_assoc()) {
    $current_patients[] = $row;
}
$patients_stmt->close();

// get all doctors assigned to this ward
$doctors_sql = 'SELECT d.staffno, d.firstname, d.lastname, d.consultantstatus,
                s.specialisation_name, d.username
                FROM doctor d
                LEFT JOIN specialisation s ON d.specialisation_id = s.specialisation_id
                WHERE d.ward_id = ?
                ORDER BY d.consultantstatus DESC, d.lastname';
$doctors_stmt = $conn->prepare($doctors_sql);
$doctors_stmt->bind_param('i', $ward_id);
$doctors_stmt->execute();
$doctors_result = $doctors_stmt->get_result();
$ward_doctors = [];
while ($row = $doctors_result->fetch_assoc()) {
    $ward_doctors[] = $row;
}
$doctors_stmt->close();

// Get admission history (last 20)
$history_sql = 'SELECT wpa.*, p.firstname, p.lastname, p.NHSno,
                d.firstname as consultant_firstname, d.lastname as consultant_lastname
                FROM wardpatientaddmission wpa
                JOIN patient p ON wpa.pid = p.NHSno
                LEFT JOIN doctor d ON wpa.consultantid = d.staffno
                WHERE wpa.wardid = ?
                ORDER BY wpa.date DESC, wpa.time DESC
                LIMIT 20';
$history_stmt = $conn->prepare($history_sql);
$history_stmt->bind_param('i', $ward_id);
$history_stmt->execute();
$history_result = $history_stmt->get_result();
$admission_history = [];
while ($row = $history_result->fetch_assoc()) {
    $admission_history[] = $row;
}
$history_stmt->close();

// Calculate statistics
$total_beds = $ward['noofbeds'] ?? 0;
$occupied_beds = count($current_patients);
$available_beds = $total_beds - $occupied_beds;
$occupancy_rate = $total_beds > 0 ? round(($occupied_beds / $total_beds) * 100, 1) : 0;

// setup page title
$page_title = htmlspecialchars($ward['wardname']) . ' - Ward Details';
$extra_css = [];

// load templates
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>🏥 <?php echo htmlspecialchars($ward['wardname']); ?></h1>
        <p><?php echo htmlspecialchars($ward['department_name'] ?? 'General Ward'); ?></p>
    </div>

    <!-- Ward Statistics -->
    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 20px;">
        <div class="stat-card">
            <div class="stat-label">Total Beds</div>
            <div class="stat-value"><?php echo $total_beds; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Occupied</div>
            <div class="stat-value" style="color: #e74c3c;"><?php echo $occupied_beds; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Available</div>
            <div class="stat-value" style="color: #27ae60;"><?php echo $available_beds; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Occupancy Rate</div>
            <div class="stat-value" style="color: <?php echo $occupancy_rate > 80 ? '#e74c3c' : ($occupancy_rate > 60 ? '#f39c12' : '#27ae60'); ?>">
                <?php echo $occupancy_rate; ?>%
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Assigned Doctors</div>
            <div class="stat-value"><?php echo count($ward_doctors); ?></div>
        </div>
    </div>

    <!-- Ward Information -->
    <div class="card">
        <div class="card-header">
            <h2>ℹ️ Ward Information</h2>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div>
                    <strong>Ward Name:</strong><br>
                    <?php echo htmlspecialchars($ward['wardname']); ?>
                </div>
                <div>
                    <strong>Department:</strong><br>
                    <?php echo htmlspecialchars($ward['department_name'] ?? 'N/A'); ?>
                </div>
                <div>
                    <strong>Phone:</strong><br>
                    <?php echo htmlspecialchars($ward['phone']); ?>
                </div>
                <div>
                    <strong>Location:</strong><br>
                    <?php echo htmlspecialchars($ward['street'] ?? 'N/A'); ?>,<br>
                    <?php echo htmlspecialchars($ward['city'] ?? 'N/A'); ?> <?php echo htmlspecialchars($ward['postcode'] ?? ''); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Patients -->
    <div class="card mt-3">
        <div class="card-header">
            <h2>👥 Current Patients (<?php echo count($current_patients); ?>)</h2>
        </div>
        <div class="card-body">
            <?php if (count($current_patients) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>NHS Number</th>
                                <th>Patient Name</th>
                                <th>Admission Date</th>
                                <th>Admission Time</th>
                                <th>Days Admitted</th>
                                <th>Consultant</th>
                                <th>Phone</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($current_patients as $patient): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($patient['NHSno']); ?></code></td>
                                    <td><strong><?php echo htmlspecialchars($patient['firstname'] . ' ' . $patient['lastname']); ?></strong></td>
                                    <td><?php echo date('d/m/Y', strtotime($patient['date'])); ?></td>
                                    <td><?php echo $patient['time'] ? date('H:i', strtotime($patient['time'])) : 'N/A'; ?></td>
                                    <td>
                                        <span class="badge <?php echo $patient['days_admitted'] > 7 ? 'badge-warning' : 'badge-info'; ?>">
                                            <?php echo $patient['days_admitted']; ?> days
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($patient['consultant_firstname'] . ' ' . $patient['consultant_lastname']); ?></td>
                                    <td><?php echo htmlspecialchars($patient['phone'] ?? 'N/A'); ?></td>
                                    <td>
                                        <a href="patient_info.php?nhs=<?php echo urlencode($patient['NHSno']); ?>" class="btn btn-primary btn-sm">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="info-message">
                    <p>ℹ️ No patients currently admitted to this ward.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ward Doctors -->
    <div class="card mt-3">
        <div class="card-header">
            <h2>👨‍⚕️ Assigned Doctors (<?php echo count($ward_doctors); ?>)</h2>
        </div>
        <div class="card-body">
            <?php if (count($ward_doctors) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Staff No</th>
                                <th>Name</th>
                                <th>Specialisation</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ward_doctors as $doctor): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($doctor['staffno']); ?></code></td>
                                    <td><strong><?php echo htmlspecialchars($doctor['firstname'] . ' ' . $doctor['lastname']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($doctor['specialisation_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if ($doctor['consultantstatus']): ?>
                                            <span class="badge badge-success">Consultant</span>
                                        <?php else: ?>
                                            <span class="badge badge-info">Doctor</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="info-message">
                    <p>ℹ️ No doctors currently assigned to this ward.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Admission History -->
    <div class="card mt-3">
        <div class="card-header">
            <h2>📋 Recent Admission History (Last 20)</h2>
        </div>
        <div class="card-body">
            <?php if (count($admission_history) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>NHS Number</th>
                                <th>Patient Name</th>
                                <th>Admission Date</th>
                                <th>Admission Time</th>
                                <th>Status</th>
                                <th>Consultant</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admission_history as $admission): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($admission['NHSno']); ?></code></td>
                                    <td><strong><?php echo htmlspecialchars($admission['firstname'] . ' ' . $admission['lastname']); ?></strong></td>
                                    <td><?php echo date('d/m/Y', strtotime($admission['date'])); ?></td>
                                    <td><?php echo $admission['time'] ? date('H:i', strtotime($admission['time'])) : 'N/A'; ?></td>
                                    <td>
                                        <?php if ($admission['status'] === 'admitted'): ?>
                                            <span class="badge badge-success">Admitted</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Discharged</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($admission['consultant_firstname'] . ' ' . $admission['consultant_lastname']); ?></td>
                                    <td>
                                        <a href="patient_info.php?nhs=<?php echo urlencode($admission['NHSno']); ?>" class="btn btn-primary btn-sm">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="info-message">
                    <p>ℹ️ No admission history found for this ward.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="text-center mt-3">
        <a href="ward_dashboard.php" class="btn btn-secondary">← Back to Ward Dashboard</a>
    </div>
</div>

<?php
$conn->close();
require_once 'includes/footer.php';
?>

