<?php
session_start();

// check if user logged in
if (!isset($_SESSION['staffno'])) {
    header('Location: ../index.php');
    exit();
}

// only admins can view audit log
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../dashboard.php');
    exit();
}

require_once '../db.inc.php';

$staffno = $_SESSION['staffno'];

// setup pagination - showing 50 records per page
$records_per_page = 50;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $records_per_page;

// get filter parameters from url
$filter_user = isset($_GET['user']) ? trim($_GET['user']) : '';
$filter_action = isset($_GET['action']) ? trim($_GET['action']) : '';
$filter_table = isset($_GET['table']) ? trim($_GET['table']) : '';
$filter_date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$filter_date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// build WHERE clause dynamically based on filters
$where_conditions = [];
$params = [];
$types = '';

// add user filter if provided
if (!empty($filter_user)) {
    $where_conditions[] = 'a.user_id = ?';
    $params[] = $filter_user;
    $types .= 's';
}

// add action filter if provided
if (!empty($filter_action)) {
    $where_conditions[] = 'a.action = ?';
    $params[] = $filter_action;
    $types .= 's';
}

// add table filter if provided
if (!empty($filter_table)) {
    $where_conditions[] = 'a.table_name = ?';
    $params[] = $filter_table;
    $types .= 's';
}

// add date from filter if provided
if (!empty($filter_date_from)) {
    $where_conditions[] = 'DATE(a.timestamp) >= ?';
    $params[] = $filter_date_from;
    $types .= 's';
}

// add date to filter if provided
if (!empty($filter_date_to)) {
    $where_conditions[] = 'DATE(a.timestamp) <= ?';
    $params[] = $filter_date_to;
    $types .= 's';
}

// combine all conditions into WHERE clause
$where_clause = count($where_conditions) > 0 ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total 
              FROM audit_log a 
              $where_clause";
$count_stmt = $conn->prepare($count_sql);

if (!empty($types)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
$count_stmt->close();

// Fetch audit logs with doctor information
$audit_sql = "SELECT a.audit_id, a.user_id, a.action, a.table_name, a.record_id, 
                     a.old_value, a.new_value, a.timestamp, a.ip_address,
                     d.firstname, d.lastname, d.is_admin
              FROM audit_log a
              LEFT JOIN doctor d ON a.user_id = d.staffno
              $where_clause
              ORDER BY a.timestamp DESC
              LIMIT ? OFFSET ?";

$audit_stmt = $conn->prepare($audit_sql);

// Add pagination parameters
$params[] = $records_per_page;
$params[] = $offset;
$types .= 'ii';

$audit_stmt->bind_param($types, ...$params);
$audit_stmt->execute();
$audit_result = $audit_stmt->get_result();
$audit_logs = [];

while ($row = $audit_result->fetch_assoc()) {
    $audit_logs[] = $row;
}
$audit_stmt->close();

// Get all users for filter dropdown
$users_sql = 'SELECT DISTINCT staffno, firstname, lastname FROM doctor ORDER BY firstname, lastname';
$users_result = $conn->query($users_sql);
$users = [];

while ($row = $users_result->fetch_assoc()) {
    $users[] = $row;
}

// Get all actions for filter dropdown
$actions_sql = 'SELECT DISTINCT action FROM audit_log ORDER BY action';
$actions_result = $conn->query($actions_sql);
$actions = [];

while ($row = $actions_result->fetch_assoc()) {
    $actions[] = $row['action'];
}

// Get all tables for filter dropdown
$tables_sql = 'SELECT DISTINCT table_name FROM audit_log WHERE table_name IS NOT NULL ORDER BY table_name';
$tables_result = $conn->query($tables_sql);
$tables = [];

while ($row = $tables_result->fetch_assoc()) {
    $tables[] = $row['table_name'];
}

// Get statistics
$stats_sql = 'SELECT 
                COUNT(*) as total_logs,
                COUNT(DISTINCT user_id) as total_users,
                COUNT(DISTINCT DATE(timestamp)) as days_logged,
                MAX(timestamp) as last_activity
              FROM audit_log';
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// setup page title
$page_title = 'Audit Trail - QMC Hospital Management System';
$extra_css = [];
$css_path_prefix = '../';

// load templates
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<div class="container">
    <div class="page-header">
        <h1>📋 Audit Trail</h1>
        <p>Complete database activity log for regulatory compliance and security monitoring</p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
        <div class="stat-card">
            <div class="stat-label">Total Audit Logs</div>
            <div class="stat-value"><?php echo number_format($stats['total_logs']); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Active Users</div>
            <div class="stat-value"><?php echo $stats['total_users']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Days Logged</div>
            <div class="stat-value"><?php echo $stats['days_logged']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Last Activity</div>
            <div class="stat-value" style="font-size: 14px;">
                <?php echo $stats['last_activity'] ? date('d/m/Y H:i', strtotime($stats['last_activity'])) : 'N/A'; ?>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card">
        <div class="card-header">
            <h2>🔍 Filter Audit Logs</h2>
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <div class="form-row">
                    <!-- User Filter -->
                    <div class="form-group" style="flex: 1;">
                        <label for="user">User</label>
                        <select id="user" name="user">
                            <option value="">-- All Users --</option>
                            <?php foreach ($users as $user) { ?>
                                <option value="<?php echo htmlspecialchars($user['staffno']); ?>"
                                    <?php echo ($filter_user === $user['staffno']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname'] . ' (' . $user['staffno'] . ')'); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <!-- Action Filter -->
                    <div class="form-group" style="flex: 1;">
                        <label for="action">Action</label>
                        <select id="action" name="action">
                            <option value="">-- All Actions --</option>
                            <?php foreach ($actions as $action) { ?>
                                <option value="<?php echo htmlspecialchars($action); ?>"
                                    <?php echo ($filter_action === $action) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($action); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <!-- Table Filter -->
                    <div class="form-group" style="flex: 1;">
                        <label for="table">Table</label>
                        <select id="table" name="table">
                            <option value="">-- All Tables --</option>
                            <?php foreach ($tables as $table) { ?>
                                <option value="<?php echo htmlspecialchars($table); ?>"
                                    <?php echo ($filter_table === $table) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($table); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <!-- Date From -->
                    <div class="form-group" style="flex: 1;">
                        <label for="date_from">Date From</label>
                        <input type="date" id="date_from" name="date_from"
                               value="<?php echo htmlspecialchars($filter_date_from); ?>">
                    </div>

                    <!-- Date To -->
                    <div class="form-group" style="flex: 1;">
                        <label for="date_to">Date To</label>
                        <input type="date" id="date_to" name="date_to"
                               value="<?php echo htmlspecialchars($filter_date_to); ?>">
                    </div>

                    <!-- Buttons -->
                    <div class="form-group" style="flex: 1; display: flex; align-items: flex-end; gap: 10px;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">Apply Filters</button>
                        <a href="audit_log.php" class="btn btn-secondary" style="flex: 1; text-align: center;">Clear</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Audit Logs Table -->
    <div class="card mt-3">
        <div class="card-header">
            <h2>📊 Audit Logs (<?php echo number_format($total_records); ?> records)</h2>
            <?php if (count($where_conditions) > 0) { ?>
                <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;">
                    Filtered results - Showing <?php echo count($audit_logs); ?> of <?php echo number_format($total_records); ?> records
                </p>
            <?php } ?>
        </div>
        <div class="card-body">
            <?php if (count($audit_logs) > 0) { ?>
                <div style="overflow-x: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th style="width: 140px;">Timestamp</th>
                                <th>User</th>
                                <th style="width: 100px;">Action</th>
                                <th style="width: 120px;">Table</th>
                                <th style="width: 100px;">Record ID</th>
                                <th>Details</th>
                                <th style="width: 120px;">IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($audit_logs as $log) { ?>
                                <tr>
                                    <td><?php echo $log['audit_id']; ?></td>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($log['timestamp'])); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($log['firstname'] . ' ' . $log['lastname']); ?></strong>
                                        <br>
                                        <small style="color: #666;"><?php echo htmlspecialchars($log['user_id']); ?></small>
                                        <?php if ($log['is_admin']) { ?>
                                            <span class="badge badge-admin" style="margin-left: 5px;">ADMIN</span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php
                                        $action_colors = [
                                            'LOGIN'  => 'badge-success',
                                            'LOGOUT' => 'badge-secondary',
                                            'INSERT' => 'badge-primary',
                                            'UPDATE' => 'badge-warning',
                                            'DELETE' => 'badge-danger',
                                            'SELECT' => 'badge-info',
                                        ];
                                $badge_class = $action_colors[$log['action']] ?? 'badge-secondary';
                                ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo htmlspecialchars($log['action']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['table_name'] ?? 'N/A'); ?></td>
                                    <td><code><?php echo htmlspecialchars($log['record_id'] ?? 'N/A'); ?></code></td>
                                    <td style="max-width: 300px;">
                                        <?php if ($log['old_value']) { ?>
                                            <strong>Old:</strong> <?php echo htmlspecialchars(substr($log['old_value'], 0, 100)); ?>
                                            <?php if (strlen($log['old_value']) > 100) { ?>...<?php } ?>
                                            <br>
                                        <?php } ?>
                                        <?php if ($log['new_value']) { ?>
                                            <strong>New:</strong> <?php echo htmlspecialchars(substr($log['new_value'], 0, 100)); ?>
                                            <?php if (strlen($log['new_value']) > 100) { ?>...<?php } ?>
                                        <?php } ?>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></code></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1) { ?>
                    <div class="pagination" style="margin-top: 20px; display: flex; justify-content: center; gap: 5px;">
                        <?php if ($page > 1) { ?>
                            <a href="?page=1<?php echo !empty($filter_user) ? '&user=' . urlencode($filter_user) : ''; ?><?php echo !empty($filter_action) ? '&action=' . urlencode($filter_action) : ''; ?><?php echo !empty($filter_table) ? '&table=' . urlencode($filter_table) : ''; ?><?php echo !empty($filter_date_from) ? '&date_from=' . urlencode($filter_date_from) : ''; ?><?php echo !empty($filter_date_to) ? '&date_to=' . urlencode($filter_date_to) : ''; ?>"
                               class="btn btn-secondary">« First</a>
                            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($filter_user) ? '&user=' . urlencode($filter_user) : ''; ?><?php echo !empty($filter_action) ? '&action=' . urlencode($filter_action) : ''; ?><?php echo !empty($filter_table) ? '&table=' . urlencode($filter_table) : ''; ?><?php echo !empty($filter_date_from) ? '&date_from=' . urlencode($filter_date_from) : ''; ?><?php echo !empty($filter_date_to) ? '&date_to=' . urlencode($filter_date_to) : ''; ?>"
                               class="btn btn-secondary">‹ Previous</a>
                        <?php } ?>

                        <span class="btn btn-primary" style="cursor: default;">
                            Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                        </span>

                        <?php if ($page < $total_pages) { ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($filter_user) ? '&user=' . urlencode($filter_user) : ''; ?><?php echo !empty($filter_action) ? '&action=' . urlencode($filter_action) : ''; ?><?php echo !empty($filter_table) ? '&table=' . urlencode($filter_table) : ''; ?><?php echo !empty($filter_date_from) ? '&date_from=' . urlencode($filter_date_from) : ''; ?><?php echo !empty($filter_date_to) ? '&date_to=' . urlencode($filter_date_to) : ''; ?>"
                               class="btn btn-secondary">Next ›</a>
                            <a href="?page=<?php echo $total_pages; ?><?php echo !empty($filter_user) ? '&user=' . urlencode($filter_user) : ''; ?><?php echo !empty($filter_action) ? '&action=' . urlencode($filter_action) : ''; ?><?php echo !empty($filter_table) ? '&table=' . urlencode($filter_table) : ''; ?><?php echo !empty($filter_date_from) ? '&date_from=' . urlencode($filter_date_from) : ''; ?><?php echo !empty($filter_date_to) ? '&date_to=' . urlencode($filter_date_to) : ''; ?>"
                               class="btn btn-secondary">Last »</a>
                        <?php } ?>
                    </div>
                <?php } ?>

            <?php } else { ?>
                <div class="info-message">
                    <p>ℹ️ No audit logs found matching the selected filters.</p>
                    <?php if (count($where_conditions) > 0) { ?>
                        <p><a href="audit_log.php">Clear filters to view all logs</a></p>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- Action Summary -->
    <div class="card mt-3">
        <div class="card-header">
            <h2>📈 Action Summary</h2>
        </div>
        <div class="card-body">
            <?php
            // Get action summary
            $summary_where = count($where_conditions) > 0 ? 'WHERE ' . implode(' AND ', array_map(function ($cond) {
                return str_replace('a.', '', $cond);
            }, $where_conditions)) : '';

$summary_sql = "SELECT action, COUNT(*) as count
                           FROM audit_log
                           $summary_where
                           GROUP BY action
                           ORDER BY count DESC";
$summary_stmt = $conn->prepare($summary_sql);

if (!empty($types) && count($where_conditions) > 0) {
    // Remove the last two 'ii' from types (pagination params)
    $summary_types = substr($types, 0, -2);
    // Remove last two params (pagination)
    $summary_params = array_slice($params, 0, -2);

    if (!empty($summary_types)) {
        $summary_stmt->bind_param($summary_types, ...$summary_params);
    }
}

$summary_stmt->execute();
$summary_result = $summary_stmt->get_result();
$action_summary = [];

while ($row = $summary_result->fetch_assoc()) {
    $action_summary[] = $row;
}
$summary_stmt->close();
?>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                <?php foreach ($action_summary as $summary) { ?>
                    <div class="stat-card">
                        <div class="stat-label"><?php echo htmlspecialchars($summary['action']); ?></div>
                        <div class="stat-value"><?php echo number_format($summary['count']); ?></div>
                    </div>
                <?php } ?>
            </div>
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

code {
    background: #f4f4f4;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
}

.pagination .btn {
    padding: 8px 12px;
    text-decoration: none;
}

@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
    }

    .pagination {
        flex-wrap: wrap;
    }
}
</style>

<?php
$conn->close();
require_once '../includes/footer.php';
?>

