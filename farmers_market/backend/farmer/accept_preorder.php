<?php
// Include database configuration
require_once '../db_config.php';

// Check if user is logged in and is a farmer
require_farmer();

header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

// Get the post data
$preorder_id = isset($_POST['preorder_id']) ? sanitize_input($conn, $_POST['preorder_id']) : '';
$action = isset($_POST['action']) ? sanitize_input($conn, $_POST['action']) : '';

// Validate input
if (empty($preorder_id) || empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Preorder ID and action are required.']);
    exit();
}

// Validate action (must be 'accept' or 'reject')
if ($action !== 'accept' && $action !== 'reject') {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit();
}

// Get farmer ID from session
$farmer_id = $_SESSION['user_id'];

// Check if the preorder belongs to this farmer
$sql = "SELECT * FROM preorders WHERE preorder_id = ? AND farmer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $preorder_id, $farmer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Preorder not found or not authorized.']);
    $stmt->close();
    exit();
}

$preorder = $result->fetch_assoc();

// Update preorder status
$status = ($action === 'accept') ? 'accepted' : 'rejected';
$sql = "UPDATE preorders SET status = ? WHERE preorder_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $status, $preorder_id);

if ($stmt->execute()) {
    // If accepted, send notification to customer (in a real system)
    if ($action === 'accept') {
        // In a real system, you would send an email or push notification here
        // For now, we'll just return success
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Preorder ' . ($action === 'accept' ? 'accepted' : 'rejected') . ' successfully.'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update preorder: ' . $conn->error]);
}

// Close statement and connection
$stmt->close();
$conn->close();
?>