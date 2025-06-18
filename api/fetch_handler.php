<?php
/**
 * Central API Fetch Handler
 * This script acts as a router to call different API fetching functions.
 */

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

// --- Main Fetch Function ---
function fetchProductsFromPlatform($platform) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM affiliate_config WHERE api_name = :platform AND is_active = TRUE");
    $stmt->execute([':platform' => $platform]);
    $config = $stmt->fetch();

    if (!$config) {
        echo "Error: Configuration for '{$platform}' not found or is inactive.\n";
        return [];
    }

    $api_key = decrypt_data($config['api_key']);
    $api_secret = decrypt_data($config['api_secret']);
    $affiliate_tag = $config['affiliate_tag'];

    switch (strtolower($platform)) {
        case 'pexels':
            return callPexelsAPI('products', $api_key);
        case 'pixabay':
            return callPixabayAPI('electronics', $api_key, $affiliate_tag);
        default:
            echo "Error: No handler available for platform '{$platform}'.\n";
            return [];
    }
}

// --- Specific API Handler Functions ---
function callPexelsAPI($query, $api_key) {
    $url = "https://api.pexels.com/v1/search?query=" . urlencode($query) . "&per_page=5";
    $headers = ['Authorization: ' . $api_key];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
        // CRITICAL FIX: Removed CURLOPT_SSL_VERIFYPEER => false.
        // On a production server like Render, SSL verification should always be enabled.
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "cURL Error for Pexels: " . $error . "\n";
        return [];
    }

    $data = json_decode($response, true);
    echo "Successfully fetched " . count($data['photos'] ?? []) . " items from Pexels.\n";
    return $data['photos'] ?? [];
}

function callPixabayAPI($query, $api_key, $affiliate_tag) {
    $url = "https://pixabay.com/api/?key={$api_key}&q=" . urlencode($query) . "&image_type=photo&per_page=5";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        // CRITICAL FIX: Removed CURLOPT_SSL_VERIFYPEER => false.
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "cURL Error for Pixabay: " . $error . "\n";
        return [];
    }

    $data = json_decode($response, true);
    $standardized_products = [];

    if (!empty($data['hits'])) {
        foreach ($data['hits'] as $hit) {
            $standardized_products[] = [
                'title' => ucwords(implode(' ', array_slice(explode(',', $hit['tags']), 0, 3))),
                'description' => 'A high-quality image from Pixabay.',
                'image_url' => $hit['largeImageURL'],
                'platform' => 'Pixabay',
                'price' => round(rand(100, 2000) / 10, 2), // Dummy price
                'affiliate_link' => rtrim($hit['pageURL'], '/') . '/?tag=' . urlencode($affiliate_tag),
            ];
        }
    }
    
    echo "Successfully fetched and standardized " . count($standardized_products) . " items from Pixabay.\n";
    return $standardized_products;
}

// --- Example Usage (for direct testing from command line) ---
/*
To test this file, run from your project's root directory:
php api/fetch_handler.php

$platform_to_test = 'Pixabay'; // Change to 'Pexels' to test that
$fetched_products = fetchProductsFromPlatform($platform_to_test);

echo "<pre>";
print_r($fetched_products);
echo "</pre>";
*/
?>