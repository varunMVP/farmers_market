<?php
// Include database configuration
require_once 'backend\db_config.php';

header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

// Get the post data
$username = isset($_POST['username']) ? sanitize_input($conn, $_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// Validate input
if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
    exit();
}

// Check if user exists
$sql = "SELECT user_id, username, password, role, full_name FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
    exit();
}

// Fetch user data
$user = $result->fetch_assoc();

// Verify password
if (!password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
    exit();
}

// Start session and set user data
session_start();
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];
$_SESSION['full_name'] = $user['full_name'];

// Return success response with redirect URL based on role
$redirect_url = ($user['role'] === 'farmer') ? '/frontend/farmer/dashboard.html' : '/frontend/customer/dashboard.html';
echo json_encode(['success' => true, 'message' => 'Login successful.', 'redirect' => $redirect_url]);

// Close statement and connection
$stmt->close();
$conn->close();
?>