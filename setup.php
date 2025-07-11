<?php
// Suppress errors for initial connection attempt if DB doesn't exist
error_reporting(0);
ini_set('display_errors', 0);

require_once 'config.php'; // Defines DB_HOST, DB_USER, DB_PASS, DB_NAME

// --- Stage 1: Connect to MySQL server and create database if it doesn't exist ---
$temp_conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS);

if (!$temp_conn) {
    die("ERROR: Could not connect to MySQL server. " . mysqli_connect_error());
}

$db_name_sanitized = mysqli_real_escape_string($temp_conn, DB_NAME);
$sql_create_db = "CREATE DATABASE IF NOT EXISTS `$db_name_sanitized` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";

if (mysqli_query($temp_conn, $sql_create_db)) {
    echo "Database '" . htmlspecialchars(DB_NAME) . "' created successfully or already exists.<br>";
} else {
    die("ERROR: Could not create database '$db_name_sanitized'. " . mysqli_error($temp_conn) . "<br>");
}
mysqli_close($temp_conn);

// Restore error reporting settings from config.php or default
error_reporting(E_ALL);
ini_set('display_errors', 1); // Or whatever was set in config.php

// --- Stage 2: Include db.php (which establishes connection to the specific DB) and run schema ---
require_once 'db.php'; // Now includes db_connect() which uses DB_NAME

$connection = db_connect(); // Establish connection using db.php function

if (!$connection) {
    die("ERROR: Could not connect to the database '" . htmlspecialchars(DB_NAME) . "' after creating it. " . mysqli_connect_error() . "<br>");
}

echo "Successfully connected to database '" . htmlspecialchars(DB_NAME) . "'.<br>";

// --- Stage 3: Read and execute schema.sql ---
$schema_sql = file_get_contents('schema.sql');
if ($schema_sql === false) {
    die("ERROR: Could not read schema.sql file.<br>");
}

// Split schema into individual statements (mysqli_multi_query can be problematic with some complex statements or comments)
$sql_statements = explode(';', $schema_sql);
$errors = [];

foreach ($sql_statements as $statement) {
    $statement = trim($statement);
    if (!empty($statement)) {
        // Remove comments from the statement for cleaner execution
        $statement = preg_replace('/\s*--.*/', '', $statement); // Remove -- comments
        $statement = preg_replace('/\s*\/\*.*?\*\//s', '', $statement); // Remove /* */ comments

        if (!empty(trim($statement))) { // Check if statement is not empty after removing comments
            if (mysqli_query($connection, $statement)) {
                echo "Successfully executed: " . htmlspecialchars(substr($statement, 0, 100)) . "...<br>";
            } else {
                $error_message = "Error executing statement: " . htmlspecialchars(substr($statement, 0, 100)) . "... - " . mysqli_error($connection) . "<br>";
                echo $error_message;
                $errors[] = $error_message;
            }
        }
    }
}

if (empty($errors)) {
    echo "Database schema created/updated successfully.<br>";
} else {
    echo "There were errors during schema creation:<br>";
    foreach ($errors as $err) {
        echo "- " . $err;
    }
}


// --- Stage 4: Add a default admin user if not exists ---
$admin_username = 'admin';
$admin_email = 'admin@example.com';
$admin_password = 'password123'; // Plain text password, will be hashed

// Hash the password
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

// Sanitize inputs for SQL query (even though they are hardcoded here, it's good practice)
$s_admin_username = sanitize_string($admin_username); // Uses $connection from db_connect()
$s_admin_email = sanitize_string($admin_email); // Uses $connection from db_connect()
$s_hashed_password = sanitize_string($hashed_password); // Hash is already safe, but escape for query syntax

// Check if admin user already exists
$sql_check_user = "SELECT id FROM `users` WHERE `username` = '$s_admin_username' OR `email` = '$s_admin_email'";
$result = db_query($sql_check_user);

if ($result && mysqli_num_rows($result) == 0) {
    // Insert the admin user
    $sql_insert_admin = "INSERT INTO `users` (`username`, `password`, `email`) VALUES ('$s_admin_username', '$s_hashed_password', '$s_admin_email')";
    if (db_query($sql_insert_admin)) {
        echo "Admin user '$admin_username' created successfully.<br>";
        echo "Login with Username: $admin_username, Password: $admin_password <br>";
        echo "<strong>IMPORTANT: Change this password after first login!</strong><br>";
    } else {
        echo "Error creating admin user: " . mysqli_error($connection) . "<br>";
    }
} elseif ($result && mysqli_num_rows($result) > 0) {
    echo "Admin user '$admin_username' or email '$admin_email' already exists.<br>";
} else {
    echo "Error checking for existing admin user: " . mysqli_error($connection) . "<br>";
}

echo "Setup script finished.<br>";

// db_close(); // Connection will be closed by shutdown function if registered in db.php, or manually if needed.
?>
