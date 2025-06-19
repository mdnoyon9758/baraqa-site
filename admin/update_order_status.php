<?php
/**
 * Backend Handler for Updating Order Status
 *
 * This script processes the form submission from view_order.php to update an order's status.
 * It performs security checks, updates the database, and redirects back with a message.
 */

// =================================================================
// 1. CORE SETUP AND SECURITY CHECKS
// =================================================================

// Load the core application file which handles sessions, db, and all essential functions
require_once __DIR__ . '/../includes/app.php';

// Ensure the user is an authenticated admin
require_login();

// This script only accepts POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('This script only accepts POST requests.');
}

// Verify the CSRF token
if (!verify_csrf_token($_POST['csrf_token'])) {
    set_flash_message('CSRF token mismatch. Action aborted for security reasons.', 'danger');
    header('Location: /admin/orders.php'); // Redirect to the main orders list on token failure
    exit;
}

// =================================================================
// 2. VALIDATE INPUT AND UPDATE DATABASE
// =================================================================

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$new_status = $_POST['status'] ?? '';

// Define a whitelist of allowed statuses to prevent arbitrary data injection
$allowed_statuses = ['pending', 'processing', 'completed', 'cancelled'];

if ($order_id > 0 && in_array($new_status, $allowed_statuses)) {
    try {
        // Prepare and execute the update query
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);

        // Check if the update was successful
        if ($stmt->rowCount() > 0) {
            set_flash_message("Order #" . $order_id . " status has been updated to '" . ucfirst($new_status) . "'.", 'success');
            log_admin_activity("Updated status for Order #{$order_id} to '{$new_status}'.");
        } else {
            set_flash_message("No changes were made. The order status might already be '" . ucfirst($new_status) . "'.", 'info');
        }

    } catch (PDOException $e) {
        set_flash_message('Database error: Could not update order status. ' . $e->getMessage(), 'danger');
        error_log("Order status update error for Order #{$order_id}: " . $e->getMessage());
    }
} else {
    // If input is invalid, set an error message
    set_flash_message('Invalid order ID or status provided.', 'danger');
}

// =================================================================
// 3. REDIRECT BACK TO THE ORDER VIEW PAGE
// =================================================================

// Redirect back to the specific order's detail page
$redirect_url = '/admin/view_order.php?id=' . $order_id;
if ($order_id <= 0) {
    // If the order ID was invalid, redirect to the main list instead
    $redirect_url = '/admin/orders.php';
}

header('Location: ' . $redirect_url);
exit();