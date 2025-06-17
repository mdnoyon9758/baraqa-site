<?php
require_once __DIR__ . '/includes/app.php';

// --- 1. Get Brand Info from Slug ---
$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    header("Location: /bs/");
    exit;
}

try {
    // Fetch brand details from the brands table
    $stmt_brand = $pdo->prepare("SELECT * FROM brands WHERE slug = :slug");
    $stmt_brand->execute(['slug' => $slug]);
    $brand = $stmt_brand->fetch();

    if (!$brand) {
        http_response_code(404);
        $page_title = "Brand Not Found";
        require_once __DIR__ . '/includes/header.php';
        echo '<div class="container text-center my-5"><h1 class="display-1">404</h1><h2>Brand Not Found</h2><p>The brand you are looking for does not exist.</p><a href="/bs/" class="btn btn-primary">Go to Homepage</a></div>';
        require_once __DIR__ . '/includes/footer.php';
        exit;
    }
    
    $page_title = 'Products by ' . $brand['name'];
    $brand_id = $brand['id'];

    // --- 2. Build Dynamic SQL Query ---
    $params = [':brand_id' => $brand_id];
    $sql_base = "FROM products p";
    $where_clauses = ["p.brand_id = :brand_id", "p.is_published = 1"];
    $sql_where = " WHERE " . implode(" AND ", $where_clauses);
    
    // --- 3. Sorting, Pagination, and Fetching Products ---
    $sort_options = ['popularity' => 'p.trend_score DESC', 'price_asc' => 'p.price ASC', 'price_desc' => 'p.price DESC', 'rating' => 'p.rating DESC'];
    $sort_key = $_GET['sort'] ?? 'popularity';
    $order_by_sql = $sort_options[$sort_key] ?? $sort_options['popularity'];
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;
    $products_per_page = (int)($SITE_SETTINGS['products_per_page'] ?? 16);
    $offset = ($page - 1) * $products_per_page;
    
    $count_sql = "SELECT COUNT(p.id) " . $sql_base . $sql_where;
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_products = $count_stmt->fetchColumn();
    $total_pages = ceil($total_products / $products_per_page);
    
    $product_sql = "SELECT p.* " . $sql_base . $sql_where . " ORDER BY $order_by_sql LIMIT :limit OFFSET :offset";
    $product_stmt = $pdo->prepare($product_sql);
    $product_stmt->bindValue(':brand_id', $brand_id, PDO::PARAM_INT);
    $product_stmt->bindValue(':limit', $products_per_page, PDO::PARAM_INT);
    $product_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $product_stmt->execute();
    $products = $product_stmt->fetchAll();

} catch (PDOException $e) {
    http_response_code(500);
    $page_title = "Server Error";
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container text-center my-5"><h1>Server Error</h1><p>We are experiencing technical difficulties.</p></div>';
    error_log('Brand page error: ' . $e->getMessage());
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="card p-4 p-md-5 mb-5 text-center bg-light border-0 shadow-sm">
        <?php if (!empty($brand['brand_logo_url'])): ?>
            <img src="<?php echo e($brand['brand_logo_url']); ?>" alt="<?php echo e($brand['name']); ?> Logo" class="brand-logo mx-auto mb-3">
        <?php endif; ?>
        <h1 class="display-4"><?php echo e($brand['name']); ?></h1>
        <?php if (!empty($brand['description'])): ?>
            <p class="lead col-lg-8 mx-auto"><?php echo e($brand['description']); ?></p>
        <?php endif; ?>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="fw-bold text-muted">Showing <?php echo count($products); ?> of <?php echo $total_products; ?> products</div>
        <form method="GET" id="sort-form" action="/bs/brand/<?php echo e($slug); ?>">
            <label for="sort" class="form-label me-2 mb-0">Sort by:</label>
            <select name="sort" id="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="popularity" <?php echo ($sort_key == 'popularity') ? 'selected' : ''; ?>>Popularity</option>
                <option value="price_asc" <?php echo ($sort_key == 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
                <option value="price_desc" <?php echo ($sort_key == 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
                <option value="rating" <?php echo ($sort_key == 'rating') ? 'selected' : ''; ?>>Rating</option>
            </select>
        </form>
    </div>

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
                <a class="page-link" href="/bs/brand/<?php echo e($slug); ?>?page=<?php echo $i; ?><?php echo $sort_param; ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; endif; ?>
        </ul>
    </nav>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>