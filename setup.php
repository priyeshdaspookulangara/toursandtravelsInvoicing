<?php
// Suppress errors for initial connection attempt if DB doesn't exist
error_reporting(0);
ini_set('display_errors', 0);

require_once 'config.php'; // Defines DB_HOST, DB_USER, DB_PASS, DB_NAME

// --- Stage 1: Connect to MySQL server and drop/create database ---
$temp_conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS);

if (!$temp_conn) {
    die("ERROR: Could not connect to MySQL server. " . mysqli_connect_error());
}

$db_name_sanitized = mysqli_real_escape_string($temp_conn, DB_NAME);

// Drop the database if it exists to ensure a clean slate
$sql_drop_db = "DROP DATABASE IF EXISTS `$db_name_sanitized`";
if (mysqli_query($temp_conn, $sql_drop_db)) {
    echo "Database '" . htmlspecialchars(DB_NAME) . "' dropped successfully if it existed.<br>";
} else {
    // This is not a fatal error, as the user might not have drop privileges,
    // but we should report it. The IF NOT EXISTS clauses in schema.sql should handle it.
    echo "Warning: Could not drop database '$db_name_sanitized'. " . mysqli_error($temp_conn) . "<br>";
}

// Create the database
$sql_create_db = "CREATE DATABASE `$db_name_sanitized` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (mysqli_query($temp_conn, $sql_create_db)) {
    echo "Database '" . htmlspecialchars(DB_NAME) . "' created successfully.<br>";
} else {
    die("ERROR: Could not create database '$db_name_sanitized'. " . mysqli_error($temp_conn) . "<br>");
}
mysqli_close($temp_conn);

// Restore error reporting settings
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Stage 2: Connect to the new database and run schema ---
require_once 'db.php';
$connection = db_connect();

if (!$connection) {
    die("ERROR: Could not connect to the database '" . htmlspecialchars(DB_NAME) . "' after creating it. " . mysqli_connect_error() . "<br>");
}
echo "Successfully connected to database '" . htmlspecialchars(DB_NAME) . "'.<br>";

// --- Stage 3: Read and execute schema.sql ---
$schema_sql = file_get_contents('schema.sql');
if ($schema_sql === false) {
    die("ERROR: Could not read schema.sql file.<br>");
}

// Use mysqli_multi_query for simplicity as it handles multiple statements.
// Note: This requires that statements in schema.sql are properly terminated with semicolons.
if (mysqli_multi_query($connection, $schema_sql)) {
    do {
        // Store first result set
        if ($result = mysqli_store_result($connection)) {
            mysqli_free_result($result);
        }
    } while (mysqli_next_result($connection));
    echo "Database schema created/updated successfully.<br>";
} else {
    die("Error creating schema: " . mysqli_error($connection) . "<br>");
}


// --- Stage 4: Add a default admin user ---
$admin_username = 'admin';
$admin_email = 'admin@example.com';
$admin_password = 'password123';
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

$s_admin_username = sanitize_string($admin_username);
$s_admin_email = sanitize_string($admin_email);
$s_hashed_password = sanitize_string($hashed_password);

// The table is new, so no need to check if user exists.
$sql_insert_admin = "INSERT INTO `users` (`username`, `password`, `email`) VALUES ('$s_admin_username', '$s_hashed_password', '$s_admin_email')";
if (db_query($sql_insert_admin)) {
    echo "Admin user '$admin_username' created successfully.<br>";
    echo "Login with Username: $admin_username, Password: $admin_password <br>";
    echo "<strong>IMPORTANT: Change this password after first login!</strong><br>";
} else {
    echo "Error creating admin user: " . mysqli_error($connection) . "<br>";
}

echo "Setup script finished.<br>";
?>