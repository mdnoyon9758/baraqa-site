<?php
/**
 * Handles adding/removing products from the user's wishlist via AJAX.
 * The wishlist is stored in the PHP session.
 */

// Start the session to access or modify $_SESSION variables.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set the response content type to JSON.
header('Content-Type: application/json');

// Initialize a default response array.
$response = [
    'status'  => 'error',
    'message' => 'An invalid request occurred.',
    'action'  => 'none',
    'count'   => isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0
];

// Validate that the request is a POST request.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method. Only POST is allowed.';
    echo json_encode($response);
    exit;
}

// Validate that product_id is provided and is a positive integer.
if (!isset($_POST['product_id']) || !filter_var($_POST['product_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
    $response['message'] = 'Invalid or missing Product ID.';
    echo json_encode($response);
    exit;
}

$product_id = (int)$_POST['product_id'];

// Initialize the wishlist session array if it doesn't exist.
if (!isset($_SESSION['wishlist']) || !is_array($_SESSION['wishlist'])) {
    $_SESSION['wishlist'] = [];
}

// Find the key of the product in the wishlist array.
$key = array_search($product_id, $_SESSION['wishlist']);

if ($key !== false) {
    // If found, the product is in the wishlist. Remove it.
    unset($_SESSION['wishlist'][$key]);
    
    $response['status']  = 'success';
    $response['action']  = 'removed';
    $response['message'] = 'Product removed from wishlist.';
} else {
    // If not found, the product is not in the wishlist. Add it.
    $_SESSION['wishlist'][] = $product_id;
    
    $response['status']  = 'success';
    $response['action']  = 'added';
    $response['message'] = 'Product added to wishlist.';
}

// Re-index the array to maintain a clean, 0-indexed array.
$_SESSION['wishlist'] = array_values($_SESSION['wishlist']);

// Update the final count in the response.
$response['count'] = count($_SESSION['wishlist']);

// Send the JSON response back to the frontend.
echo json_encode($response);
?>