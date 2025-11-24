<?php
// navbar component - needs session to be active
if (!isset($_SESSION['staffno'])) {
    // dont show navbar if user not logged in
    return;
}

// figure out path prefix for links depending on directory level
$path_prefix = isset($css_path_prefix) ? $css_path_prefix : '';
?>
<nav class="navbar">
    <div class="navbar-brand">
        🏥 QMC Hospital Management System
    </div>
    <div class="navbar-user">
        <div class="user-info">
            <!-- show doctor name from session -->
            <div class="user-name">
                Dr. <?php echo htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']); ?>
                <?php if ($_SESSION['is_admin']): ?>
                    <span class="badge badge-admin">ADMIN</span>
                <?php endif; ?>
            </div>
            <!-- display specialisation and ward if available -->
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

