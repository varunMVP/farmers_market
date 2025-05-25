<?php
/**
 * Database Configuration File
 * 
 * This file handles all database connection functionality for the Farmers Market application.
 */

// Enable error display during development
// Comment these out in production environment
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection details
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'farmers_market');

/**
 * Create and return a database connection
 * 
 * @return mysqli A mysqli connection object
 */
function connectDB() {
    // Create connection without specifying the database first
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Check if database exists
    $result = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    
    if ($result->num_rows == 0) {
        // Database doesn't exist, create it
        $sql = "CREATE DATABASE " . DB_NAME;
        if ($conn->query($sql) === TRUE) {
            echo "Database created successfully<br>";
        } else {
            die("Error creating database: " . $conn->error);
        }
    }
    
    // Select the database
    $conn->select_db(DB_NAME);
    
    // Set charset to ensure proper handling of special characters
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

/**
 * Sanitize input data to prevent SQL injection and XSS attacks
 * 
 * @param string $data The input data to sanitize
 * @return string The sanitized data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Handle database errors with proper logging and user feedback
 * 
 * @param mysqli $conn The database connection
 * @param string $query Optional query string that caused the error
 * @param string $redirectURL URL to redirect to after error
 */
function handleDBError($conn, $query = "", $redirectURL = "../index.html") {
    // Log the error (in a real application, use a proper logging system)
    error_log("Database Error: " . $conn->error . " in query: " . $query);
    
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Set error message in session
    $_SESSION['error_message'] = "Something went wrong. Please try again later.";
    
    // Redirect to appropriate page
    header("Location: " . $redirectURL);
    exit();
}

/**
 * Close database connection properly
 * 
 * @param mysqli $conn The database connection to close
 */
function closeDB($conn) {
    if ($conn) {
        $conn->close();
    }
}

/**
 * Execute a query with prepared statements
 * 
 * @param string $sql The SQL query with placeholders
 * @param string $types The types of parameters (i=integer, s=string, d=double, b=blob)
 * @param array $params Array of parameters to bind
 * @return mysqli_stmt|false The prepared statement or false on failure
 */
function executeQuery($sql, $types = "", $params = []) {
    $conn = connectDB();
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        handleDBError($conn, $sql);
        return false;
    }
    
    // Bind parameters if any
    if (!empty($params) && !empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    
    // Execute statement
    if (!$stmt->execute()) {
        handleDBError($conn, $sql);
        return false;
    }
    
    return $stmt;
}

/**
 * Get the last inserted ID
 * 
 * @return int The ID of the last inserted row
 */
function getLastInsertId() {
    $conn = connectDB();
    $lastId = $conn->insert_id;
    closeDB($conn);
    return $lastId;
}
?>