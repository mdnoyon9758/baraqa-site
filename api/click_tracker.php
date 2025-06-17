<?php
// Set headers for JSON response and prevent caching
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// This API endpoint only needs the database connection.
// Including the full app.php is unnecessary and slower.
require_once __DIR__ . '/../includes/db_connect.php'; 

// Initialize a default response
$response = [
    'status' => 'error',
    'message' => 'Invalid request.'
];

// 1. Validate the request method and input
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['product_id'])) {
    http_response_code(400); // Bad Request
    $response['message'] = 'Invalid request method or missing product ID.';
    echo json_encode($response);
    exit;
}

// 2. Sanitize and validate the product ID
$product_id = filter_var($_POST['product_id'], FILTER_VALIDATE_INT);

if ($product_id === false || $product_id <= 0) {
    http_response_code(400); // Bad Request
    $response['message'] = 'Invalid product ID specified.';
    echo json_encode($response);
    exit;
}

// 3. Gather user information for logging
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';

// 4. Insert the click record into the database using PDO
try {
    $sql = "INSERT INTO affiliate_clicks (product_id, ip_address, user_agent) VALUES (:product_id, :ip_address, :user_agent)";
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([
        ':product_id' => $product_id,
        ':ip_address' => $ip_address,
        ':user_agent' => $user_agent
    ]);

    // 5. Send a success response
    $response['status'] = 'success';
    $response['message'] = 'Click tracked successfully.';
    http_response_code(200); // OK

} catch (PDOException $e) {
    // If the product_id doesn't exist (foreign key constraint fails) or another DB error occurs.
    http_response_code(500); // Internal Server Error
    $response['message'] = 'Database error. Could not track the click.';
    // In a production environment, you would log the detailed error instead of exposing it.
    error_log("Click Tracker PDOException: " . $e->getMessage());
}

// 6. Echo the final JSON response
echo json_encode($response);
?>