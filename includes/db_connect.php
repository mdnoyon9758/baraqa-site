<?php
/**
 * This file establishes the database connection using PDO.
 * It creates a single, reusable $pdo object for the entire application.
 */

// 1. Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'bs_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// 2. PDO Connection Options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// 3. Data Source Name (DSN)
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

// 4. Create PDO Instance within a try-catch block
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // On connection failure, stop the script and display a clean error message.
    // This prevents leaking sensitive information in a production environment.
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(503); // Service Unavailable
    die("Database Connection Failed: Unable to connect to the database. Please try again later.");
    // For debugging, you can use: die("Database Connection Failed: " . $e->getMessage());
}
?>