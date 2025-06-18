<?php
// Our front controller (index.php) handles app initialization.

$search_query = trim($_GET['q'] ?? '');
$page_title = 'Search Results';
if (!empty($search_query)) {
    $page_title .= ' for "' . e($search_query) . '"';
}

// --- Initialize all filter variables to safe defaults ---
$products = [];
$total_products = 0;
$total_pages = 0;
$available_brands = [];
$global_min_price = 0;
$global_max_price = 5000;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$brands_filter = isset($_GET['brands']) && is_array($_GET['brands']) ? $_GET['brands'] : [];
$price_range = isset($_GET['price']) ? explode('-', $_GET['price']) : [];
$min_price = !empty($price_range[0]) ? (float)$price_range[0] : null;
$max_price = !empty($price_range[1]) ? (float)$price_range[1] : null;
$min_rating = isset($_GET['min_rating']) ? (float)$_GET['min_rating'] : 0;
$sort_key = $_GET['sort'] ?? 'relevance';

try {
    // --- PART 1: Get Filter Options (Brands & Price) based on the initial search query ONLY ---
    if (!empty($search_query)) {
        $filter_base_sql = "FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN brands b ON p.brand_id = b.id WHERE p.is_published = 1 AND (p.title LIKE ? OR p.description LIKE ? OR c.name LIKE ? OR b.name LIKE ?)";
        $filter_params = array_fill(0, 4, '%' . $search_query . '%');

        $brands_stmt = $pdo->prepare("SELECT DISTINCT b.id, b.name " . $filter_base_sql . " AND b.id IS NOT NULL ORDER BY b.name ASC");
        $brands_stmt->execute($filter_params);
        $available_brands = $brands_stmt->fetchAll();

        $price_stmt = $pdo->prepare("SELECT MIN(p.price) as min_p, MAX(p.price) as max_p " . $filter_base_sql);
        $price_stmt->execute($filter_params);
        $price_limits = $price_stmt->fetch();
        $global_min_price = floor($price_limits['min_p'] ?? 0);
        $global_max_price = ceil($price_limits['max_p'] ?? 5000);
    }
    
    // --- PART 2: Build the MAIN query with ALL filters applied ---
    $main_params = [];
    $where_clauses = ["p.is_published = 1"];
    if (!empty($search_query)) {
        $where_clauses[] = "(p.title LIKE ? OR p.description LIKE ?)";
        array_push($main_params, '%' . $search_query . '%', '%' . $search_query . '%');
    }
    if (!empty($brands_filter)) {
        $where_clauses[] = "p.brand_id IN (" . implode(',', array_fill(0, count($brands_filter), '?')) . ")";
        array_push($main_params, ...$brands_filter);
    }
    if ($min_price !== null) { $where_clauses[] = "p.price >= ?"; $main_params[] = $min_price; }
    if ($max_price !== null) { $where_clauses[] = "p.price <= ?"; $main_params[] = $max_price; }
    if ($min_rating > 0) { $where_clauses[] = "p.rating >= ?"; $main_params[] = $min_rating; }
    
    $sql_where = " WHERE " . implode(" AND ", $where_clauses);
    $sql_base = "FROM products p";

    // --- PART 3: Execute query for product count and paginated results ---
    $sort_options = ['relevance' => 'p.trend_score DESC', 'price_asc' => 'p.price ASC', 'price_desc' => 'p.price DESC', 'rating' => 'p.rating DESC'];
    // CRITICAL FIX: Ensure the order by clause is safe and not from user input directly
    $order_by_sql = $sort_options[$sort_key] ?? $sort_options['relevance'];
    
    $products_per_page = (int)($SITE_SETTINGS['products_per_page'] ?? 16);
    $offset = ($page - 1) * $products_per_page;

    $count_sql = "SELECT COUNT(p.id) " . $sql_base . $sql_where;
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($main_params);
    $total_products = $count_stmt->fetchColumn();
    $total_pages = ceil($total_products / $products_per_page);

    if ($total_products > 0 && !empty($search_query)) {
        $product_sql = "SELECT p.* " . $sql_base . $sql_where . " ORDER BY " . $order_by_sql . " LIMIT ? OFFSET ?";
        $product_params = array_merge($main_params, [$products_per_page, $offset]);
        $product_stmt = $pdo->prepare($product_sql);
        for ($i = 0; $i < count($product_params); $i++) { 
            $product_stmt->bindValue($i + 1, $product_params[$i]); 
        }
        $product_stmt->execute();
        $products = $product_stmt->fetchAll();
    }

} catch (PDOException $e) {
    // Graceful error handling
    $_SESSION['error_message'] = "A search error occurred. Please try again.";
    error_log('Search page error: ' . $e->getMessage());
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="text-center mb-5">
        <h1 class="display-5">Search Results</h1>
        <?php if (!empty($search_query)): ?>
            <p class="lead">You searched for: <strong>"<?php echo e($search_query); ?>"</strong></p>
        <?php endif; ?>
    </div>
    
    <div class="row">
        <!-- Filter Sidebar -->
        <aside class="col-lg-3">
            <div class="filter-sidebar p-3 rounded bg-light shadow-sm">
                <h4 class="mb-3">Refine Results</h4>
                <form method="GET" id="filter-form" action="/search">
                    <input type="hidden" name="q" value="<?php echo e($search_query); ?>">
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
                    <div class="d-grid gap-2"><button type="submit" class="btn btn-primary">Apply Filters</button><a href="/search?q=<?php echo e($search_query);?>" class="btn btn-outline-secondary">Reset</a></div>
                </form>
            </div>
        </aside>

        <!-- Main Product Area -->
        <main class="col-lg-9">
             <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-white rounded shadow-sm">
                 <div class="fw-bold text-muted"><?php echo $total_products; ?> products found.</div>
                 <form method="GET" id="sort-form" action="/search">
                    <?php 
                        // CRITICAL FIX: Keep all other filter parameters when sorting
                        foreach ($_GET as $key => $value) { 
                            if ($key == 'sort') continue;
                            if (is_array($value)) { 
                                foreach ($value as $sub_value) {
                                    echo '<input type="hidden" name="' . e($key) . '[]" value="' . e($sub_value) . '">';
                                }
                            } else { 
                                echo '<input type="hidden" name="' . e($key) . '" value="' . e($value) . '">';
                            } 
                        } 
                    ?>
                    <label for="sort" class="form-label me-2 mb-0">Sort by:</label>
                    <select name="sort" id="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="relevance" <?php echo ($sort_key == 'relevance') ? 'selected' : ''; ?>>Relevance</option>
                        <option value="price_asc" <?php echo ($sort_key == 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_desc" <?php echo ($sort_key == 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="rating" <?php echo ($sort_key == 'rating') ? 'selected' : ''; ?>>Rating</option>
                    </select>
                </form>
            </div>
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                <?php if (!empty($products)): foreach ($products as $product) { include __DIR__ . '/includes/_product_card.php'; }
                elseif (!empty($search_query)): echo '<div class="col-12"><div class="alert alert-warning text-center p-5"><h4>No Products Found</h4><p>Your search - <strong>' . e($search_query) . '</strong> - did not match any products. Try a different keyword or adjust your filters.</p></div></div>';
                else: echo '<div class="col-12"><div class="alert alert-info text-center p-5"><h4>Start Searching</h4><p>Please enter a keyword in the search bar above to find products.</p></div></div>'; endif; ?>
            </div>
            <!-- Pagination -->
            <nav class="mt-5 d-flex justify-content-center">
                <ul class="pagination">
                    <?php if ($total_pages > 1): $query_params = $_GET;
                        for ($i = 1; $i <= $total_pages; $i++): $query_params['page'] = $i; ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>"><a class="page-link" href="/search?<?php echo http_build_query($query_params); ?>"><?php echo $i; ?></a></li>
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
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filter-form');
    const priceSlider = document.getElementById('price-slider');
    if (priceSlider) {
        noUiSlider.create(priceSlider, {
            start: [<?php echo $min_price ?? $global_min_price; ?>, <?php echo $max_price ?? $global_max_price; ?>],
            connect: true, range: {'min': <?php echo $global_min_price; ?>,'max': <?php echo $global_max_price; ?>},
            format: wNumb({ decimals: 0, prefix: '$' }), step: 5, tooltips: true
        });
        const priceInput = document.getElementById('price-input');
        priceSlider.noUiSlider.on('update', function(values) { priceInput.value = values.join('-').replace(/\$/g, ''); });
        priceSlider.noUiSlider.on('change', function() { filterForm.submit(); });
    }
    document.querySelectorAll('.brand-checkbox, .rating-radio').forEach(input => {
        input.addEventListener('change', function() { filterForm.submit(); });
    });
});
</script>