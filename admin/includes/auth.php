<?php
/**
 * This file is the single entry point for security and common functions for all admin pages.
 * It must be the first file required on any secure page in the /admin/ directory.
 */

// Ensure a session is active. The functions.php file also has this check,
// but it's good practice to have it here as well, as this is the security entry point.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load core functions and the database connection.
// The path is crucial: from /bs/admin/includes/ go up two levels to /bs/ then into /includes/
require_once __DIR__ . '/../../includes/functions.php';

// Enforce login for the page.
// The require_login() function (defined in functions.php) will handle the
// redirection to login.php if the user is not authenticated, and then exit the script.
require_login();

// Generate a CSRF token for any forms that might be on the page.
// This token should be included as a hidden field in all POST forms.
$csrf_token = generate_csrf_token();