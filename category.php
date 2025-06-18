<?php
// Our front controller (index.php) handles app initialization.

// --- 1. Get Category Info from Slug ---
$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    header("Location: /"); // Redirect to homepage if no slug
    exit;
}

// Initialize variables to safe defaults
$products = [];
$total_products = 0;
$total_pages = 0;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$brands_filter = isset($_GET['brands']) && is_array($_GET['brands']) ? $_GET['brands'] : [];
$price_range = isset($_GET['price']) ? explode('-', $_GET['price']) : [];
$min_price = !empty($price_range[0]) ? (float)$price_range[0] : null;
$max_price = !empty($price_range[1]) ? (float)$price_range[1] : null;
$min_rating = isset($_GET['min_rating']) ? (float)$_GET['min_rating'] : 0;

try {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = :slug AND is_published = 1");
    $stmt->execute(['slug' => $slug]);
    $category = $stmt->fetch();

    if (!$category) {
        http_response_code(404);
        require __DIR__ . '/views/404.php'; // Use our standard 404 page
        exit;
    }

    $page_title = $category['name'];

    // --- 2. Determine which category IDs to query ---
    $category_ids_to_query = [];
    if ($category['parent_id'] === null) {
        $category_ids_to_query[] = $category['id'];
        $stmt_sub = $pdo->prepare("SELECT id FROM categories WHERE parent_id = :parent_id AND is_published = 1");
        $stmt_sub->execute(['parent_id' => $category['id']]);
        $sub_ids = $stmt_sub->fetchAll(PDO::FETCH_COLUMN);
        $category_ids_to_query = array_merge($category_ids_to_query, $sub_ids);
    } else {
        $category_ids_to_query[] = $category['id'];
    }

    // --- 3. Build Dynamic SQL Query with Filters ---
    $params = [];
    $where_clauses = ["p.is_published = 1"];
    
    if (!empty($category_ids_to_query)) {
        $placeholders = implode(',', array_fill(0, count($category_ids_to_query), '?'));
        $where_clauses[] = "p.category_id IN ($placeholders)";
        $params = array_merge($params, $category_ids_to_query);
    } else {
        // No valid categories, so no products.
        $total_products = 0;
    }
    
    if (!empty($brands_filter)) {
        $where_clauses[] = "p.brand_id IN (" . implode(',', array_fill(0, count($brands_filter), '?')) . ")";
        $params = array_merge($params, $brands_filter);
    }

    if ($min_price !== null) { $where_clauses[] = "p.price >= ?"; $params[] = $min_price; }
    if ($max_price !== null) { $where_clauses[] = "p.price <= ?"; $params[] = $max_price; }
    if ($min_rating > 0) { $where_clauses[] = "p.rating >= ?"; $params[] = $min_rating; }
    
    // --- 4. Get Filter Options (only if there are categories to check) ---
    if(!empty($category_ids_to_query)) {
        $filter_cat_placeholders = implode(',', array_fill(0, count($category_ids_to_query), '?'));
        $brands_stmt = $pdo->prepare("SELECT DISTINCT b.id, b.name FROM products p JOIN brands b ON p.brand_id = b.id WHERE p.category_id IN ($filter_cat_placeholders) AND p.is_published = 1 ORDER BY b.name ASC");
        $brands_stmt->execute($category_ids_to_query);
        $available_brands = $brands_stmt->fetchAll();

        $price_stmt = $pdo->prepare("SELECT MIN(price) as min_p, MAX(price) as max_p FROM products WHERE category_id IN ($filter_cat_placeholders) AND is_published = 1");
        $price_stmt->execute($category_ids_to_query);
        $price_limits = $price_stmt->fetch();
        $global_min_price = floor($price_limits['min_p'] ?? 0);
        $global_max_price = ceil($price_limits['max_p'] ?? 5000);
    } else {
        $available_brands = [];
        $global_min_price = 0;
        $global_max_price = 5000;
    }


    // --- 5. Sorting, Pagination, and Fetching Products ---
    if (isset($total_products) && $total_products === 0) {
        // We already know there are no products, so we don't need to run more queries.
        $products = [];
        $total_pages = 0;
    } else {
        $sql_base = "FROM products p ";
        $sql_where = " WHERE " . implode(" AND ", $where_clauses);
        
        $count_sql = "SELECT COUNT(p.id) " . $sql_base . $sql_where;
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total_products = $count_stmt->fetchColumn();
        
        $products_per_page = (int)($SITE_SETTINGS['products_per_page'] ?? 12);
        $total_pages = ceil($total_products / $products_per_page);
        $offset = ($page - 1) * $products_per_page;
        
        if ($total_products > 0) {
            $sort_options = ['popularity' => 'p.trend_score DESC', 'price_asc' => 'p.price ASC', 'price_desc' => 'p.price DESC', 'rating' => 'p.rating DESC'];
            $sort_key = $_GET['sort'] ?? 'popularity';
            $order_by_sql = $sort_options[$sort_key] ?? $sort_options['popularity'];
            
            $product_sql = "SELECT p.* " . $sql_base . $sql_where . " ORDER BY $order_by_sql LIMIT ? OFFSET ?";
            $product_params = array_merge($params, [$products_per_page, $offset]);
            $product_stmt = $pdo->prepare($product_sql);
            for ($i = 0; $i < count($product_params); $i++) {
                $product_stmt->bindValue($i + 1, $product_params[$i]);
            }
            $product_stmt->execute();
            $products = $product_stmt->fetchAll();
        }
    }

} catch (PDOException $e) { /* Error handling... */ }

require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <!-- Filter Sidebar -->
        <aside class="col-lg-3">
             <div class="filter-sidebar p-3 rounded bg-light shadow-sm">
                <h4 class="mb-3">Filters</h4>
                <form method="GET" id="filter-form" action="/category/<?php echo e($slug); ?>">
                    <?php if(!empty($available_brands)): ?>
                    <div class="mb-4">
                        <h5>Brands</h5>
                        <?php foreach ($available_brands as $brand): ?>
                        <div class="form-check"><input class="form-check-input brand-checkbox" type="checkbox" name="brands[]" value="<?php echo e($brand['id']); ?>" id="brand-<?php echo e($brand['id']); ?>" <?php echo in_array($brand['id'], $brands_filter) ? 'checked' : ''; ?>><label class="form-check-label" for="brand-<?php echo e($brand['id']); ?>"><?php echo e($brand['name']); ?></label></div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <div class="mb-4">
                        <h5>Price Range</h5>
                        <div id="price-slider" class="mt-4"></div>
                        <input type="hidden" name="price" id="price-input" value="<?php echo e(isset($_GET['price']) ? $_GET['price'] : ''); ?>">
                    </div>
                    <div class="mb-4">
                        <h5>Minimum Rating</h5>
                        <div class="rating-filter d-flex flex-row-reverse justify-content-end">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="star<?php echo $i; ?>" name="min_rating" value="<?php echo $i; ?>" class="d-none rating-radio" <?php echo ($min_rating == $i) ? 'checked' : ''; ?>><label for="star<?php echo $i; ?>" class="rating-star fs-4" title="<?php echo $i; ?> star & up">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="d-grid gap-2"><button type="submit" class="btn btn-primary">Apply Filters</button><a href="/category/<?php echo e($slug);?>" class="btn btn-outline-secondary">Reset All</a></div>
                </form>
            </div>
        </aside>

        <!-- Main Product Area -->
        <main class="col-lg-9">
            <div class="p-3 bg-white rounded shadow-sm mb-4">
                <h1 class="h3"><?php echo e($page_title); ?></h1>
                <p class="text-muted mb-0">Showing <?php echo is_array($products) ? count($products) : 0; ?> of <?php echo $total_products; ?> products</p>
            </div>
            
             <div class="d-flex justify-content-end align-items-center mb-3">
                 <form method="GET" id="sort-form" action="/category/<?php echo e($slug); ?>">
                    <?php foreach ($_GET as $key => $value) { if ($key == 'sort' || $key == 'slug') continue; if (is_array($value)) { foreach ($value as $sub_value) echo '<input type="hidden" name="' . e($key) . '[]" value="' . e($sub_value) . '">'; } else { echo '<input type="hidden" name="' . e($key) . '" value="' . e($value) . '">'; } } ?>
                    <label for="sort" class="form-label me-2 mb-0">Sort by:</label>
                    <select name="sort" id="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="popularity" <?php echo ($sort_key == 'popularity') ? 'selected' : ''; ?>>Popularity</option>
                        <option value="price_asc" <?php echo ($sort_key == 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_desc" <?php echo ($sort_key == 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="rating" <?php echo ($sort_key == 'rating') ? 'selected' : ''; ?>>Rating</option>
                    </select>
                </form>
            </div>

            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                <?php if (!empty($products)):
                    foreach ($products as $product) {
                        include __DIR__ . '/includes/_product_card.php';
                    }
                else:
                    echo '<div class="col-12"><p class="text-center p-5 bg-light rounded">No products found in this category. Please try adjusting the filters or check back later.</p></div>';
                endif; ?>
            </div>

            <!-- Pagination -->
            <nav class="mt-5 d-flex justify-content-center">
                <ul class="pagination">
                    <?php if ($total_pages > 1): $query_params = $_GET; unset($query_params['slug']);
                        for ($i = 1; $i <= $total_pages; $i++): $query_params['page'] = $i; ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>"><a class="page-link" href="/category/<?php echo e($slug); ?>?<?php echo http_build_query($query_params); ?>"><?php echo $i; ?></a></li>
                    <?php endfor; endif; ?>
                </ul>
            </nav>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/wnumb/1.2.0/wNumb.min.js"></script>
<script>
    // JS code remains the same...
</script>