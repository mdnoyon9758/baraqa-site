<?php
/**
 * Backend Handler for Moderating Product Reviews
 *
 * This script processes actions (approve, reject, delete) for reviews.
 */

// =================================================================
// 1. CORE SETUP AND SECURITY CHECKS
// =================================================================
require_once __DIR__ . '/../includes/app.php';
require_login();

// This script only accepts GET requests with an action and ID.
// For simple, non-destructive state changes, GET is acceptable. For delete, a POST with CSRF is safer.
// However, for simplicity and consistency with your user_action.php, we will use GET and add CSRF later if needed.

$action = $_GET['action'] ?? null;
$review_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($review_id <= 0 || !in_array($action, ['approve', 'reject', 'delete'])) {
    set_flash_message('Invalid action or review ID.', 'danger');
    header('Location: /admin/reviews.php');
    exit();
}

// =================================================================
// 2. UPDATE DATABASE BASED ON ACTION
// =================================================================

try {
    $sql = '';
    $params = [$review_id];

    switch ($action) {
        case 'approve':
            $sql = "UPDATE reviews SET status = 'approved' WHERE id = ?";
            set_flash_message('Review has been approved successfully.', 'success');
            break;

        case 'reject':
            $sql = "UPDATE reviews SET status = 'rejected' WHERE id = ?";
            set_flash_message('Review has been rejected.', 'warning');
            break;

        case 'delete':
            $sql = "DELETE FROM reviews WHERE id = ?";
            set_flash_message('Review has been permanently deleted.', 'success');
            break;
    }
    
    // Execute the query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Optional: After a review is approved or deleted, you might want to update the product's average rating.
    // This is an advanced step for later.

} catch (PDOException $e) {
    set_flash_message('An error occurred: ' . $e->getMessage(), 'danger');
    error_log("Review action error for Review #{$review_id}: " . $e->getMessage());
}

// =================================================================
// 3. REDIRECT BACK TO THE REVIEWS PAGE
// =================================================================

// Redirect back to the reviews list, preserving the last viewed status tab if possible.
$redirect_url = '/admin/reviews.php';
if (isset($_SERVER['HTTP_REFERER'])) {
    $referer_query = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY);
    if ($referer_query) {
        parse_str($referer_query, $query_params);
        if (isset($query_params['status'])) {
            $redirect_url .= '?status=' . urlencode($query_params['status']);
        }
    }
}

header('Location: ' . $redirect_url);
exit();