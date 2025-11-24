<?php

/**
 * Database Connection Configuration
 * QMC Hospital Management System
 * COMP4039 Coursework.
 */

// database config - using docker mariadb container
$db_host = 'mariadb';  // Docker service name
$db_user = 'root';
$db_pass = 'rootpwd';  // From docker-compose.yml MYSQL_ROOT_PASSWORD
$db_name = 'hospital';

// create the mysql connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// check if connection worked, die if it didnt
if ($conn->connect_error) {
    exit('Database connection failed: ' . $conn->connect_error);
}

// set charset so we dont get weird encoding issues
$conn->set_charset('utf8mb4');

// turn on exceptions for better error handeling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
// No closing PHP tag to prevent whitespace issues with header() redirects
