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
$items = isset($_POST['items']) ? json_decode($_POST['items'], true) : [];
$delivery_address = isset($_POST['delivery_address']) ? sanitize_input($conn, $_POST['delivery_address']) : '';
$payment_method = isset($_POST['payment_method']) ? sanitize_input($conn, $_POST['payment_method']) : '';

// Validate input
if (empty($items) || !is_array($items)) {
    echo json_encode(['success' => false, 'message' => 'No items in the order.']);
    exit();
}

if (empty($delivery_address)) {
    echo json_encode(['success' => false, 'message' => 'Delivery address is required.']);
    exit();
}

if (empty($payment_method)) {
    echo json_encode(['success' => false, 'message' => 'Payment method is required.']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Calculate total amount and validate items
    $total_amount = 0;
    $order_items = [];
    
    foreach ($items as $item) {
        // Check required fields
        if (!isset($item['product_id']) || !isset($item['quantity'])) {
            throw new Exception('Invalid item format.');
        }
        
        $product_id = sanitize_input($conn, $item['product_id']);
        $quantity = sanitize_input($conn, $item['quantity']);
        
        // Validate product exists and has enough stock
        $product_query = "SELECT p.product_id, p.farmer_id, p.price, p.quantity as available_quantity, p.unit 
                         FROM products p 
                         WHERE p.product_id = ? AND p.is_available = 1";
        $stmt = $conn->prepare($product_query);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product_result = $stmt->get_result();
        
        if ($product_result->num_rows === 0) {
            throw new Exception('Product not found or not available.');
        }
        
        $product = $product_result->fetch_assoc();
        
        if ($product['available_quantity'] < $quantity) {
            throw new Exception('Not enough stock for product ID ' . $product_id);
        }
        
        // Calculate subtotal for this item
        $subtotal = $product['price'] * $quantity;
        $total_amount += $subtotal;
        
        // Add to order items
        $order_items[] = [
            'product_id' => $product_id,
            'farmer_id' => $product['farmer_id'],
            'quantity' => $quantity,
            'price' => $product['price'],
            'subtotal' => $subtotal
        ];
        
        // Update product quantity
        $new_quantity = $product['available_quantity'] - $quantity;
        $update_query = "UPDATE products SET quantity = ? WHERE product_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("di", $new_quantity, $product_id);
        $stmt->execute();
    }
    
    // Insert order
    $order_query = "INSERT INTO orders (customer_id, total_amount, status, payment_method, payment_status, delivery_address) 
                   VALUES (?, ?, 'pending', ?, 'pending', ?)";
    $stmt = $conn->prepare($order_query);
    $stmt->bind_param("idss", $customer_id, $total_amount, $payment_method, $delivery_address);
    $stmt->execute();
    $order_id = $conn->insert_id;
    
    // Insert order items
    $item_query = "INSERT INTO order_items (order_id, product_id, farmer_id, quantity, price, subtotal) 
                  VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($item_query);
    
    foreach ($order_items as $item) {
        $stmt->bind_param("iiiddd", $order_id, $item['product_id'], $item['farmer_id'], $item['quantity'], $item['price'], $item['subtotal']);
        $stmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Order placed successfully.',
        'order_id' => $order_id,
        'total_amount' => $total_amount
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Close connection
$conn->close();
?>