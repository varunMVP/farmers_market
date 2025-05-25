<?php
// Start session
session_start();

// Include database configuration
require_once 'backend\db_config.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Basic validation
    if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password']) || 
        empty($_POST['role']) || empty($_POST['full_name'])) {
        $_SESSION['error_message'] = "All required fields must be filled";
        header("Location: ../../register.html");
        exit();
    }
    
    // Get form data and sanitize
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password']; // Will be hashed, so no need to sanitize
    $role = sanitizeInput($_POST['role']);
    $full_name = sanitizeInput($_POST['full_name']);
    $phone = isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : '';
    $address = isset($_POST['address']) ? sanitizeInput($_POST['address']) : '';
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Invalid email format";
        header("Location: ../../register.html");
        exit();
    }
    
    // Validate role
    if ($role !== 'farmer' && $role !== 'customer') {
        $_SESSION['error_message'] = "Invalid role selected";
        header("Location: ../../register.html");
        exit();
    }
    
    // Connect to database
    $conn = connectDB();
    
    // Check if username or email already exists
    $checkQuery = "SELECT * FROM users WHERE username = ? OR email = ?";
    $checkStmt = $conn->prepare($checkQuery);
    
    if (!$checkStmt) {
        handleDBError($conn, $checkQuery, "../../register.html");
    }
    
    $checkStmt->bind_param("ss", $username, $email);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if ($user['username'] === $username) {
            $_SESSION['error_message'] = "Username already exists";
        } else {
            $_SESSION['error_message'] = "Email already exists";
        }
        $checkStmt->close();
        $conn->close();
        header("Location: ../../register.html");
        exit();
    }
    $checkStmt->close();
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Prepare and execute insert query
    $insertQuery = "INSERT INTO users (username, email, password, role, full_name, phone, address) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertQuery);
    
    if (!$insertStmt) {
        handleDBError($conn, $insertQuery, "../../register.html");
    }
    
    $insertStmt->bind_param("sssssss", $username, $email, $hashed_password, $role, $full_name, $phone, $address);
    
    if ($insertStmt->execute()) {
        // Success
        $insertStmt->close();
        $conn->close();
        
        $_SESSION['success_message'] = "Registration successful! Please login.";
        header("Location: ../../login.html");
        exit();
    } else {
        // Error handling
        handleDBError($conn, $insertQuery, "../../register.html");
    }
    
} else {
    // If not POST request, redirect to registration page
    header("Location: ../../register.html");
    exit();
}
?>