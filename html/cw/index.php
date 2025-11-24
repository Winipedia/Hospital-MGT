<?php
session_start();

// if user already logged in, just send them to dashboard
if (isset($_SESSION['staffno'])) {
    header('Location: dashboard.php');
    exit();
}

// setup variables for login form
$error = '';
$username = '';

// handle login when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'db.inc.php';

    // get username and password from form
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // make sure both fields are filled in
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // look up user in database with all there info
        $sql = 'SELECT d.staffno, d.username, d.password, d.firstname, d.lastname, d.is_admin,
                       s.specialisation_name, w.wardname
                FROM doctor d
                LEFT JOIN specialisation s ON d.specialisation_id = s.specialisation_id
                LEFT JOIN ward w ON d.ward_id = w.wardid
                WHERE d.username = ?';

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        // check if we found exactly one user
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // check password - plain text cuz coursework doesnt need security
            if ($password === $user['password']) {
                // login worked! setup all session variables
                $_SESSION['staffno'] = $user['staffno'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['firstname'] = $user['firstname'];
                $_SESSION['lastname'] = $user['lastname'];
                $_SESSION['is_admin'] = $user['is_admin'];
                $_SESSION['specialisation'] = $user['specialisation_name'];
                $_SESSION['ward'] = $user['wardname'];
                $_SESSION['login_time'] = date('Y-m-d H:i:s');

                // log the login event for audit trail
                $audit_sql = "INSERT INTO audit_log (user_id, action, table_name, record_id, new_value, ip_address)
                              VALUES (?, 'LOGIN', 'doctor', ?, ?, ?)";
                $audit_stmt = $conn->prepare($audit_sql);
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $login_info = 'Login successful at ' . $_SESSION['login_time'];
                $audit_stmt->bind_param('ssss', $user['staffno'], $user['staffno'], $login_info, $ip_address);
                $audit_stmt->execute();
                $audit_stmt->close();

                // send user to dashboard
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Invalid username or password.';
        }

        $stmt->close();
        $conn->close();
    }
}

// setup page title and css
$page_title = 'Login - QMC Hospital Management System';
$extra_css = ['login.css'];

// load header template
require_once 'includes/header.php';
?>
    <div class="login-container">
        <div class="login-header">
            <h1>🏥 QMC Hospital</h1>
            <p>Management System</p>
        </div>

        <div class="login-body">
            <h2 style="margin-bottom: 20px; color: #333; font-size: 20px;">Doctor Login</h2>

            <?php if ($error) { ?>
                <div class="error-message">
                    ⚠️ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php } ?>

            <form method="POST" action="index.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="<?php echo htmlspecialchars($username); ?>"
                        placeholder="Enter your username"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                    >
                </div>

                <button type="submit" class="btn-login">Login</button>
            </form>
        </div>

        <div class="login-footer">
            Queen's Medical Centre &copy; <?php echo date('Y'); ?><br>
            COMP4039 Coursework - Hospital Management System
        </div>
    </div>
<?php require_once 'includes/footer.php'; ?>
