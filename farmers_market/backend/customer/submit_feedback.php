<?php
// Include database configuration
require_once '../db_config.php';

// Check if user is logged in and is a customer
require_customer();

header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

// Get customer ID from session
$customer_id = $_SESSION['user_id'];

// Get POST data
$farmer_id = isset($_POST['farmer_id']) ? sanitize_input($conn, $_POST['farmer_id']) : '';
$product_id = isset($_POST['product_id']) ? sanitize_input($conn, $_POST['product_id']) : null;
$order_id = isset($_POST['order_id']) ? sanitize_input($conn, $_POST['order_id']) : null;
$rating = isset($_POST['rating']) ? sanitize_input($conn, $_POST['rating']) : '';
$comment = isset($_POST['comment']) ? sanitize_input($conn, $_POST['comment']) : '';

// Validate input
if (empty($farmer_id)) {
    echo json_encode(['success' => false, 'message' => 'Farmer ID is required.']);
    exit();
}

if (empty($rating) || !is_numeric($rating) || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5.']);
    exit();
}

// Validate farmer exists
$farmer_query = "SELECT user_id FROM users WHERE user_id = ? AND role = 'farmer'";
$stmt = $conn->prepare($farmer_query);
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$farmer_result = $stmt->get_result();

if ($farmer_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Farmer not found.']);
    $stmt->close();
    exit();
}

// Validate product if provided
if (!empty($product_id)) {
    $product_query = "SELECT product_id FROM products WHERE product_id = ? AND farmer_id = ?";
    $stmt = $conn->prepare($product_query);
    $stmt->bind_param("ii", $product_id, $farmer_id);
    $stmt->execute();
    $product_result = $stmt->get_result();
    
    if ($product_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Product not found or does not belong to this farmer.']);
        $stmt->close();
        exit();
    }
}

// Validate order if provided
if (!empty($order_id)) {
    $order_query = "SELECT o.order_id FROM orders o 
                   JOIN order_items oi ON o.order_id = oi.order_id 
                   WHERE o.order_id = ? AND o.customer_id = ? AND oi.farmer_id = ?";
    $stmt = $conn->prepare($order_query);
    $stmt->bind_param("iii", $order_id, $customer_id, $farmer_id);
    $stmt->execute();
    $order_result = $stmt->get_result();
    
    if ($order_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Order not found or does not match customer and farmer.']);
        $stmt->close();
        exit();
    }
}

// Insert feedback
$sql = "INSERT INTO feedback (customer_id, farmer_id, product_id, order_id, rating, comment) 
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiiss", $customer_id, $farmer_id, $product_id, $order_id, $rating, $comment);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'message' => 'Feedback submitted successfully.',
        'feedback_id' => $conn->insert_id
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit feedback: ' . $conn->error]);
}

// Close statement and connection
$stmt->close();
$conn->close();
?>