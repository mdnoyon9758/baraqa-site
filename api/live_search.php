<?php
/**
 * Live Search API Endpoint.
 * Fetches product suggestions based on a user's query and responds with JSON.
 */

// Set headers for JSON response.
header('Content-Type: application/json');

// This API endpoint only needs the database connection, not the full app environment.
require_once __DIR__ . '/../includes/db_connect.php'; 

// Initialize a default, structured response.
$response = [
    'status' => 'error',
    'message' => 'Invalid request.',
    'products' => []
];

// Validate the search query parameter 'q'.
$query = trim($_GET['q'] ?? '');

if (empty($query)) {
    $response['message'] = 'Search query is missing.';
    echo json_encode($response);
    exit;
}

// Enforce a minimum query length to avoid excessive database load on very short queries.
if (strlen($query) < 2) {
    $response['status'] = 'info';
    $response['message'] = 'Query is too short. Minimum 2 characters required.';
    echo json_encode($response);
    exit;
}

// Execute the database query within a try-catch block for error handling.
try {
    // Prepare a statement to search for products where the title matches the query.
    // We select 'slug' for building SEO-friendly URLs on the frontend.
    // Results are ordered by relevance (trend_score) and limited for performance.
    $sql = "SELECT id, slug, title, image_url 
            FROM products 
            WHERE title LIKE :query AND is_published = 1 
            ORDER BY trend_score DESC, rating DESC
            LIMIT 7";

    $stmt = $pdo->prepare($sql);
    
    // Bind the search term with '%' wildcards for a partial match.
    $stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
    
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build the final response based on whether products were found.
    $response['status'] = 'success';
    if (!empty($products)) {
        $response['message'] = count($products) . ' products found.';
        $response['products'] = $products;
    } else {
        $response['message'] = 'No products found matching your query.';
    }

} catch (PDOException $e) {
    // If a database error occurs, send a 500 Internal Server Error status
    // and a generic error message. The detailed error is logged for the admin.
    http_response_code(500); 
    $response['status'] = 'error';
    $response['message'] = 'A database error occurred.';
    error_log("Live Search API PDOException: " . $e->getMessage());
}

// Send the final JSON response to the frontend.
echo json_encode($response);
?>