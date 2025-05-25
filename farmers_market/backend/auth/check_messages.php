<?php
session_start();
header('Content-Type: application/json');

$response = [];

// Check for error messages
if (isset($_SESSION['error_message'])) {
    $response['error'] = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Check for success messages
if (isset($_SESSION['success_message'])) {
    $response['success'] = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

echo json_encode($response);
?>