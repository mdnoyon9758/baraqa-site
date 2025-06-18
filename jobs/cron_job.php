<?php
// This script is intended to be run from the command line (CLI)
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

define('CRON_JOB_RUNNING', true);
set_time_limit(600); // 10 minutes
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- DYNAMIC CONFIGURATION ---
// CRITICAL FIX: Use __DIR__ to define a portable base path.
// This goes up one level from /jobs/ to the project root.
define('BASE_PATH', dirname(__DIR__));

// CRITICAL FIX: Use 'python3' for Linux environments like Render.
$python_executable = 'python3'; 
// --- END DYNAMIC CONFIGURATION ---

// Load dependencies using the dynamic base path
require_once BASE_PATH . '/includes/functions.php'; // This will also load db_connect.php
require_once BASE_PATH . '/config.php';

// BEST PRACTICE: Fetch API key from environment variables on Render
$pixabay_api_key = getenv('PIXABAY_API_KEY') ?: ''; // Fallback to empty if not set

// --- Get Command-Line Options ---
$options = getopt('', ['force-new', 'refetch-images', 'no-images']);
$force_new_only = isset($options['force-new']);
$refetch_images = isset($options['refetch-images']);
$no_images = isset($options['no-images']);

echo "Cron Job Started.\n";
if ($force_new_only) echo "Mode: Force New Products Only.\n";
if ($refetch_images) echo "Mode: Refetching All Images.\n";
if ($no_images) echo "Mode: Image fetching disabled for updates.\n";


function fetchMultipleImagesFromPixabay(string $query, int $count = 3): array {
    global $pixabay_api_key;
    if (empty($pixabay_api_key)) {
        echo "  - SKIPPING API CALL: PIXABAY_API_KEY environment variable is not set.\n";
        return [];
    }
    $url = sprintf("https://pixabay.com/api/?key=%s&q=%s&image_type=photo&per_page=%d&safesearch=true&orientation=horizontal", $pixabay_api_key, urlencode($query), $count);
    echo "  - Calling Pixabay API with query: '{$query}'\n";
    $ch = curl_init();
    // CRITICAL FIX: Removed CURLOPT_SSL_VERIFYPEER => false for production security
    curl_setopt_array($ch, [CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error) { echo "    -> cURL Error: {$error}\n"; return []; }
    if ($http_code !== 200) { echo "    -> API call failed with HTTP status code: {$http_code}\n"; return []; }
    $data = json_decode($response, true);
    if (isset($data['hits']) && !empty($data['hits'])) {
        echo "    -> SUCCESS: Found " . count($data['hits']) . " image(s).\n";
        return array_column($data['hits'], 'largeImageURL');
    }
    echo "    -> API call successful, but no images found.\n";
    return [];
}

// Logic for re-fetching images for all products
if ($refetch_images) {
    echo "Starting image re-fetch process...\n";
    $products_to_update = $pdo->query("SELECT id, title, brand_id FROM products ORDER BY id DESC")->fetchAll();
    foreach($products_to_update as $product) {
        $brand_name = $pdo->query("SELECT name FROM brands WHERE id = {$product['brand_id']}")->fetchColumn();
        $image_query = $brand_name . ' ' . $product['title'];
        $images = fetchMultipleImagesFromPixabay($image_query, 1);
        if (!empty($images)) {
            $pdo->prepare("UPDATE products SET image_url = ? WHERE id = ?")->execute([$images[0], $product['id']]);
            echo "  - Updated image for product ID {$product['id']}: {$product['title']}\n";
        } else {
            echo "  - Could not find image for product ID {$product['id']}\n";
        }
        sleep(1);
    }
    echo "Image re-fetch process finished.\n";
    exit;
}

// --- Main Product Generation and Processing Logic ---
$scraper_script = BASE_PATH . '/scraper.py';
if (!file_exists($scraper_script)) die("FATAL: Scraper script not found at '{$scraper_script}'!");
$command = escapeshellcmd($python_executable) . ' ' . escapeshellarg($scraper_script);
$json_output = shell_exec($command);
$products_from_script = json_decode($json_output, true);
if (!is_array($products_from_script) || empty($products_from_script)) die("FATAL: Product generation failed or returned empty JSON.\n");
echo "Generated " . count($products_from_script) . " products. Processing...\n";

function getOrCreateEntity($pdo, $table, $name, $extra_cols = []) {
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

foreach ($products_from_script as $product_data) {
    echo "\n---------------------------------------------------\n";
    echo "STARTING: " . ($product_data['title'] ?? 'Untitled') . "\n";
    try {
        $pdo->beginTransaction();
        $main_cat_id = getOrCreateEntity($pdo, 'categories', $product_data['main_category'], ['is_published' => 1]);
        $sub_cat_id = getOrCreateEntity($pdo, 'categories', $product_data['sub_category'], ['parent_id' => $main_cat_id, 'is_published' => 1]);
        $brand_logo_url = null;
        if (!$no_images) {
            $logo_images = fetchMultipleImagesFromPixabay($product_data['brand'] . " logo", 1);
            $brand_logo_url = !empty($logo_images) ? $logo_images[0] : null;
        }
        $brand_id = getOrCreateEntity($pdo, 'brands', $product_data['brand'], ['brand_logo_url' => $brand_logo_url]);
        
        $stmt_check = $pdo->prepare("SELECT id, image_url FROM products WHERE title = :title");
        $stmt_check->execute(['title' => $product_data['title']]);
        $existing_product = $stmt_check->fetch();
        
        if ($force_new_only && $existing_product) {
            echo "  - SKIPPED: Product already exists (as per --force-new flag).\n"; $pdo->commit(); continue;
        }
        $main_image_url = $existing_product['image_url'] ?? null;
        if (empty($main_image_url) && !$no_images) {
            $image_search_query = $product_data['brand'] . ' ' . $product_data['sub_category'];
            $api_gallery_images = fetchMultipleImagesFromPixabay($image_search_query, 3);
            if (empty($api_gallery_images)) {
                echo "  - FAILED & SKIPPED: Could not fetch any real images for query '{$image_search_query}'. Product will not be added.\n";
                $pdo->commit(); continue;
            }
            $main_image_url = $api_gallery_images[0];
        } elseif ($no_images && !$existing_product) {
             echo "  - FAILED & SKIPPED: No images allowed for new products with --no-images flag.\n";
             $pdo->commit(); continue;
        }

        $product_slug = slugify($product_data['title']);
        if (!$existing_product) {
            $temp_slug = $product_slug; $counter = 1;
            do {
                $stmt_slug_check = $pdo->prepare("SELECT id FROM products WHERE slug = ?");
                $stmt_slug_check->execute([$temp_slug]);
                $is_duplicate = $stmt_slug_check->fetch();
                if ($is_duplicate) $temp_slug = $product_slug . '-' . $counter++;
            } while ($is_duplicate);
            $product_slug = $temp_slug;
        }
        $product_params = [
            'title' => $product_data['title'], 'slug' => $product_slug, 'description' => $product_data['description'],
            'category_id' => $sub_cat_id, 'brand_id' => $brand_id, 'price' => (float)($product_data['price'] ?? 0),
            'discount_percentage' => (int)($product_data['discount_percentage'] ?? 0), 'rating' => (float)($product_data['rating'] ?? 0),
            'reviews_count' => (int)($product_data['reviews_count'] ?? 0), 'stock_quantity' => random_int(10, 300),
            'image_url' => $main_image_url, 'affiliate_link' => "https://www.example.com/product/" . $product_slug,
            'platform' => $product_data['platform'], 'trend_score' => calculateTrendScore((float)($product_data['rating'] ?? 0), (int)($product_data['reviews_count'] ?? 0), (int)($product_data['discount_percentage'] ?? 0)),
            'is_published' => 1
        ];

        if ($existing_product) {
            $product_params['id'] = $existing_product['id'];
            $sql = "UPDATE products SET slug=:slug, description=:description, category_id=:category_id, price=:price, discount_percentage=:discount_percentage, rating=:rating, reviews_count=:reviews_count, stock_quantity=:stock_quantity, image_url=:image_url, affiliate_link=:affiliate_link, platform=:platform, trend_score=:trend_score, is_published=:is_published WHERE id=:id";
            echo "  - Updating existing product (ID: {$existing_product['id']}).\n";
        } else {
            $sql = "INSERT INTO products (title, slug, description, category_id, brand_id, price, discount_percentage, rating, reviews_count, stock_quantity, image_url, affiliate_link, platform, trend_score, is_published) VALUES (:title, :slug, :description, :category_id, :brand_id, :price, :discount_percentage, :rating, :reviews_count, :stock_quantity, :image_url, :affiliate_link, :platform, :trend_score, :is_published)";
            echo "  - Inserting new product.\n";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($product_params);
        
        $db_product_id = $existing_product['id'] ?? $pdo->lastInsertId();

        if ($db_product_id) {
            // CRITICAL FIX: PostgreSQL compatible date query
            $pdo->prepare("DELETE FROM price_history WHERE product_id = ? AND check_date::date = CURRENT_DATE")->execute([$db_product_id]);
            $pdo->prepare("INSERT INTO price_history (product_id, price) VALUES (?, ?)")->execute([$db_product_id, $product_params['price']]);
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