<?php
// Navbar - requires session to be started
if (!isset($_SESSION['staffno'])) {
    // Don't show navbar if not logged in
    return;
}

// Determine path prefix for links
$path_prefix = isset($css_path_prefix) ? $css_path_prefix : '';
?>
<nav class="navbar">
    <div class="navbar-brand">
        🏥 QMC Hospital Management System
    </div>
    <div class="navbar-user">
        <div class="user-info">
            <div class="user-name">
                Dr. <?php echo htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']); ?>
                <?php if ($_SESSION['is_admin']): ?>
                    <span class="badge badge-admin">ADMIN</span>
                <?php endif; ?>
            </div>
            <div class="user-role">
                <?php echo htmlspecialchars($_SESSION['specialisation'] ?? 'N/A'); ?>
                <?php if (isset($_SESSION['ward'])): ?>
                    | <?php echo htmlspecialchars($_SESSION['ward']); ?>
                <?php endif; ?>
            </div>
        </div>
        <a href="<?php echo $path_prefix; ?>logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

