<?php
// We need functions.php to access log_admin_activity() and session management.
// The functions.php file already handles session_start().
require_once __DIR__ . '/../includes/functions.php';

// Log the logout action before destroying the session.
// We check if admin_id exists to ensure we are logging out a valid session.
if (isset($_SESSION['admin_id'])) {
    log_admin_activity('Admin logged out successfully.');
}

// --- Standard Logout Procedure ---

// 1. Unset all of the session variables.
$_SESSION = [];

// 2. If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Finally, destroy the session.
session_destroy();

// 4. Redirect to login page with a success message.
// We need to start a new, clean session to store the flash message.
session_start();
$_SESSION['success_message'] = "You have been logged out successfully.";
header("Location: login.php");
exit;
?>