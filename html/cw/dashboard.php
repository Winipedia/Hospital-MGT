<?php
session_start();

// make sure user is logged in before showing dashboard
if (!isset($_SESSION['staffno'])) {
    header('Location: index.php');
    exit();
}

// grab user info from session to display on page
$firstname = $_SESSION['firstname'];
$lastname = $_SESSION['lastname'];
$is_admin = $_SESSION['is_admin'];
$specialisation = $_SESSION['specialisation'] ?? 'N/A';
$ward = $_SESSION['ward'] ?? 'N/A';

// setup page title and extra css files
$page_title = 'Dashboard - QMC Hospital Management System';
$extra_css = ['dashboard.css'];

// load the header template
require_once 'includes/header.php';

// load navbar with user info
require_once 'includes/navbar.php';
?>

<div class="container">
    <div class="card">
        <h1>Welcome back, Dr. <?php echo htmlspecialchars($firstname); ?>! 👋</h1>
        <p>Logged in at <?php echo date('l, F j, Y g:i A', strtotime($_SESSION['login_time'])); ?></p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Specialisation</div>
            <div class="stat-value"><?php echo htmlspecialchars($specialisation); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Ward</div>
            <div class="stat-value"><?php echo htmlspecialchars($ward); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Account Type</div>
            <div class="stat-value"><?php echo $is_admin ? 'Administrator' : 'Doctor'; ?></div>
        </div>
    </div>

    <div class="card">
        <h2>Quick Actions</h2>
        <div class="menu-grid">
            <a href="patient_search.php" class="menu-card">
                <div class="menu-card-icon">🔍</div>
                <div class="menu-card-title">Search Patients</div>
                <div class="menu-card-desc">Search for patients by name or NHS number and view their details</div>
            </a>

            <a href="add_test.php" class="menu-card">
                <div class="menu-card-icon">💉</div>
                <div class="menu-card-title">Prescribe Tests</div>
                <div class="menu-card-desc">Add new tests and prescribe them to patients</div>
            </a>

            <a href="profile.php" class="menu-card">
                <div class="menu-card-icon">👤</div>
                <div class="menu-card-title">My Profile</div>
                <div class="menu-card-desc">View and update your doctor profile information</div>
            </a>

            <a href="parking_permit.php" class="menu-card">
                <div class="menu-card-icon">🅿️</div>
                <div class="menu-card-title">Parking Permit</div>
                <div class="menu-card-desc">Request a monthly or yearly parking permit</div>
            </a>

            <a href="ward_dashboard.php" class="menu-card">
                <div class="menu-card-icon">🏥</div>
                <div class="menu-card-title">Ward Dashboard</div>
                <div class="menu-card-desc">View all wards, bed occupancy, and patient admissions</div>
            </a>

            <?php if ($is_admin): ?>
                <a href="admin/create_doctor.php" class="menu-card" style="border-left-color: #ffd700;">
                    <div class="menu-card-icon">👨‍⚕️</div>
                    <div class="menu-card-title">Create Doctor Account</div>
                    <div class="menu-card-desc">Add new doctor accounts to the system (Admin only)</div>
                </a>

                <a href="admin/parking_approvals.php" class="menu-card" style="border-left-color: #ffd700;">
                    <div class="menu-card-icon">✅</div>
                    <div class="menu-card-title">Parking Approvals</div>
                    <div class="menu-card-desc">Approve or reject parking permit requests (Admin only)</div>
                </a>

                <a href="admin/audit_log.php" class="menu-card" style="border-left-color: #ffd700;">
                    <div class="menu-card-icon">📋</div>
                    <div class="menu-card-title">Audit Trail</div>
                    <div class="menu-card-desc">View all database changes and user actions (Admin only)</div>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
