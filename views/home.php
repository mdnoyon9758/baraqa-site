<?php
// --- 1. Initialization and Data Fetching ---
require_once __DIR__ . '/../includes/app.php';
$page_title = 'AI Curated Deals & Trends';

try {
    // --- Top Sections Data ---
    $top_deals_stmt = $pdo->query("SELECT * FROM products WHERE is_published = 1 AND discount_percentage > 20 ORDER BY discount_percentage DESC, trend_score DESC LIMIT 4");
    $top_deals = $top_deals_stmt->fetchAll(PDO::FETCH_ASSOC);
    $top_trending_stmt = $pdo->query("SELECT * FROM products WHERE is_published = 1 ORDER BY trend_score DESC, rating DESC LIMIT 4");
    $top_trending = $top_trending_stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Featured Categories Section (Admin Curated) ---
    $featured_categories_stmt = $pdo->query("SELECT id, name, slug FROM categories WHERE is_featured = 1 AND parent_id IS NULL AND is_published = 1 ORDER BY menu_order ASC LIMIT 5");
    $featured_categories = $featured_categories_stmt->fetchAll(PDO::FETCH_ASSOC);
    $products_by_featured_category = [];
    foreach ($featured_categories as $f_cat) {
        $stmt_sub_ids = $pdo->prepare("SELECT id FROM categories WHERE parent_id = ?");
        $stmt_sub_ids->execute([$f_cat['id']]);
        $cat_ids_to_query = array_merge([$f_cat['id']], $stmt_sub_ids->fetchAll(PDO::FETCH_COLUMN));
        $placeholders = implode(',', array_fill(0, count($cat_ids_to_query), '?'));
        $stmt_prods = $pdo->prepare("SELECT * FROM products WHERE category_id IN ($placeholders) AND is_published = 1 ORDER BY trend_score DESC, created_at DESC LIMIT 10");
        $stmt_prods->execute($cat_ids_to_query);
        $products_by_featured_category[$f_cat['id']] = $stmt_prods->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- Popular Categories Section (Based on Clicks) ---
    $popular_categories_stmt = $pdo->query(
        "SELECT c.id, c.name, c.slug, COUNT(ac.id) as click_count
         FROM categories c JOIN products p ON c.id = p.category_id JOIN affiliate_clicks ac ON p.id = ac.product_id
         WHERE c.is_published = 1 AND c.parent_id IS NOT NULL
         GROUP BY c.id, c.name, c.slug
         ORDER BY click_count DESC LIMIT 5"
    );
    $popular_categories = $popular_categories_stmt->fetchAll(PDO::FETCH_ASSOC);
    $products_by_popular_category = [];
    foreach ($popular_categories as $p_cat) {
        $stmt_prods = $pdo->prepare("SELECT * FROM products WHERE category_id = ? AND is_published = 1 ORDER BY trend_score DESC LIMIT 10");
        $stmt_prods->execute([$p_cat['id']]);
        $products_by_popular_category[$p_cat['id']] = $stmt_prods->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- Other Sections Data ---
    $you_may_like_products = $pdo->query("SELECT * FROM products WHERE is_published = 1 ORDER BY RAND() LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
    $top_brands = $pdo->query("SELECT * FROM brands WHERE is_featured = 1 AND brand_logo_url IS NOT NULL LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Graceful error handling
    $top_deals = []; $top_trending = []; $featured_categories = []; $popular_categories = []; $you_may_like_products = []; $top_brands = [];
    error_log("Homepage data fetch failed: " . $e->getMessage());
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- HERO & FEATURES SECTIONS -->
<section class="hero-section text-center"><div class="container"><h1 class="display-3 fw-bold animate__animated animate__fadeInDown"><?php echo e($SITE_SETTINGS['hero_title'] ?? 'Smart Shopping, Smarter Savings'); ?></h1><p class="lead fs-4 animate__animated animate__fadeInUp"><?php echo e($SITE_SETTINGS['hero_subtitle'] ?? 'Discover AI-powered deals and trending products daily.'); ?></p><div class="d-grid gap-2 d-sm-flex justify-content-sm-center animate__animated animate__fadeInUp"><a href="#hot-deals" class="btn btn-primary btn-lg px-4">Explore Hot Deals</a><a href="/bs/category/electronics" class="btn btn-outline-light btn-lg px-4">Browse Electronics</a></div></div></section>
<section class="py-5"><div class="container"><div class="row text-center g-4"><div class="col-md-4" data-aos="fade-up"><div class="feature-icon mb-3"><i class="fas fa-brain fa-2x"></i></div><h4 class="fw-bold">AI-Powered Curation</h4><p class="text-muted">Our smart algorithms analyze trends to bring you the best products.</p></div><div class="col-md-4" data-aos="fade-up" data-aos-delay="100"><div class="feature-icon mb-3"><i class="fas fa-tags fa-2x"></i></div><h4 class="fw-bold">Exclusive Daily Deals</h4><p class="text-muted">Access exclusive discounts and offers you won't find anywhere else.</p></div><div class="col-md-4" data-aos="fade-up" data-aos-delay="200"><div class="feature-icon mb-3"><i class="fas fa-check-circle fa-2x"></i></div><h4 class="fw-bold">Verified Products</h4><p class="text-muted">We feature products only from trusted brands and platforms.</p></div></div></div></section>

<!-- HOT DEALS & TRENDING SECTION (Grid Layout) -->
<section id="hot-deals" class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center section-title" data-aos="fade-up"><span>Today's Highlights</span></h2>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            <?php $highlight_products = array_slice(array_merge($top_deals, $top_trending), 0, 4); ?>
            <?php foreach ($highlight_products as $index => $product): ?>
                <div data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>"><?php include __DIR__ . '/includes/_product_card.php'; ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- FEATURED CATEGORIES (Horizontal Scroll) -->
<?php if (!empty($featured_categories)): ?>
<section class="py-5">
    <div class="container-fluid">
        <?php foreach ($featured_categories as $f_cat): ?>
            <?php if (!empty($products_by_featured_category[$f_cat['id']])): ?>
            <div class="mb-5" data-aos="fade-up">
                <div class="container d-flex justify-content-between align-items-center mb-4">
                    <h2 class="section-title mb-0"><span><?php echo e($f_cat['name']); ?></span></h2>
                    <a href="/bs/category/<?php echo e($f_cat['slug']); ?>" class="btn btn-outline-primary">View All</a>
                </div>
                <div class="horizontal-scroll-wrapper">
                    <div class="ps-md-5"></div> <!-- Gutter space -->
                    <?php foreach ($products_by_featured_category[$f_cat['id']] as $product): ?>
                        <div class="product-card-wrapper"><?php include __DIR__ . '/includes/_product_card.php'; ?></div>
                    <?php endforeach; ?>
                    <div class="pe-md-5"></div> <!-- Gutter space -->
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- POPULAR CATEGORIES (Horizontal Scroll) -->
<?php if (!empty($popular_categories)): ?>
<section class="py-5 bg-light">
    <div class="container-fluid">
        <?php foreach ($popular_categories as $p_cat): ?>
            <?php if (!empty($products_by_popular_category[$p_cat['id']])): ?>
            <div class="mb-5" data-aos="fade-up">
                 <div class="container d-flex justify-content-between align-items-center mb-4">
                    <h2 class="section-title mb-0"><span>Popular in <?php echo e($p_cat['name']); ?></span></h2>
                    <a href="/bs/category/<?php echo e($p_cat['slug']); ?>" class="btn btn-outline-primary">View All</a>
                </div>
                <div class="horizontal-scroll-wrapper">
                     <div class="ps-md-5"></div>
                    <?php foreach ($products_by_popular_category[$p_cat['id']] as $product): ?>
                        <div class="product-card-wrapper"><?php include __DIR__ . '/includes/_product_card.php'; ?></div>
                    <?php endforeach; ?>
                    <div class="pe-md-5"></div>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- YOU MAY ALSO LIKE SECTION -->
<?php if (!empty($you_may_like_products)): ?>
<section class="py-5">
    <div class="container">
        <h2 class="text-center section-title" data-aos="fade-up"><span>You May Also Like</span></h2>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            <?php foreach ($you_may_like_products as $index => $product): ?>
                <div data-aos="fade-up" data-aos-delay="<?php echo $index * 50; ?>"><?php include __DIR__ . '/includes/_product_card.php'; ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- SHOP BY BRAND SECTION -->
<?php if (!empty($top_brands)): ?>
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center section-title" data-aos="fade-up"><span>Shop by Brand</span></h2>
        <div class="row row-cols-3 row-cols-sm-4 row-cols-md-6 g-5 align-items-center">
            <?php foreach ($top_brands as $brand): ?>
                <div class="col text-center" data-aos="zoom-in"><a href="/bs/brand/<?php echo e($brand['slug']); ?>" class="d-block p-3 brand-card"><img src="<?php echo e($brand['brand_logo_url']); ?>" alt="<?php echo e($brand['name']); ?> Logo" class="img-fluid" style="max-height: 40px; max-width: 100%;"></a></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- NEWSLETTER CTA SECTION -->
<section class="py-5">
    <div class="container">
        <div class="cta-section p-5 text-center" data-aos="flip-up"><h2 class="h1 fw-bold">Never Miss a Deal!</h2><p class="lead my-3">Subscribe to our newsletter to get the latest deals and trending products delivered straight to your inbox.</p><div class="col-lg-6 mx-auto"><form><div class="input-group"><input type="email" class="form-control form-control-lg" placeholder="Enter your email" required><button class="btn btn-dark" type="submit">Subscribe</button></div></form></div></div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>