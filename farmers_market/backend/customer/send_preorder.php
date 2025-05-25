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
$category_id = isset($_POST['category_id']) ? sanitize_input($conn, $_POST['category_id']) : '';
$product_id = isset($_POST['product_id']) ? sanitize_input($conn, $_POST['product_id']) : null;
$quantity = isset($_POST['quantity']) ? sanitize_input($conn, $_POST['quantity']) : '';
$unit = isset($_POST['unit']) ? sanitize_input($conn, $_POST['unit']) : '';
$request_details = isset($_POST['request_details']) ? sanitize_input($conn, $_POST['request_details']) : '';

// Validate input
if (empty($farmer_id)) {
    echo json_encode(['success' => false, 'message' => 'Farmer ID is required.']);
    exit();
}

if (empty($category_id)) {
    echo json_encode(['success' => false, 'message' => 'Category ID is required.']);
    exit();
}

if (empty($quantity) || !is_numeric($quantity) || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Valid quantity is required.']);
    exit();
}

if (empty($unit)) {
    echo json_encode(['success' => false, 'message' => 'Unit is required.']);
    exit();
}

// Validate farmer exists and is a farmer
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

// Validate category exists
$category_query = "SELECT category_id FROM categories WHERE category_id = ?";
$stmt = $conn->prepare($category_query);
$stmt->bind_param("i", $category_id);
$stmt->execute();
$category_result = $stmt->get_result();

if ($category_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Category not found.']);
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

// Insert preorder
$sql = "INSERT INTO preorders (customer_id, farmer_id, product_id, category_id, quantity, unit, request_details) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiidsss", $customer_id, $farmer_id, $product_id, $category_id, $quantity, $unit, $request_details);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'message' => 'Preorder request sent successfully.',
        'preorder_id' => $conn->insert_id
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send preorder request: ' . $conn->error]);
}

// Close statement and connection
$stmt->close();
$conn->close();
?>