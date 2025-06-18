<?php
// File: jobs/cron_job.php

define('CRON_JOB_RUNNING', true);
if (php_sapi_name() !== 'cli') { die("Forbidden"); }
set_time_limit(900);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config.php';

echo "Cron Job Started at " . date('Y-m-d H:i:s') . "\n";
echo "---------------------------------------------------\n";

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

function fetch_image_from_api($query, $active_apis) {
    if (empty($active_apis)) return null;

    foreach ($active_apis as $platform => $config) {
        if (empty($config['key'])) continue;

        echo "  - Trying API: " . ucfirst($platform) . " for query '{$query}'...\n";
        $image_url = null;
        $url = '';

        switch ($platform) {
            case 'pixabay':
                $url = "https://pixabay.com/api/?key=" . urlencode($config['key']) . "&q=" . urlencode($query) . "&image_type=photo&per_page=3&safesearch=true";
                break;
            case 'pexels':
                $url = "https://api.pexels.com/v1/search?query=" . urlencode($query) . "&per_page=1";
                break;
            default:
                echo "    - SKIPPED: Platform '{$platform}' is not supported by the cron job yet.\n";
                continue 2;
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

        if ($platform === 'pixabay' && !empty($data['hits'][0]['largeImageURL'])) {
            $image_url = $data['hits'][0]['largeImageURL'];
        } elseif ($platform === 'pexels' && !empty($data['photos'][0]['src']['large2x'])) {
            $image_url = $data['photos'][0]['src']['large2x'];
        }
        
        if ($image_url) {
            echo "    - SUCCESS: Image found via " . ucfirst($platform) . ".\n";
            return $image_url;
        } else {
            echo "    - INFO: No image found via " . ucfirst($platform) . ".\n";
        }
    }
    return null;
}

function save_product_to_db($product_data, $image_url) {
    global $pdo;
    try {
        $params = [
            'title' => $product_data['title'],
            'slug' => slugify($product_data['title']),
            'description' => $product_data['description'],
            'price' => $product_data['price'],
            'image_url' => $image_url,
            'category_id' => $product_data['category_id'],
            'brand_id' => $product_data['brand_id'],
            'is_published' => 1,
            'is_manual' => 0,
            'rating' => $product_data['rating'],
            'discount_percentage' => $product_data['discount'],
        ];

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

function get_or_create_term_id($name, $table_name) {
    global $pdo;
    if (empty(trim($name))) { return null; }

    $stmt_check = $pdo->prepare("SELECT id FROM {$table_name} WHERE name = :name");
    $stmt_check->execute([':name' => $name]);
    $existing = $stmt_check->fetch();

    if ($existing) {
        return $existing['id'];
    }

    try {
        $slug = slugify($name);
        $sql = "INSERT INTO {$table_name} (name, slug) VALUES (:name, :slug)";
        $stmt_insert = $pdo->prepare($sql);
        $stmt_insert->execute([':name' => $name, 'slug' => $slug]);
        $new_id = $pdo->lastInsertId();
        echo "  - CREATED new {$table_name}: '{$name}' (ID: {$new_id})\n";
        return $new_id;
    } catch (PDOException $e) {
        echo "  - DB_ERROR: Could not create {$table_name} '{$name}'. " . $e->getMessage() . "\n";
        return null;
    }
}

function get_dummy_product_list_from_generator() {
    $timestamp = time(); 
    return [
        ['title' => 'Gaming Laptop ' . substr($timestamp, -4), 'keywords' => 'gaming laptop', 'description' => 'A powerful laptop for all your gaming needs.', 'price' => 1499.99, 'rating' => 4.8, 'discount' => 15, 'category_name' => 'Electronics', 'brand_name' => 'TechMaster'],
        ['title' => 'Running Shoes ' . substr($timestamp, -4), 'keywords' => 'running shoes', 'description' => 'Lightweight and durable shoes.', 'price' => 89.99, 'rating' => 4.5, 'discount' => 10, 'category_name' => 'Fashion', 'brand_name' => 'SpeedFlex'],
        ['title' => 'Espresso Machine ' . substr($timestamp, -4), 'keywords' => 'coffee machine', 'description' => 'Brew perfect espresso shots.', 'price' => 299.50, 'rating' => 4.9, 'discount' => 20, 'category_name' => 'Home & Kitchen', 'brand_name' => 'BrewRight'],
        ['title' => 'Eco-Friendly Yoga Mat ' . substr($timestamp, -4), 'keywords' => 'yoga mat', 'description' => 'A non-slip, eco-friendly mat.', 'price' => 45.00, 'rating' => 4.7, 'discount' => 5, 'category_name' => 'Sports & Outdoors', 'brand_name' => 'ZenFlow'],
        ['title' => 'Designer Handbag ' . substr($timestamp, -4), 'keywords' => 'leather handbag', 'description' => 'A stylish and elegant handbag.', 'price' => 350.00, 'rating' => 4.6, 'discount' => 25, 'category_name' => 'Fashion', 'brand_name' => 'VogueStitch'],
    ];
}

function run_product_import() {
    $active_apis = get_active_apis();
    if (empty($active_apis)) { return; }

    $products_to_process = get_dummy_product_list_from_generator();
    echo "Found " . count($products_to_process) . " products to process...\n";

    $added_count = 0;
    foreach ($products_to_process as $product_info) {
        echo "---------------------------------------------------\n";
        echo "Processing: " . $product_info['title'] . "\n";
        
        $image_url = fetch_image_from_api($product_info['keywords'], $active_apis);

        if ($image_url) {
            $product_info['category_id'] = get_or_create_term_id($product_info['category_name'], 'categories');
            $product_info['brand_id'] = get_or_create_term_id($product_info['brand_name'], 'brands');

            if (save_product_to_db($product_info, $image_url)) {
                $added_count++;
                echo "  -> SUCCESS: Product '{$product_info['title']}' was added to the database.\n";
            }
        } else {
            echo "  -> FAILED & SKIPPED: Could not fetch an image for '{$product_info['title']}' from any active API.\n";
        }
    }
    echo "---------------------------------------------------\n";
    echo "Summary: Added {$added_count} new products.\n";
}

run_product_import();

echo "---------------------------------------------------\n";
echo "Cron Job Finished at " . date('Y-m-d H:i:s') . "\n";
?>