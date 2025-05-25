<?php
// Include database configuration
require_once '../db_config.php';

// Check if user is logged in
require_login();

header('Content-Type: application/json');

// Get filter parameters
$category_id = isset($_GET['category_id']) ? sanitize_input($conn, $_GET['category_id']) : null;
$search = isset($_GET['search']) ? sanitize_input($conn, $_GET['search']) : null;
$sort_by = isset($_GET['sort_by']) ? sanitize_input($conn, $_GET['sort_by']) : 'name';
$sort_order = isset($_GET['sort_order']) ? sanitize_input($conn, $_GET['sort_order']) : 'ASC';

// Valid sort options
$valid_sort_fields = ['name', 'price', 'created_at'];
$valid_sort_orders = ['ASC', 'DESC'];

// Validate sort parameters
if (!in_array($sort_by, $valid_sort_fields)) {
    $sort_by = 'name';
}

if (!in_array(strtoupper($sort_order), $valid_sort_orders)) {
    $sort_order = 'ASC';
}

// Build query
$sql = "SELECT p.product_id, p.farmer_id, p.category_id, p.name, p.description, 
         p.price, p.quantity, p.unit, p.image, p.created_at,
         c.name as category_name, u.full_name as farmer_name
         FROM products p
         JOIN categories c ON p.category_id = c.category_id
         JOIN users u ON p.farmer_id = u.user_id
         WHERE p.is_available = 1 AND p.quantity > 0";

// Add category filter if provided
if (!empty($category_id)) {
    $sql .= " AND p.category_id = ?";
}

// Add search filter if provided
if (!empty($search)) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ? OR u.full_name LIKE ?)";
}

// Add sorting
$sql .= " ORDER BY p.$sort_by $sort_order";

// Prepare statement
$stmt = $conn->prepare($sql);

// Bind parameters
if (!empty($category_id) && !empty($search)) {
    $search_param = "%$search%";
    $stmt->bind_param("issss", $category_id, $search_param, $search_param, $search_param, $search_param);
} elseif (!empty($category_id)) {
    $stmt->bind_param("i", $category_id);
} elseif (!empty($search)) {
    $search_param = "%$search%";
    $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
}

// Execute query
$stmt->execute();
$result = $stmt->get_result();

// Fetch all products
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

// Return products as JSON
echo json_encode(['success' => true, 'products' => $products]);

// Close statement and connection
$stmt->close();
$conn->close();
?>