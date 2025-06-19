<?php
/**
 * Backend Handler for Updating Commission Status
 *
 * This script processes actions (mark_paid, mark_voided) for commissions.
 */

// =================================================================
// 1. CORE SETUP AND SECURITY CHECKS
// =================================================================
require_once __DIR__ . '/../includes/app.php';
require_login();

// This script accepts GET requests with an action and ID.
$action = $_GET['action'] ?? null;
$commission_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Validate the inputs
if ($commission_id <= 0 || !in_array($action, ['mark_paid', 'mark_voided'])) {
    set_flash_message('Invalid action or commission ID.', 'danger');
    header('Location: /admin/commissions.php');
    exit();
}

// =================================================================
// 2. UPDATE DATABASE BASED ON ACTION
// =================================================================

try {
    $sql = '';
    $params = [$commission_id];

    switch ($action) {
        case 'mark_paid':
            $sql = "UPDATE commissions SET status = 'paid' WHERE id = ? AND status = 'unpaid'";
            set_flash_message('Commission has been marked as paid.', 'success');
            break;

        case 'mark_voided':
            $sql = "UPDATE commissions SET status = 'voided' WHERE id = ? AND status = 'unpaid'";
            set_flash_message('Commission has been voided.', 'warning');
            break;
    }
    
    // Execute the query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() > 0) {
        log_admin_activity("Performed action '{$action}' on Commission ID: {$commission_id}");
    } else {
        set_flash_message('Action could not be completed. The commission may have already been processed.', 'info');
    }

} catch (PDOException $e) {
    set_flash_message('An error occurred: ' . $e->getMessage(), 'danger');
    error_log("Commission action error for Commission #{$commission_id}: " . $e->getMessage());
}

// =================================================================
// 3. REDIRECT BACK TO THE COMMISSIONS PAGE
// =================================================================

// Redirect back to the commissions list, preserving the last viewed status tab if possible.
$redirect_url = '/admin/commissions.php';
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