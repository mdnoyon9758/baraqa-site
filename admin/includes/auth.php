<?php
/**
 * Security & Authentication Entry Point for Admin Panel
 *
 * This file must be the first file required on any secure admin page.
 * It handles session management, loads the core application, and enforces authentication.
 */

// Load the core application file. This single file is responsible for:
// 1. Starting a session safely.
// 2. Connecting to the database (via db_connect.php).
// 3. Loading all global helper functions (via functions.php).
// The path goes up two levels from /admin/includes/ to the project root.
require_once __DIR__ . '/../../includes/app.php';

// Enforce admin login.
// The require_login() function (from functions.php) will check for an active admin session.
// If the user is not authenticated, it will redirect them to the login page and stop script execution.
require_login();

// Generate a CSRF token to be used in all POST forms on the page.
// This helps protect against Cross-Site Request Forgery attacks.
$csrf_token = generate_csrf_token();