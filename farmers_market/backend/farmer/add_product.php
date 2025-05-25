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
$category_id = isset($_POST['category_id']) ? sanitize_input($conn, $_POST['category_id']) : '';
$name = isset($_POST['name']) ? sanitize_input($conn, $_POST['name']) : '';
$description = isset($_POST['description']) ? sanitize_input($conn, $_POST['description']) : '';
$price = isset($_POST['price']) ? sanitize_input($conn, $_POST['price']) : '';
$quantity = isset($_POST['quantity']) ? sanitize_input($conn, $_POST['quantity']) : '';
$unit = isset($_POST['unit']) ? sanitize_input($conn, $_POST['unit']) : '';

// Validate input
if (empty($category_id) || empty($name) || empty($price) || empty($quantity) || empty($unit)) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled.']);
    exit();
}

// Validate numeric values
if (!is_numeric($price) || $price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Price must be a positive number.']);
    exit();
}

if (!is_numeric($quantity) || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Quantity must be a positive number.']);
    exit();
}

// Get farmer ID from session
$farmer_id = $_SESSION['user_id'];

// Initialize image path variable
$image_path = null;

// Handle image upload if provided
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../../uploads/products/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename
    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $file_name = uniqid('product_') . '.' . $file_extension;
    $target_file = $upload_dir . $file_name;
    
    // Validate file type
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array(strtolower($file_extension), $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Only JPG, JPEG, PNG & GIF files are allowed.']);
        exit();
    }
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
        $image_path = 'uploads/products/' . $file_name;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload image.']);
        exit();
    }
}

// Insert product into database
$sql = "INSERT INTO products (farmer_id, category_id, name, description, price, quantity, unit, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iissddsss", $farmer_id, $category_id, $name, $description, $price, $quantity, $unit, $image_path);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Product added successfully.', 'product_id' => $conn->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add product: ' . $conn->error]);
}

// Close statement and connection
$stmt->close();
$conn->close();
?>