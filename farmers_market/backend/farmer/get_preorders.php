<?php
// Include database configuration
require_once __DIR__ . '/../backend/db_config.php';

// Check if user is logged in and is a farmer
require_farmer();

header('Content-Type: application/json');

// Get farmer ID from session
$farmer_id = $_SESSION['user_id'];

// Filter parameters
$status = isset($_GET['status']) ? sanitize_input($conn, $_GET['status']) : null;

// Build query
$sql = "SELECT p.preorder_id, p.customer_id, p.product_id, p.category_id, p.quantity, p.unit, 
         p.request_details, p.status, p.created_at, c.name as category_name, 
         u.full_name as customer_name, u.phone as customer_phone, 
         prod.name as product_name
         FROM preorders p
         JOIN users u ON p.customer_id = u.user_id
         JOIN categories c ON p.category_id = c.category_id
         LEFT JOIN products prod ON p.product_id = prod.product_id
         WHERE p.farmer_id = ?";

// Add status filter if provided
if (!empty($status)) {
    $sql .= " AND p.status = ?";
}

$sql .= " ORDER BY p.created_at DESC";

// Prepare and execute query
$stmt = $conn->prepare($sql);

if (!empty($status)) {
    $stmt->bind_param("is", $farmer_id, $status);
} else {
    $stmt->bind_param("i", $farmer_id);
}

$stmt->execute();
$result = $stmt->get_result();

// Fetch all preorders
$preorders = [];
while ($row = $result->fetch_assoc()) {
    $preorders[] = $row;
}

// Return preorders as JSON
echo json_encode(['success' => true, 'preorders' => $preorders]);

// Close statement and connection
$stmt->close();
$conn->close();
?>