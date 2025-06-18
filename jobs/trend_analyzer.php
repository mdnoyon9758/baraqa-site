<?php
// This script is intended to be run from the command line (CLI)
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

define('CRON_JOB_RUNNING', true);
set_time_limit(600); // 10 minutes
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Use __DIR__ to define a portable base path.
// Goes up one level from /jobs/ to the project root.
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/includes/functions.php'; // This also loads db_connect.php

echo "Trend Analyzer Started.\n";

try {
    // --- STEP 1: Fetch all published products ---
    $products_stmt = $pdo->query("SELECT id, rating, reviews_count, discount_percentage FROM products WHERE is_published = 1");
    $products = $products_stmt->fetchAll();
    
    if (!$products) {
        die("No published products found to analyze.\n");
    }

    echo "Found " . count($products) . " products to analyze.\n";
    
    // --- STEP 2: Prepare statements for performance ---
    // Get total clicks in the last 7 days for a product
    $clicks_sql = "SELECT COUNT(*) FROM affiliate_clicks WHERE product_id = :product_id AND click_time >= (CURRENT_DATE - INTERVAL '7 days')";
    $clicks_stmt = $pdo->prepare($clicks_sql);
    
    // Update the trend_score for a product
    $update_sql = "UPDATE products SET trend_score = :trend_score WHERE id = :id";
    $update_stmt = $pdo->prepare($update_sql);

    // --- STEP 3: Loop through each product and calculate new score ---
    $pdo->beginTransaction();
    $counter = 0;

    foreach ($products as $product) {
        // Fetch recent click count
        $clicks_stmt->execute(['product_id' => $product['id']]);
        $recent_clicks = (int)$clicks_stmt->fetchColumn();

        // Trend Score Calculation Logic
        // Weights can be adjusted
        $w_rating = 0.4;    // Product's inherent quality
        $w_reviews = 0.2;   // Social proof
        $w_discount = 0.1;  // Deal appeal
        $w_clicks = 0.3;    // Recent popularity/traffic

        $normalized_rating = ($product['rating'] / 5) * 100;
        
        // Normalize reviews count (logarithmic scale)
        $normalized_reviews = ($product['reviews_count'] > 0) ? (log10($product['reviews_count']) / log10(100000)) * 100 : 0;
        $normalized_reviews = min($normalized_reviews, 100);

        $normalized_discount = (float)$product['discount_percentage'];

        // Normalize recent clicks (logarithmic scale, maxed at 1000 clicks)
        $normalized_clicks = ($recent_clicks > 0) ? (log10($recent_clicks) / log10(1000)) * 100 : 0;
        $normalized_clicks = min($normalized_clicks, 100);

        // Calculate the final weighted score
        $score = ($normalized_rating * $w_rating) +
                 ($normalized_reviews * $w_reviews) +
                 ($normalized_discount * $w_discount) +
                 ($normalized_clicks * $w_clicks);

        $final_score = round($score, 2);

        // Update the product in the database
        $update_stmt->execute([
            'trend_score' => $final_score,
            'id' => $product['id']
        ]);
        
        $counter++;
        if ($counter % 100 == 0) {
            echo "  - Processed {$counter} products...\n";
        }
    }
    
    $pdo->commit();
    echo "Successfully analyzed and updated trend scores for {$counter} products.\n";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("DATABASE ERROR: " . $e->getMessage() . "\n");
}

echo "Trend Analyzer Finished.\n";
?>