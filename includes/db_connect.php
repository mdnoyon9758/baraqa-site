<?php
// includes/db_connect.php

// For Render deployment, fetch credentials from environment variables
if (getenv('DB_HOST')) {
    $db_host = getenv('DB_HOST');
    $db_name = getenv('DB_NAME');
    $db_user = getenv('DB_USER');
    $db_pass = getenv('DB_PASSWORD');
    $db_port = getenv('DB_PORT') ?: 5432;

    // DSN for PostgreSQL
    $dsn = "pgsql:host={$db_host};port={$db_port};dbname={$db_name}";
} else {
    // Fallback for local XAMPP development (MySQL)
    $db_host = 'localhost';
    $db_name = 'bs'; // আপনার লোকাল ডাটাবেসের নাম
    $db_user = 'root';
    $db_pass = ''; // আপনার লোকাল ডাটাবেসের পাসওয়ার্ড
    
    // DSN for MySQL
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
}

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // Don't show detailed errors on a live site for security reasons
    error_log("Database Connection Error: " . $e->getMessage());
    // Display a generic, user-friendly error message
    die("Database Connection Failed: Unable to connect to the database. Please try again later.");
}
?>