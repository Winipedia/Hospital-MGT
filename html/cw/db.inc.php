<?php
/**
 * Database Connection Configuration
 * QMC Hospital Management System
 * COMP4039 Coursework
 */

// Database configuration
$db_host = 'mariadb';  // Docker service name
$db_user = 'root';
$db_pass = 'rootpwd';  // From docker-compose.yml MYSQL_ROOT_PASSWORD
$db_name = 'hospital';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Enable exception mode for better error handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
// No closing PHP tag to prevent whitespace issues with header() redirects