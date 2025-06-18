<?php
// config.php - Database Configuration

// Enable error reporting (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database credentials
// define('DB_HOST', 'sql311.infinityfree.com');
// define('DB_USER', 'if0_38382334');
// define('DB_PASS', 'PAyQbh24YXB');
// define('DB_NAME', 'if0_38382334_wispotdb');

// $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$conn = new mysqli('localhost', 'root', '', 'capstonesample');
// Create database connection


// Check connection
if ($conn->connect_error) {
    // Optional: log error to a file
    error_log("Database connection failed: " . $conn->connect_error);
    die("We are currently experiencing technical issues. Please try again later.");
}
?>