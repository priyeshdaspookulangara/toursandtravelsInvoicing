<?php
require_once 'config.php';

$conn = null; // Global connection variable

/**
 * Establishes a database connection.
 * @return mysqli|false The mysqli connection object or false on failure.
 */
function db_connect() {
    global $conn;
    if ($conn === null) {
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if (!$conn) {
            // In a real application, you might log this error or display a more user-friendly message.
            die("Database connection failed: " . mysqli_connect_error());
        }
        mysqli_set_charset($conn, "utf8mb4");
    }
    return $conn;
}

/**
 * Closes the database connection.
 */
function db_close() {
    global $conn;
    if ($conn !== null) {
        mysqli_close($conn);
        $conn = null;
    }
}

/**
 * Sanitizes a string input to prevent SQL injection.
 * This is a basic example. More robust sanitization might be needed depending on the context.
 * IMPORTANT: This function must be used for ALL data that will be part of an SQL query string.
 *
 * @param string $input The string to sanitize.
 * @return string The sanitized string.
 */
function sanitize_string($input) {
    $connection = db_connect();
    if (!$connection) {
        // Handle error appropriately if connection fails
        // For now, we'll die, but a more graceful handling is better in production
        die("Cannot sanitize string: Database connection not available.");
    }
    // Trim whitespace from the beginning and end of the string
    $input = trim($input);
    // Use mysqli_real_escape_string
    $sanitized_input = mysqli_real_escape_string($connection, $input);
    return $sanitized_input;
}

/**
 * Sanitizes an integer input.
 * @param mixed $input The input to sanitize.
 * @return int|false The sanitized integer or false if not a valid integer.
 */
function sanitize_int($input) {
    $input = filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    if (filter_var($input, FILTER_VALIDATE_INT) === false && $input !== '0') { // Allow '0'
        return false; // Or handle error as appropriate
    }
    return (int)$input;
}

/**
 * Sanitizes a decimal/float input.
 * @param mixed $input The input to sanitize.
 * @param int $precision Number of decimal places for formatting (optional).
 * @return float|false The sanitized float/decimal or false if not valid.
 */
function sanitize_decimal($input) {
    $input = filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    if (filter_var($input, FILTER_VALIDATE_FLOAT) === false && $input !== '0.0' && $input !== '0') { // Allow '0.0' and '0'
         return false; // Or handle error as appropriate
    }
    return (float)$input;
}

/**
 * Sanitizes an email input.
 * @param string $input The email string to sanitize.
 * @return string|false The sanitized email or false if not a valid email.
 */
function sanitize_email($input) {
    $sanitized_email = filter_var(trim($input), FILTER_SANITIZE_EMAIL);
    if (filter_var($sanitized_email, FILTER_VALIDATE_EMAIL) === false) {
        return false; // Or handle error as appropriate
    }
    return $sanitized_email;
}

/**
 * Executes a SQL query.
 *
 * @param string $sql The SQL query string.
 * @return mysqli_result|bool The result object for SELECT, SHOW, DESCRIBE or EXPLAIN queries,
 *                            true for other successful queries. False on failure.
 */
function db_query($sql) {
    $connection = db_connect();
    if (!$connection) {
        error_log("Database query failed: No connection. Query: " . $sql);
        return false;
    }

    $result = mysqli_query($connection, $sql);

    if ($result === false) {
        // Log the error for debugging
        error_log("SQL Error: " . mysqli_error($connection) . "\nQuery: " . $sql);
        // Optionally, you could throw an exception or return a more detailed error object
        // For now, returning false indicates failure.
        // In a production environment, avoid displaying mysqli_error() to the user.
        // echo "Error: " . mysqli_error($connection); // For debugging only
    }
    return $result;
}

/**
 * Fetches a single row from a query result.
 *
 * @param mysqli_result $result The result object from mysqli_query.
 * @return array|null|false An associative array representing the row, null if no more rows, false on error.
 */
function db_fetch_assoc($result) {
    if ($result instanceof mysqli_result) {
        return mysqli_fetch_assoc($result);
    }
    return false;
}

/**
 * Fetches all rows from a query result.
 *
 * @param mysqli_result $result The result object from mysqli_query.
 * @return array An array of associative arrays representing all rows. Empty array if no rows or error.
 */
function db_fetch_all($result) {
    $rows = [];
    if ($result instanceof mysqli_result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

/**
 * Gets the ID of the last inserted row.
 *
 * @return int|string The ID generated for an AUTO_INCREMENT column by the previous query.
 */
function db_insert_id() {
    global $conn;
    if ($conn) {
        return mysqli_insert_id($conn);
    }
    return 0;
}

/**
 * Gets the number of affected rows by the last query.
 * @return int Number of affected rows.
 */
function db_affected_rows() {
    global $conn;
    if ($conn) {
        return mysqli_affected_rows($conn);
    }
    return 0;
}

// Example of how to use sanitize_string, assuming $conn is your mysqli connection object
// $unsafe_variable = $_POST['user_input'];
// $safe_variable = sanitize_string($unsafe_variable);
// $sql = "INSERT INTO users (username) VALUES ('" . $safe_variable . "')";
// Then execute the query.

// Make sure to close the connection when appropriate, e.g., at the end of a script or request.
// register_shutdown_function('db_close'); // Automatically close DB connection at script end.

?>
