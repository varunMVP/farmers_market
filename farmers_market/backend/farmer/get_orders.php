<?php
// Include database configuration
require_once '../db_config.php';

// Check if user is logged in and is a farmer
require_farmer();

header('Content-Type: application/json');

// Get farmer ID from session
$farmer_id = $_SESSION['user_id'];

// Filter parameters
$status = isset($_GET['status']) ? sanitize_input($conn, $_GET['status']) : null;

// Build query to get all order items for this farmer
$sql = "SELECT oi.order_item_id, oi.order_id, oi.product_id, oi.quantity, oi.price, oi.subtotal,
         o.customer_id, o.status as order_status, o.payment_status, o.created_at as order_date,
         p.name as product_name, p.unit,
         u.full_name as customer_name, u.phone as customer_phone, u.address as customer_address
         FROM order_items oi
         JOIN orders o ON oi.order_id = o.order_id
         JOIN products p ON oi.product_id = p.product_id
         JOIN users u ON o.customer_id = u.user_id
         WHERE oi.farmer_id = ?";

// Add status filter if provided
if (!empty($status)) {
    $sql .= " AND o.status = ?";
}

$sql .= " ORDER BY o.created_at DESC";

// Prepare and execute query
$stmt = $conn->prepare($sql);

if (!empty($status)) {
    $stmt->bind_param("is", $farmer_id, $status);
} else {
    $stmt->bind_param("i", $farmer_id);
}

$stmt->execute();
$result = $stmt->get_result();

// Fetch all orders
$orders = [];
while ($row = $result->fetch_assoc()) {
    // Group by order_id
    $order_id = $row['order_id'];
    if (!isset($orders[$order_id])) {
        $orders[$order_id] = [
            'order_id' => $order_id,
            'customer_name' => $row['customer_name'],
            'customer_phone' => $row['customer_phone'],
            'customer_address' => $row['customer_address'],
            'order_status' => $row['order_status'],
            'payment_status' => $row['payment_status'],
            'order_date' => $row['order_date'],
            'items' => []
        ];
    }
    
    // Add item to order
    $orders[$order_id]['items'][] = [
        'order_item_id' => $row['order_item_id'],
        'product_id' => $row['product_id'],
        'product_name' => $row['product_name'],
        'quantity' => $row['quantity'],
        'unit' => $row['unit'],
        'price' => $row['price'],
        'subtotal' => $row['subtotal']
    ];
}

// Convert to indexed array
$orders = array_values($orders);

// Return orders as JSON
echo json_encode(['success' => true, 'orders' => $orders]);

// Close statement and connection
$stmt->close();
$conn->close();
?>