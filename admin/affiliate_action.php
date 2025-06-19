<?php
/**
 * Backend Handler for Moderating Affiliates
 *
 * This script processes actions (approve, suspend, delete) for affiliates.
 */

// =================================================================
// 1. CORE SETUP AND SECURITY CHECKS
// =================================================================
require_once __DIR__ . '/../includes/app.php';
require_login();

// This script accepts GET requests with an action and ID.
$action = $_GET['action'] ?? null;
$affiliate_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Validate the inputs
if ($affiliate_id <= 0 || !in_array($action, ['approve', 'suspend', 'delete'])) {
    set_flash_message('Invalid action or affiliate ID.', 'danger');
    header('Location: /admin/affiliates.php');
    exit();
}

// =================================================================
// 2. UPDATE DATABASE BASED ON ACTION
// =================================================================

try {
    $sql = '';
    $params = [$affiliate_id];

    switch ($action) {
        case 'approve':
            $sql = "UPDATE affiliates SET status = 'approved' WHERE id = ?";
            set_flash_message('Affiliate has been approved successfully.', 'success');
            break;

        case 'suspend':
            $sql = "UPDATE affiliates SET status = 'suspended' WHERE id = ?";
            set_flash_message('Affiliate has been suspended.', 'warning');
            break;

        case 'delete':
            // Deleting an affiliate will also delete their commissions due to 'ON DELETE CASCADE'
            $sql = "DELETE FROM affiliates WHERE id = ?";
            set_flash_message('Affiliate and all their associated data have been permanently deleted.', 'success');
            break;
    }
    
    // Execute the query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    log_admin_activity("Performed action '{$action}' on Affiliate ID: {$affiliate_id}");

} catch (PDOException $e) {
    set_flash_message('An error occurred: ' . $e->getMessage(), 'danger');
    error_log("Affiliate action error for Affiliate #{$affiliate_id}: " . $e->getMessage());
}

// =================================================================
// 3. REDIRECT BACK TO THE AFFILIATES PAGE
// =================================================================

// Redirect back to the affiliates list, preserving the last viewed status tab if possible.
$redirect_url = '/admin/affiliates.php';
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