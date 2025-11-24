<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['staffno'])) {
    header('Location: index.php');
    exit();
}

require_once 'db.inc.php';

$staffno = $_SESSION['staffno'];

// Get all wards with statistics
$wards_sql = "SELECT w.wardid, w.wardname, w.phone, w.noofbeds,
                     a.street, a.city, a.postcode,
                     d.name as department_name,
                     COUNT(DISTINCT wpa.pid) as current_patients,
                     COUNT(DISTINCT doc.staffno) as total_doctors
              FROM ward w
              LEFT JOIN address a ON w.address_id = a.address_id
              LEFT JOIN department d ON w.department_id = d.department_id
              LEFT JOIN wardpatientaddmission wpa ON w.wardid = wpa.wardid AND wpa.status = 'admitted'
              LEFT JOIN doctor doc ON w.wardid = doc.ward_id
              GROUP BY w.wardid
              ORDER BY w.wardname";
$wards_result = $conn->query($wards_sql);
$wards = [];
while ($row = $wards_result->fetch_assoc()) {
    $wards[] = $row;
}

// Get overall statistics
$total_beds = 0;
$total_occupied = 0;
$total_wards = count($wards);
$total_doctors_all = 0;

foreach ($wards as $ward) {
    $total_beds += $ward['noofbeds'] ?? 0;
    $total_occupied += $ward['current_patients'];
    $total_doctors_all += $ward['total_doctors'];
}

$occupancy_rate = $total_beds > 0 ? round(($total_occupied / $total_beds) * 100, 1) : 0;

// Set page variables for header
$page_title = 'Ward Dashboard - QMC Hospital Management System';
$extra_css = [];

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>🏥 Ward Dashboard</h1>
        <p>Overview of all hospital wards, bed occupancy, and patient admissions</p>
    </div>

    <!-- Overall Statistics -->
    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
        <div class="stat-card">
            <div class="stat-label">Total Wards</div>
            <div class="stat-value"><?php echo $total_wards; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Beds</div>
            <div class="stat-value"><?php echo $total_beds; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Occupied Beds</div>
            <div class="stat-value"><?php echo $total_occupied; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Occupancy Rate</div>
            <div class="stat-value" style="color: <?php echo $occupancy_rate > 80 ? '#e74c3c' : ($occupancy_rate > 60 ? '#f39c12' : '#27ae60'); ?>">
                <?php echo $occupancy_rate; ?>%
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Doctors</div>
            <div class="stat-value"><?php echo $total_doctors_all; ?></div>
        </div>
    </div>

    <!-- Wards List -->
    <div class="card">
        <div class="card-header">
            <h2>🏥 All Wards</h2>
        </div>
        <div class="card-body">
            <?php if (count($wards) > 0): ?>
                <div class="wards-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px;">
                    <?php foreach ($wards as $ward): ?>
                        <?php
                        $ward_occupancy = ($ward['noofbeds'] ?? 0) > 0 ? round(($ward['current_patients'] / $ward['noofbeds']) * 100, 1) : 0;
                        $available_beds = ($ward['noofbeds'] ?? 0) - $ward['current_patients'];
                        ?>
                        <div class="ward-card" style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid #667eea;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                                <div>
                                    <h3 style="margin: 0 0 5px 0; color: #2c3e50; font-size: 20px;">
                                        <?php echo htmlspecialchars($ward['wardname']); ?>
                                    </h3>
                                    <p style="margin: 0; color: #7f8c8d; font-size: 14px;">
                                        <?php echo htmlspecialchars($ward['department_name'] ?? 'N/A'); ?>
                                    </p>
                                </div>
                                <span class="badge <?php echo $ward_occupancy > 80 ? 'badge-danger' : ($ward_occupancy > 60 ? 'badge-warning' : 'badge-success'); ?>">
                                    <?php echo $ward_occupancy; ?>% Full
                                </span>
                            </div>

                            <div style="margin-bottom: 15px;">
                                <div style="background: #ecf0f1; border-radius: 10px; height: 20px; overflow: hidden;">
                                    <div style="background: <?php echo $ward_occupancy > 80 ? '#e74c3c' : ($ward_occupancy > 60 ? '#f39c12' : '#27ae60'); ?>; height: 100%; width: <?php echo $ward_occupancy; ?>%; transition: width 0.3s;"></div>
                                </div>
                                <p style="margin: 5px 0 0 0; font-size: 12px; color: #7f8c8d;">
                                    <?php echo $ward['current_patients']; ?> / <?php echo $ward['noofbeds'] ?? 0; ?> beds occupied
                                    (<?php echo $available_beds; ?> available)
                                </p>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                                <div style="background: #f8f9fa; padding: 10px; border-radius: 5px;">
                                    <div style="font-size: 12px; color: #7f8c8d;">Current Patients</div>
                                    <div style="font-size: 20px; font-weight: 700; color: #667eea;"><?php echo $ward['current_patients']; ?></div>
                                </div>
                                <div style="background: #f8f9fa; padding: 10px; border-radius: 5px;">
                                    <div style="font-size: 12px; color: #7f8c8d;">Doctors</div>
                                    <div style="font-size: 20px; font-weight: 700; color: #667eea;"><?php echo $ward['total_doctors']; ?></div>
                                </div>
                            </div>

                            <div style="font-size: 13px; color: #555; margin-bottom: 10px;">
                                <div style="margin-bottom: 5px;">
                                    <strong>📍 Location:</strong> <?php echo htmlspecialchars($ward['street'] ?? 'N/A'); ?>, 
                                    <?php echo htmlspecialchars($ward['city'] ?? 'N/A'); ?>
                                </div>
                                <div>
                                    <strong>📞 Phone:</strong> <?php echo htmlspecialchars($ward['phone']); ?>
                                </div>
                            </div>

                            <a href="ward_details.php?wardid=<?php echo $ward['wardid']; ?>" class="btn btn-primary" style="width: 100%; text-align: center; margin-top: 10px;">
                                View Details →
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="info-message">
                    <p>ℹ️ No wards found in the system.</p>
                </div>
            <?php endif; ?>
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

