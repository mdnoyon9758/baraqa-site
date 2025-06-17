<?php
define('CRON_JOB_RUNNING', true);
set_time_limit(600);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- CONFIGURATION ---
define('BASE_PATH', 'C:\xampp\htdocs\bs');
// IMPORTANT: Make sure your Pixabay API key is correct.
define('PIXABAY_API_KEY', '50872936-f8bbe223639b3a51ef7f2fcfa');
$python_executable = 'C:\Users\USER\AppData\Local\Programs\Python\Python311\python.exe';
// --- END CONFIGURATION ---

require_once BASE_PATH . '/includes/db_connect.php';
require_once BASE_PATH . '/config.php';
require_once BASE_PATH . '/includes/functions.php';

$options = getopt('', ['force-new::']);
$force_new_only = isset($options['force-new']);
echo "Cron Job Started with STRICT IMAGE POLICY.\n";

/**
 * Fetches multiple image URLs from Pixabay using a robust query.
 *
 * @param string $query The search term.
 * @param int $count The number of images to fetch.
 * @return array An array of image URLs, or an empty array on failure.
 */
function fetchMultipleImagesFromPixabay(string $query, int $count = 3): array {
    if (empty(PIXABAY_API_KEY) || strpos(PIXABAY_API_KEY, 'YOUR_') === 0) {
        echo "  - SKIPPING API CALL: Pixabay API Key is not set.\n";
        return [];
    }

    $url = sprintf(
        "https://pixabay.com/api/?key=%s&q=%s&image_type=photo&per_page=%d&safesearch=true&orientation=horizontal",
        PIXABAY_API_KEY,
        urlencode($query), // The query is now pre-formatted
        $count
    );

    echo "  - Calling Pixabay API with URL: " . $url . "\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, [CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_SSL_VERIFYPEER => false]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        echo "    -> API call failed with HTTP status code: {$http_code}\n";
        return [];
    }

    $data = json_decode($response, true);
    
    if (isset($data['hits']) && !empty($data['hits'])) {
        $found_count = count($data['hits']);
        echo "    -> SUCCESS: Found {$found_count} image(s).\n";
        return array_column($data['hits'], 'largeImageURL');
    }
    
    echo "    -> API call successful, but no images found for this query.\n";
    return [];
}

$scraper_script = BASE_PATH . '/scraper.py';
if (!file_exists($scraper_script)) die("FATAL: Scraper script not found at '{$scraper_script}'!");
$command = escapeshellcmd('"' . $python_executable . '" "' . $scraper_script . '"');
$json_output = shell_exec($command);
$products_from_script = json_decode($json_output, true);
if (!is_array($products_from_script) || empty($products_from_script)) die("FATAL: Product generation failed or returned empty JSON.\n");
echo "Generated " . count($products_from_script) . " products. Processing with strict image rule...\n";

function getOrCreateEntity($pdo, $table, $name, $extra_cols = []) {
    // This function remains the same, it's solid.
    $name = trim($name); if (empty($name)) return null; $slug = slugify($name);
    $stmt = $pdo->prepare("SELECT id FROM {$table} WHERE slug = :slug");
    $stmt->execute(['slug' => $slug]); $id = $stmt->fetchColumn();
    if ($id) return $id;
    $cols = array_merge(['name', 'slug'], array_keys($extra_cols));
    $placeholders = implode(', ', array_map(fn($c) => ":$c", $cols));
    $sql = "INSERT INTO {$table} (" . implode(', ', $cols) . ") VALUES ($placeholders)";
    $insert_stmt = $pdo->prepare($sql);
    $params = array_merge(['name' => $name, 'slug' => $slug], $extra_cols);
    $insert_stmt->execute($params);
    echo "    - Created new '{$table}': '{$name}'\n";
    return $pdo->lastInsertId();
}


// --- MAIN PROCESSING LOOP ---
foreach ($products_from_script as $product_data) {
    echo "\n---------------------------------------------------\n";
    echo "STARTING: " . $product_data['title'] . "\n";

    // --- STEP 1: IMAGE FETCHING (THE GATEKEEPER) ---
    // Create a smarter, more generic query that has a high chance of success.
    $image_search_query = $product_data['brand'] . ' ' . $product_data['sub_category'];
    $api_gallery_images = fetchMultipleImagesFromPixabay($image_search_query, 3);

    // --- STEP 2: ENFORCE THE "NO IMAGE, NO PRODUCT" RULE ---
    if (empty($api_gallery_images)) {
        echo "  - FAILED & SKIPPED: Could not fetch any real images for query '{$image_search_query}'. Product will not be added.\n";
        continue; // Immediately skip to the next product in the loop.
    }
    
    // If we reach here, it means we have at least one real image.
    $main_image_url = $api_gallery_images[0];

    // --- STEP 3: DATABASE TRANSACTION ---
    try {
        $pdo->beginTransaction();
        
        $main_cat_id = getOrCreateEntity($pdo, 'categories', $product_data['main_category'], ['is_published' => 1]);
        $sub_cat_id = getOrCreateEntity($pdo, 'categories', $product_data['sub_category'], ['parent_id' => $main_cat_id, 'is_published' => 1]);
        
        // For brand logo, a placeholder is acceptable if API fails.
        $logo_images = fetchMultipleImagesFromPixabay($product_data['brand'] . " logo", 1);
        $brand_logo_url = !empty($logo_images) ? $logo_images[0] : null;
        $brand_id = getOrCreateEntity($pdo, 'brands', $product_data['brand'], ['brand_logo_url' => $brand_logo_url]);
        
        $stmt_check = $pdo->prepare("SELECT id FROM products WHERE title = :title");
        $stmt_check->execute(['title' => $product_data['title']]);
        $existing_product_id = $stmt_check->fetchColumn();
        
        if ($force_new_only && $existing_product_id) {
            echo "  - SKIPPED: Product already exists (as per --force-new flag).\n";
            $pdo->commit();
            continue;
        }

        $product_slug = slugify($product_data['title']);
        if (!$existing_product_id) {
            $temp_slug = $product_slug; $counter = 1;
            do {
                $stmt_slug_check = $pdo->prepare("SELECT id FROM products WHERE slug = ?");
                $stmt_slug_check->execute([$temp_slug]);
                $is_duplicate = $stmt_slug_check->fetch();
                if ($is_duplicate) {
                    $temp_slug = $product_slug . '-' . $counter++;
                }
            } while ($is_duplicate);
            $product_slug = $temp_slug;
        }

        $product_params = [
            'title' => $product_data['title'], 'slug' => $product_slug, 'description' => $product_data['description'],
            'category_id' => $sub_cat_id, 'brand_id' => $brand_id, 'price' => (float)($product_data['price'] ?? 0),
            'discount_percentage' => (int)($product_data['discount_percentage'] ?? 0), 'rating' => (float)($product_data['rating'] ?? 0),
            'reviews_count' => (int)($product_data['reviews_count'] ?? 0), 'stock_quantity' => random_int(10, 300),
            'image_url' => $main_image_url, // Use the real image URL
            'affiliate_link' => "https://www.example.com/product/" . $product_slug,
            'platform' => $product_data['platform'], 'trend_score' => calculateTrendScore((float)($product_data['rating'] ?? 0), (int)($product_data['reviews_count'] ?? 0), (int)($product_data['discount_percentage'] ?? 0)),
            'is_published' => 1
        ];

        if ($existing_product_id) {
            $product_params['id'] = $existing_product_id;
            $sql = "UPDATE products SET slug=:slug, description=:description, category_id=:category_id, price=:price, discount_percentage=:discount_percentage, rating=:rating, reviews_count=:reviews_count, stock_quantity=:stock_quantity, image_url=:image_url, affiliate_link=:affiliate_link, platform=:platform, trend_score=:trend_score, is_published=:is_published WHERE id=:id";
            echo "  - Updating existing product (ID: {$existing_product_id}).\n";
        } else {
            $sql = "INSERT INTO products (title, slug, description, category_id, brand_id, price, discount_percentage, rating, reviews_count, stock_quantity, image_url, affiliate_link, platform, trend_score, is_published) VALUES (:title, :slug, :description, :category_id, :brand_id, :price, :discount_percentage, :rating, :reviews_count, :stock_quantity, :image_url, :affiliate_link, :platform, :trend_score, :is_published)";
            echo "  - Inserting new product.\n";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($product_params);
        
        $db_product_id = $existing_product_id ?: $pdo->lastInsertId();

        if ($db_product_id) {
            // Update gallery with real images
            $pdo->prepare("DELETE FROM product_gallery WHERE product_id = ?")->execute([$db_product_id]);
            $stmt_gallery = $pdo->prepare("INSERT INTO product_gallery (product_id, image_url) VALUES (?, ?)");
            foreach ($api_gallery_images as $img) {
                if (!empty($img)) { $stmt_gallery->execute([$db_product_id, $img]); }
            }
            echo "    - Added " . count($api_gallery_images) . " images to gallery.\n";
            
            // Update price history
            $pdo->prepare("DELETE FROM price_history WHERE product_id = ? AND DATE(check_date) = CURDATE()")->execute([$db_product_id]);
            $stmt_history = $pdo->prepare("INSERT INTO price_history (product_id, price) VALUES (?, ?)");
            $stmt_history->execute([$db_product_id, $product_params['price']]);
            
            echo "  - SUCCESS: Product processed (ID: {$db_product_id}).\n";
        }
        
        $pdo->commit();
        sleep(1); 
    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        echo "  - DATABASE ERROR for product '" . ($product_data['title'] ?? 'N/A') . "'. Error: " . $e->getMessage() . "\n";
    }
}
echo "---------------------------------------------------\n";
echo "Cron Job Finished.\n";
?>