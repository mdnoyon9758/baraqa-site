<?php
// File: jobs/cron_job.php

define('CRON_JOB_RUNNING', true);
if (php_sapi_name() !== 'cli') { die("Forbidden"); }
set_time_limit(1800); // 30 minutes execution time

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
            echo "Warning: No active APIs found. Please add one in the admin panel.\n";
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
        echo "Fatal Error: Could not fetch API configurations. " . $e->getMessage() . "\n";
        return [];
    }
}

function get_or_create_term_id($name, $table_name) {
    global $pdo;
    if (empty(trim($name))) { return null; }
    $stmt_check = $pdo->prepare("SELECT id FROM {$table_name} WHERE name = :name");
    $stmt_check->execute([':name' => $name]);
    if ($existing = $stmt_check->fetch()) {
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

function fetch_gallery_images($query, $active_apis, $image_count = 5) {
    if (empty($active_apis)) return [];
    foreach ($active_apis as $platform => $config) {
        if (empty($config['key'])) continue;
        echo "  - Trying API: " . ucfirst($platform) . " for a gallery of {$image_count} images...\n";
        $gallery = [];
        $url = '';
        switch ($platform) {
            case 'pixabay':
                $url = "https://pixabay.com/api/?key=" . urlencode($config['key']) . "&q=" . urlencode($query) . "&image_type=photo&per_page={$image_count}&safesearch=true";
                break;
            case 'pexels':
                 $url = "https://api.pexels.com/v1/search?query=" . urlencode($query) . "&per_page={$image_count}";
                break;
            default: continue 2;
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
        if ($platform === 'pixabay' && !empty($data['hits'])) {
            foreach($data['hits'] as $hit) { $gallery[] = $hit['largeImageURL']; }
        } elseif ($platform === 'pexels' && !empty($data['photos'])) {
            foreach($data['photos'] as $photo) { $gallery[] = $photo['src']['large2x']; }
        }
        if (!empty($gallery)) {
            echo "    - SUCCESS: Found " . count($gallery) . " images via " . ucfirst($platform) . ".\n";
            return $gallery;
        } else {
             echo "    - INFO: No images found via " . ucfirst($platform) . ".\n";
        }
    }
    return [];
}

function save_product_with_gallery($product_data, $gallery_urls) {
    global $pdo;
    if (empty($gallery_urls)) return false;
    $main_image_url = $gallery_urls[0];
    try {
        $pdo->beginTransaction();
        $params = [
            'title' => $product_data['title'],
            'slug' => slugify($product_data['title']),
            'description' => $product_data['description'],
            'price' => $product_data['price'],
            'image_url' => $main_image_url,
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
             echo "  - SKIPPED: Product with slug '{$params['slug']}' already exists.\n";
             $pdo->rollBack();
             return false;
        }
        $sql = "INSERT INTO products (title, slug, description, price, image_url, category_id, brand_id, is_published, is_manual, rating, discount_percentage) 
                VALUES (:title, :slug, :description, :price, :image_url, :category_id, :brand_id, :is_published, :is_manual, :rating, :discount_percentage)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $product_id = $pdo->lastInsertId();
        if ($product_id && count($gallery_urls) > 1) {
            $gallery_sql = "INSERT INTO product_gallery (product_id, image_url) VALUES (:product_id, :image_url)";
            $gallery_stmt = $pdo->prepare($gallery_sql);
            for ($i = 1; $i < count($gallery_urls); $i++) {
                $gallery_stmt->execute([':product_id' => $product_id, ':image_url' => $gallery_urls[$i]]);
            }
        }
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "  - DB_ERROR: Failed to save product '{$product_data['title']}'. " . $e->getMessage() . "\n";
        return false;
    }
}

function get_dummy_product_list_from_generator() {
    $product_adjectives = ['High-Performance', 'Eco-Friendly', 'Luxury', 'Ergonomic', 'Smart', 'Classic', 'Next-Gen', 'Compact', 'Professional', 'Artisan'];
    $product_nouns = ['Gadget', 'Device', 'Appliance', 'Kit', 'Tool', 'System', 'Wearable', 'Accessory', 'Solution', 'Unit'];
    $product_categories = ['Tech & Gadgets', 'Home Automation', 'Health & Wellness', 'Outdoor & Adventure', 'Fashion & Style', 'Kitchenware', 'Office & Productivity', 'Automotive', 'Pet Supplies', 'Travel Gear'];
    $product_brands = ['InnovateX', 'EcoLife', 'Aura', 'Titan', 'Zenith', 'Fusion', 'Evolve', 'Quantum', 'Nexus', 'Vertex'];
    $products = [];
    for ($i = 0; $i < 30; $i++) {
        $adj = $product_adjectives[array_rand($product_adjectives)];
        $noun = $product_nouns[array_rand($product_nouns)];
        $cat = $product_categories[array_rand($product_categories)];
        $brand = $product_brands[array_rand($product_brands)];
        $products[] = [
            'title' => "{$adj} {$noun} " . rand(1000, 9999),
            'keywords' => strtolower("{$adj} {$noun}"),
            'description' => "An innovative {$adj} {$noun} designed for modern life. Perfect for the {$cat} category, brought to you by {$brand}.",
            'price' => round(rand(5000, 200000) / 100, 2),
            'rating' => round(rand(35, 50) / 10, 1),
            'discount' => rand(5, 40),
            'category_name' => $cat,
            'brand_name' => $brand,
        ];
    }
    return $products;
}

function run_product_import() {
    $active_apis = get_active_apis();
    if (empty($active_apis)) { return; }
    $products_to_process = get_dummy_product_list_from_generator();
    echo "Generated " . count($products_to_process) . " unique products to process...\n";
    $added_count = 0;
    foreach ($products_to_process as $product_info) {
        echo "---------------------------------------------------\n";
        echo "Processing: " . $product_info['title'] . "\n";
        $image_count = rand(3, 7);
        $gallery_urls = fetch_gallery_images($product_info['keywords'], $active_apis, $image_count);
        if (!empty($gallery_urls)) {
            $product_info['category_id'] = get_or_create_term_id($product_info['category_name'], 'categories');
            $product_info['brand_id'] = get_or_create_term_id($product_info['brand_name'], 'brands');
            if (save_product_with_gallery($product_info, $gallery_urls)) {
                $added_count++;
                echo "  -> SUCCESS: Product '{$product_info['title']}' and its gallery were added to the database.\n";
            }
        } else {
            echo "  -> FAILED & SKIPPED: Could not fetch any images for '{$product_info['title']}'.\n";
        }
    }
    echo "---------------------------------------------------\n";
    echo "Summary: Added {$added_count} new products.\n";
}

run_product_import();

echo "---------------------------------------------------\n";
echo "Cron Job Finished at " . date('Y-m-d H:i:s') . "\n";
?>