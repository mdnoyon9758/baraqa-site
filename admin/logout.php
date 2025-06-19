<?php
/**
 * Admin Logout Script
 *
 * This script handles the secure logout process for an authenticated admin.
 */

// Load the core application file which includes logging and session functions.
require_once __DIR__ . '/../includes/app.php';

// Log the logout action before destroying the session.
if (isset($_SESSION['admin_id'])) {
    log_admin_activity('Admin logged out successfully.');
}

// --- Standard Logout Procedure ---

// 1. Unset all session variables.
$_SESSION = [];

// 2. Delete the session cookie to ensure the session is completely terminated.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destroy the session.
session_destroy();

// 4. Redirect to the login page with a success message.
// We use our custom flash message function for this.
// Note: set_flash_message() will handle starting a new session if needed.
set_flash_message("You have been logged out successfully.", "success");

// Redirect to the login page.
header("Location: /admin/login.php");
exit;