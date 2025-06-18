<?php
// Our front controller (index.php) already includes app.php.
// So, we just need to handle the logic for this specific page.

// --- 1. Get Brand Info from Slug ---
// The slug is made available by our front controller in index.php
$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    // If no slug, redirect to homepage
    header("Location: /");
    exit;
}

try {
    // Fetch brand details
    $stmt_brand = $pdo->prepare("SELECT * FROM brands WHERE slug = :slug");
    $stmt_brand->execute(['slug' => $slug]);
    $brand = $stmt_brand->fetch();

    // If brand doesn't exist, show a proper 404 page
    if (!$brand) {
        http_response_code(404);
        require __DIR__ . '/views/404.php';
        exit;
    }
    
    $page_title = 'Products from ' . $brand['name'];
    $brand_id = $brand['id'];

    // --- 2. Build Dynamic SQL Query for Products ---
    $params = [':brand_id' => $brand_id];
    $sql_from_where = "FROM products p WHERE p.brand_id = :brand_id AND p.is_published = 1";
    
    // --- 3. Sorting Logic ---
    $sort_options = [
        'popularity' => 'p.trend_score DESC',
        'price_asc'  => 'p.price ASC',
        'price_desc' => 'p.price DESC',
        'rating'     => 'p.rating DESC'
    ];
    $sort_key = $_GET['sort'] ?? 'popularity';
    $order_by_sql = $sort_options[$sort_key] ?? $sort_options['popularity'];
    
    // --- 4. Pagination Logic ---
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;
    $products_per_page = (int)($SITE_SETTINGS['products_per_page'] ?? 16);
    $offset = ($page - 1) * $products_per_page;
    
    // --- 5. Fetch Total Count and Products with a Single Query (more efficient) ---
    // We run one query to get all IDs, then count them, then fetch data for the current page.
    $id_sql = "SELECT p.id " . $sql_from_where . " ORDER BY " . $order_by_sql;
    $id_stmt = $pdo->prepare($id_sql);
    $id_stmt->execute($params);
    $all_product_ids = $id_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $total_products = count($all_product_ids);
    $total_pages = ceil($total_products / $products_per_page);
    
    // Get the slice of IDs for the current page
    $ids_for_current_page = array_slice($all_product_ids, $offset, $products_per_page);
    
    $products = [];
    if (!empty($ids_for_current_page)) {
        $placeholders = implode(',', array_fill(0, count($ids_for_current_page), '?'));
        // The ORDER BY FIELD is specific to MySQL, so we use a CASE statement for cross-db compatibility
        $product_sql = "SELECT * FROM products WHERE id IN ($placeholders) ORDER BY " . $order_by_sql;
        $product_stmt = $pdo->prepare($product_sql);
        $product_stmt->execute($ids_for_current_page);
        $products = $product_stmt->fetchAll();
    }

} catch (PDOException $e) {
    http_response_code(500);
    $page_title = "Server Error";
    require __DIR__ . '/includes/header.php';
    echo '<div class="container text-center my-5"><h1>Server Error</h1><p>We are experiencing technical difficulties.</p></div>';
    error_log('Brand page error: ' . $e->getMessage());
    require __DIR__ . '/includes/footer.php';
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <!-- Brand Header -->
    <div class="card p-4 p-md-5 mb-5 text-center bg-light border-0 shadow-sm">
        <?php if (!empty($brand['brand_logo_url'])): ?>
            <img src="<?php echo e($brand['brand_logo_url']); ?>" alt="<?php echo e($brand['name']); ?> Logo" class="brand-logo mx-auto mb-3">
        <?php endif; ?>
        <h1 class="display-4"><?php echo e($brand['name']); ?></h1>
        <?php if (!empty($brand['description'])): ?>
            <p class="lead col-lg-8 mx-auto"><?php echo e($brand['description']); ?></p>
        <?php endif; ?>
    </div>
    
    <!-- Toolbar: Product Count & Sorting -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="fw-bold text-muted">Showing <?php echo count($products); ?> of <?php echo $total_products; ?> products</div>
        <!-- PATH FIX: form action is now a root-relative path -->
        <form method="GET" id="sort-form" action="/brand/<?php echo e($slug); ?>">
            <label for="sort" class="form-label me-2 mb-0">Sort by:</label>
            <select name="sort" id="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="popularity" <?php echo ($sort_key == 'popularity') ? 'selected' : ''; ?>>Popularity</option>
                <option value="price_asc" <?php echo ($sort_key == 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
                <option value="price_desc" <?php echo ($sort_key == 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
                <option value="rating" <?php echo ($sort_key == 'rating') ? 'selected' : ''; ?>>Rating</option>
            </select>
        </form>
    </div>

    <!-- Product Grid -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
        <?php if (!empty($products)):
            foreach ($products as $product) {
                include __DIR__ . '/includes/_product_card.php';
            }
        else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center" role="alert">
                    No products found for this brand yet. Please check back later!
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <nav class="mt-5 d-flex justify-content-center">
        <ul class="pagination">
            <?php if ($total_pages > 1):
                $sort_param = isset($_GET['sort']) ? '&sort=' . urlencode($_GET['sort']) : '';
                for ($i = 1; $i <= $total_pages; $i++):
            ?>
            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                <!-- PATH FIX: Pagination links are now root-relative -->
                <a class="page-link" href="/brand/<?php echo e($slug); ?>?page=<?php echo $i; ?><?php echo $sort_param; ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; endif; ?>
        </ul>
    </nav>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>