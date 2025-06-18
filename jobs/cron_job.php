<?php
// File: jobs/cron_job.php

// Define a constant to signal that this is a cron job execution
define('CRON_JOB_RUNNING', true);

// This script should only be run from the command line, not a browser
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Forbidden: This script is for command-line execution only.");
}

// Allow the script to run for a long time (e.g., 15 minutes)
set_time_limit(900);

// Load the core application files
require_once __DIR__ . '/../includes/functions.php'; // This also loads db_connect.php
require_once __DIR__ . '/../config.php'; // For encryption keys

echo "Cron Job Started at " . date('Y-m-d H:i:s') . "\n";
echo "---------------------------------------------------\n";

// --- 1. Fetch ALL active API configurations from the database ---
function get_active_apis() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT api_name, api_key, affiliate_tag FROM affiliate_config WHERE is_active = TRUE");
        $raw_configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $configs = [];
        if (empty($raw_configs)) {
            echo "Warning: No active APIs found in the database. Please add and activate an API in the admin panel.\n";
            return [];
        }

        foreach ($raw_configs as $config) {
            // Decrypt the API key before use
            $configs[strtolower($config['api_name'])] = [
                'key' => decrypt_data($config['api_key']),
                'tag' => $config['affiliate_tag']
            ];
        }
        echo "Found " . count($configs) . " active API(s): " . implode(', ', array_keys($configs)) . "\n";
        return $configs;
    } catch (PDOException $e) {
        echo "Fatal Error: Could not fetch API configurations from database. " . $e->getMessage() . "\n";
        return [];
    }
}

// --- 2. Generic function to fetch an image from ANY supported platform ---
function fetch_image_from_api($query, $active_apis) {
    if (empty($active_apis)) return null;

    // Try each active API in order until we get an image
    foreach ($active_apis as $platform => $config) {
        if (empty($config['key'])) continue;

        echo "  - Trying API: " . ucfirst($platform) . " for query '{$query}'...\n";
        $image_url = null;
        $url = '';

        // Build the URL based on the platform
        switch ($platform) {
            case 'pixabay':
                $url = "https://pixabay.com/api/?key=" . urlencode($config['key']) . "&q=" . urlencode($query) . "&image_type=photo&per_page=3&safesearch=true";
                break;
            case 'pexels':
                $url = "https://api.pexels.com/v1/search?query=" . urlencode($query) . "&per_page=1";
                break;
            // Add other APIs here in the future
            default:
                echo "    - SKIPPED: Platform '{$platform}' is not supported by the cron job yet.\n";
                continue 2; // Skip to the next API in the loop
        }
        
        $ch = curl_init($url);
        if ($platform === 'pexels') {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: ' . $config['key']]);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            echo "    - cURL Error for " . ucfirst($platform) . ": " . $error . "\n";
            continue;
        }

        $data = json_decode($response, true);

        // Extract image URL based on platform's response structure
        if ($platform === 'pixabay' && !empty($data['hits'][0]['largeImageURL'])) {
            $image_url = $data['hits'][0]['largeImageURL'];
        } elseif ($platform === 'pexels' && !empty($data['photos'][0]['src']['large2x'])) {
            $image_url = $data['photos'][0]['src']['large2x'];
        }
        
        if ($image_url) {
            echo "    - SUCCESS: Image found via " . ucfirst($platform) . ".\n";
            return $image_url; // Return the first image we find
        } else {
            echo "    - INFO: No image found via " . ucfirst($platform) . ".\n";
        }
    }
    return null; // Return null if no API could find an image
}

// --- 3. Function to save the product to the database ---
function save_product_to_db($product_data, $image_url) {
    global $pdo;
    try {
        $params = [
            'title' => $product_data['title'],
            'slug' => slugify($product_data['title']), // Slugify function from functions.php
            'description' => $product_data['description'],
            'price' => $product_data['price'],
            'image_url' => $image_url,
            'category_id' => $product_data['category_id'],
            'brand_id' => $product_data['brand_id'],
            'is_published' => 1,
            'is_manual' => 0, // Mark as API-added
            'rating' => $product_data['rating'],
            'discount_percentage' => $product_data['discount'],
        ];

        // Check for duplicate slug before inserting
        $check_stmt = $pdo->prepare("SELECT id FROM products WHERE slug = ?");
        $check_stmt->execute([$params['slug']]);
        if ($check_stmt->fetch()) {
             echo "  - SKIPPED: Product with similar title (slug: {$params['slug']}) already exists.\n";
             return false;
        }
        
        $sql = "INSERT INTO products (title, slug, description, price, image_url, category_id, brand_id, is_published, is_manual, rating, discount_percentage) 
                VALUES (:title, :slug, :description, :price, :image_url, :category_id, :brand_id, :is_published, :is_manual, :rating, :discount_percentage)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return true;
    } catch (PDOException $e) {
        echo "  - DB_ERROR: Failed to save product '{$product_data['title']}'. " . $e->getMessage() . "\n";
        return false;
    }
}

// --- 4. Main execution logic ---
function run_product_import() {
    $active_apis = get_active_apis();
    if (empty($active_apis)) {
        return; // Stop execution if no APIs are configured
    }

    // This is where you would get your list of products to process.
    // For now, we are using a dummy data generator.
    $products_to_process = get_dummy_product_list_from_generator();

    echo "Found " . count($products_to_process) . " products to process...\n";

    $added_count = 0;
    foreach ($products_to_process as $product) {
        echo "---------------------------------------------------\n";
        echo "Processing: " . $product['title'] . "\n";
        
        $image_url = fetch_image_from_api($product['keywords'], $active_apis);

        if ($image_url) {
            if (save_product_to_db($product, $image_url)) {
                $added_count++;
                echo "  -> SUCCESS: Product '{$product['title']}' was added to the database.\n";
            }
        } else {
            echo "  -> FAILED & SKIPPED: Could not fetch an image for '{$product['title']}' from any active API.\n";
        }
    }

    echo "---------------------------------------------------\n";
    echo "Summary: Added {$added_count} new products.\n";
}

// Dummy function to generate products. Replace this with your actual product source.
function get_dummy_product_list_from_generator() {
    // This function can be expanded to read from a CSV, another API, etc.
    return [
        ['title' => 'High-Performance Gaming Laptop', 'keywords' => 'gaming laptop', 'description' => 'A powerful laptop for all your gaming needs.', 'price' => 1499.99, 'category_id' => 1, 'brand_id' => 1, 'rating' => 4.8, 'discount' => 15],
        ['title' => 'Comfortable Running Shoes', 'keywords' => 'running shoes', 'description' => 'Lightweight and durable shoes for your daily run.', 'price' => 89.99, 'category_id' => 2, 'brand_id' => 2, 'rating' => 4.5, 'discount' => 10],
        ['title' => 'Automatic Espresso Machine', 'keywords' => 'coffee machine', 'description' => 'Brew perfect espresso shots at home with this easy-to-use machine.', 'price' => 299.50, 'category_id' => 3, 'brand_id' => 3, 'rating' => 4.9, 'discount' => 20],
        ['title' => 'Eco-Friendly Yoga Mat', 'keywords' => 'yoga mat', 'description' => 'A non-slip, eco-friendly mat for your yoga practice.', 'price' => 45.00, 'category_id' => 4, 'brand_id' => 4, 'rating' => 4.7, 'discount' => 5],
        ['title' => 'Luxury Leather Designer Handbag', 'keywords' => 'leather handbag', 'description' => 'A stylish and elegant handbag for any occasion.', 'price' => 350.00, 'category_id' => 5, 'brand_id' => 5, 'rating' => 4.6, 'discount' => 25],
    ];
}


// --- RUN THE JOB ---
run_product_import();

echo "---------------------------------------------------\n";
echo "Cron Job Finished at " . date('Y-m-d H:i:s') . "\n";
?>