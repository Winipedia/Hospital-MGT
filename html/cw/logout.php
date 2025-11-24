<?php
session_start();

// log the logout action if user was logged in
if (isset($_SESSION['staffno'])) {
    require_once 'db.inc.php';

    // insert logout event into audit log
    $audit_sql = "INSERT INTO audit_log (user_id, action, table_name, record_id, new_value, ip_address)
                  VALUES (?, 'LOGOUT', 'doctor', ?, ?, ?)";
    $audit_stmt = $conn->prepare($audit_sql);
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $logout_info = 'Logout at ' . date('Y-m-d H:i:s');
    $audit_stmt->bind_param('ssss', $_SESSION['staffno'], $_SESSION['staffno'], $logout_info, $ip_address);
    $audit_stmt->execute();
    $audit_stmt->close();
    $conn->close();
}

// clear all session data and destroy session
session_unset();
session_destroy();

// send user back to login page
header('Location: index.php');
exit();
?>

