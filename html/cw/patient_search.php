<?php
session_start();

// check if user logged in
if (!isset($_SESSION['staffno'])) {
    header('Location: index.php');
    exit();
}

require_once 'db.inc.php';

// setup variables for patient list and pagination
$patients = [];
$total_patients = 0;
$search_term = '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 5;
$offset = ($page - 1) * $per_page;

// get search term from url if it exists
if (isset($_GET['search_term'])) {
    $search_term = trim($_GET['search_term']);
}

// build query based on whether we're searching or not
if (!empty($search_term)) {
    // count how many patients match the search
    $count_sql = "SELECT COUNT(*) as total FROM patient p
                  WHERE p.NHSno LIKE ?
                     OR CONCAT(p.firstname, ' ', p.lastname) LIKE ?
                     OR p.firstname LIKE ?
                     OR p.lastname LIKE ?";

    $count_stmt = $conn->prepare($count_sql);
    $search_pattern = "%$search_term%";
    $count_stmt->bind_param('ssss', $search_pattern, $search_pattern, $search_pattern, $search_pattern);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_patients = $count_result->fetch_assoc()['total'];
    $count_stmt->close();

    // get the actual patient records that match
    $patients_sql = "SELECT p.*, g.gender_name
                     FROM patient p
                     LEFT JOIN gender g ON p.gender_id = g.gender_id
                     WHERE p.NHSno LIKE ?
                        OR CONCAT(p.firstname, ' ', p.lastname) LIKE ?
                        OR p.firstname LIKE ?
                        OR p.lastname LIKE ?
                     ORDER BY p.lastname, p.firstname
                     LIMIT ? OFFSET ?";

    $patients_stmt = $conn->prepare($patients_sql);
    $patients_stmt->bind_param('ssssii', $search_pattern, $search_pattern, $search_pattern, $search_pattern, $per_page, $offset);
    $patients_stmt->execute();
    $patients_result = $patients_stmt->get_result();
    $patients = $patients_result->fetch_all(MYSQLI_ASSOC);
    $patients_stmt->close();

    // log the search in audit trail
    $audit_sql = "INSERT INTO audit_log (user_id, action, table_name, new_value, ip_address)
                  VALUES (?, 'SELECT', 'patient', ?, ?)";
    $audit_stmt = $conn->prepare($audit_sql);
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $audit_value = "Patient search: $search_term";
    $audit_stmt->bind_param('sss', $_SESSION['staffno'], $audit_value, $ip_address);
    $audit_stmt->execute();
    $audit_stmt->close();
} else {
    // no search term, just show all patients with pagination
    $count_sql = 'SELECT COUNT(*) as total FROM patient';
    $count_result = $conn->query($count_sql);
    $total_patients = $count_result->fetch_assoc()['total'];

    // get patients for current page
    $patients_sql = 'SELECT p.*, g.gender_name
                     FROM patient p
                     LEFT JOIN gender g ON p.gender_id = g.gender_id
                     ORDER BY p.lastname, p.firstname
                     LIMIT ? OFFSET ?';

    $patients_stmt = $conn->prepare($patients_sql);
    $patients_stmt->bind_param('ii', $per_page, $offset);
    $patients_stmt->execute();
    $patients_result = $patients_stmt->get_result();
    $patients = $patients_result->fetch_all(MYSQLI_ASSOC);
    $patients_stmt->close();
}

// calculate total pages for pagination
$total_pages = ceil($total_patients / $per_page);

// setup page title
$page_title = 'Patient Search - QMC Hospital Management System';
$extra_css = [];

// load header template
require_once 'includes/header.php';

// load navbar
require_once 'includes/navbar.php';
?>

<div class="container">
    <div class="card">
        <h1>🔍 Patient Search</h1>
        <p>Search for patients by name or NHS number</p>
    </div>

    <!-- Search Form -->
    <div class="card">
        <form method="GET" action="" style="margin-bottom: 0;">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="search_term">Search Patients</label>
                <div style="display: flex; gap: 10px;">
                    <input type="text" id="search_term" name="search_term"
                           placeholder="Enter patient name or NHS number"
                           value="<?php echo htmlspecialchars($search_term); ?>"
                           style="flex: 1;">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <?php if (!empty($search_term)) { ?>
                        <a href="patient_search.php" class="btn btn-secondary">Clear</a>
                    <?php } ?>
                </div>
                <small style="color: #666; font-size: 12px;">
                    Search by first name, last name, full name, or NHS number
                </small>
            </div>
        </form>
    </div>

    <!-- Patient List -->
    <div class="card">
        <h2>
            <?php if (!empty($search_term)) { ?>
                Search Results (<?php echo $total_patients; ?> found)
            <?php } else { ?>
                All Patients (<?php echo $total_patients; ?> total)
            <?php } ?>
        </h2>

        <?php if (count($patients) > 0) { ?>
            <table>
                <thead>
                    <tr>
                        <th>NHS Number</th>
                        <th>Name</th>
                        <th>Age</th>
                        <th>Gender</th>
                        <th>Phone</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($patients as $patient) { ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($patient['NHSno']); ?></strong></td>
                            <td><?php echo htmlspecialchars($patient['firstname'] . ' ' . $patient['lastname']); ?></td>
                            <td><?php echo htmlspecialchars($patient['age']); ?> years</td>
                            <td><?php echo htmlspecialchars($patient['gender_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                            <td>
                                <a href="patient_info.php?nhs=<?php echo urlencode($patient['NHSno']); ?>"
                                   class="btn btn-primary" style="padding: 5px 15px; font-size: 13px;">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total_pages > 1) { ?>
                <div style="margin-top: 20px; text-align: center;">
                    <div style="display: inline-flex; gap: 5px; align-items: center;">
                        <?php if ($page > 1) { ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"
                               class="btn btn-secondary" style="padding: 8px 12px;">
                                ← Previous
                            </a>
                        <?php } ?>

                        <span style="padding: 0 15px; color: #666;">
                            Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                        </span>

                        <?php if ($page < $total_pages) { ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"
                               class="btn btn-secondary" style="padding: 8px 12px;">
                                Next →
                            </a>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <div class="alert alert-warning">
                <strong>No patients found!</strong><br>
                <?php if (!empty($search_term)) { ?>
                    No patients matching "<?php echo htmlspecialchars($search_term); ?>" were found in the system.
                <?php } else { ?>
                    No patients in the system.
                <?php } ?>
            </div>
        <?php } ?>
    </div>



    <div class="text-center mt-3">
        <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>
</div>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
