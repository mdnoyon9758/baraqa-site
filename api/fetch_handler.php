<?php
/**
 * Central API Fetch Handler
 * This script acts as a router to call different API fetching functions
 * based on the requested platform. It's designed to be called by
 * other scripts (like a manual fetch trigger in the admin panel) rather than directly.
 *
 * For now, this file will contain the logic, but it's not directly executable via URL
 * without a proper request structure.
 */

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

// --- Main Fetch Function ---
/**
 * Fetches products from a specified platform using its API configuration.
 *
 * @param string $platform The name of the platform to fetch from (e.g., 'Amazon', 'Pexels').
 * @return array An array of standardized product data.
 */
function fetchProductsFromPlatform($platform) {
    global $pdo;

    // 1. Get API configuration for the requested platform from the database
    $stmt = $pdo->prepare("SELECT * FROM affiliate_config WHERE api_name = :platform AND is_active = 1");
    $stmt->execute([':platform' => $platform]);
    $config = $stmt->fetch();

    if (!$config) {
        echo "Error: Configuration for '{$platform}' not found or is inactive.\n";
        return [];
    }

    // 2. Decrypt sensitive data
    $api_key = decrypt_data($config['api_key']);
    $api_secret = decrypt_data($config['api_secret']);
    $affiliate_tag = $config['affiliate_tag'];

    // 3. Call the specific handler function for the platform
    switch (strtolower($platform)) {
        case 'pexels':
            // This is just an example of how you could use it.
            // We'll return a generic structure, not actual product data.
            return callPexelsAPI('products', $api_key);
            
        case 'pixabay':
            // Example for Pixabay
            return callPixabayAPI('electronics', $api_key, $affiliate_tag);

        // Add cases for other APIs like 'Amazon', 'Alibaba', etc.
        // case 'amazon':
        //     return callAmazonAPI($api_key, $api_secret, $affiliate_tag);

        default:
            echo "Error: No handler available for platform '{$platform}'.\n";
            return [];
    }
}


// --- Specific API Handler Functions ---

/**
 * Example function to call the Pexels API.
 * In a real application, you'd process the response into your standard product format.
 * This is simplified for demonstration.
 *
 * @param string $query The search query.
 * @param string $api_key The Pexels API key.
 * @return array
 */
function callPexelsAPI($query, $api_key) {
    $url = "https://api.pexels.com/v1/search?query=" . urlencode($query) . "&per_page=5";
    $headers = ['Authorization: ' . $api_key];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false, // For local XAMPP
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    
    // In a real app, you would loop through $data['photos'] and map them to your product structure.
    // For now, we just return the raw data to show it's working.
    echo "Successfully fetched " . count($data['photos'] ?? []) . " items from Pexels.\n";
    return $data['photos'] ?? [];
}


/**
 * Example function to call the Pixabay API.
 *
 * @param string $query The search query.
 * @param string $api_key The Pixabay API key.
 * @param string $affiliate_tag Your affiliate tag for building links.
 * @return array A list of standardized product arrays.
 */
function callPixabayAPI($query, $api_key, $affiliate_tag) {
    $url = "https://pixabay.com/api/?key={$api_key}&q=" . urlencode($query) . "&image_type=photo&per_page=5";
    $ch = curl_init();
    curl_setopt_array($ch, [CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_SSL_VERIFYPEER => false]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    $standardized_products = [];

    if (!empty($data['hits'])) {
        foreach ($data['hits'] as $hit) {
            // Map the API response to your standard product structure
            $standardized_products[] = [
                'title' => ucwords(implode(' ', array_slice(explode(',', $hit['tags']), 0, 3))),
                'description' => 'A high-quality image from Pixabay.',
                'image_url' => $hit['largeImageURL'],
                'platform' => 'Pixabay',
                'price' => round(rand(100, 2000) / 10, 2), // Dummy price
                'affiliate_link' => $hit['pageURL'] . '?tag=' . $affiliate_tag,
                // ... other fields
            ];
        }
    }
    
    echo "Successfully fetched and standardized " . count($standardized_products) . " items from Pixabay.\n";
    return $standardized_products;
}


// --- Example Usage (for direct testing from command line) ---
/*
To test this file, you can uncomment the following lines and run from your terminal:
php C:\xampp\htdocs\bs\api\fetch_handler.php

$platform_to_test = 'Pixabay'; // Change to 'Pexels' to test that
$fetched_products = fetchProductsFromPlatform($platform_to_test);
echo "<pre>";
print_r($fetched_products);
echo "</pre>";
*/
?>