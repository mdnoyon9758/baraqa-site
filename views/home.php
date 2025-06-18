<?php
// --- 1. Initialization and Data Fetching ---
$page_title = 'AI Curated Deals & Trends';

try {
    // Data for Hero Carousel (Top Deals with high discount)
    $top_deals_stmt = $pdo->query("SELECT * FROM products WHERE is_published = 1 AND discount_percentage >= 20 ORDER BY discount_percentage DESC, trend_score DESC LIMIT 8");
    $top_deals = $top_deals_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Data for "Shop by Category" section (with images)
    // We assume an 'image_url' column exists in the 'categories' table.
    $featured_categories_stmt = $pdo->query("SELECT id, name, slug, image_url FROM categories WHERE is_featured = 1 AND parent_id IS NULL AND is_published = 1 ORDER BY menu_order ASC LIMIT 8");
    $featured_categories = $featured_categories_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Data for "Deal of the Day"
    $deal_of_the_day_stmt = $pdo->query("SELECT * FROM products WHERE is_published = 1 ORDER BY discount_percentage DESC, created_at DESC LIMIT 1");
    $deal_of_the_day = $deal_of_the_day_stmt->fetch(PDO::FETCH_ASSOC);

    // Data for "Featured Product" - can be based on trend score or a manual flag
    $featured_product_stmt = $pdo->query("SELECT * FROM products WHERE is_published = 1 ORDER BY trend_score DESC, rating DESC LIMIT 1");
    $featured_product = $featured_product_stmt->fetch(PDO::FETCH_ASSOC);

    // Data for Brand Carousel
    $top_brands = $pdo->query("SELECT * FROM brands WHERE is_featured = 1 AND brand_logo_url IS NOT NULL LIMIT 16")->fetchAll(PDO::FETCH_ASSOC);

    // Data for "More Products" grid
    $more_products = $pdo->query("SELECT * FROM products WHERE is_published = 1 ORDER BY RANDOM() LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Graceful error handling in case of DB failure
    $top_deals = []; $featured_categories = []; $deal_of_the_day = null; $featured_product = null; $top_brands = []; $more_products = [];
    error_log("Homepage data fetch failed: " . $e->getMessage());
}

// Include the header file
require_once __DIR__ . '/../includes/header.php';
?>

<!-- =======================
Hero Section with Carousel
======================== -->
<section class="hero-section-amazon">
    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
            <?php if (!empty($top_deals)): ?>
                <?php foreach ($top_deals as $index => $deal): ?>
                    <div class="carousel-item <?php echo ($index == 0) ? 'active' : ''; ?>">
                        <div class="hero-slide-item" style="background-image: url('<?php echo e($deal['image_url']); ?>');">
                            <div class="hero-slide-overlay"></div>
                            <div class="container text-center text-white">
                                <span class="badge bg-danger mb-2 fs-6">TOP DEAL</span>
                                <h1 class="display-4 fw-bold"><?php echo e($deal['title']); ?></h1>
                                <p class="lead">Now at <span class="hero-price">$<?php echo e($deal['price']); ?></span> with a whopping <?php echo e($deal['discount_percentage']); ?>% OFF!</p>
                                <a href="/product/<?php echo e($deal['slug']); ?>" class="btn btn-primary btn-lg mt-3">Shop This Deal <i class="fas fa-arrow-right ms-2"></i></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="carousel-item active">
                     <div class="hero-slide-item" style="background-color: #232f3e;">
                        <div class="container text-center text-white">
                            <h1 class="display-4 fw-bold">Smart Shopping, Smarter Savings</h1>
                            <p class="lead">Discover AI-powered deals and trending products daily.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php if(count($top_deals) > 1): ?>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous</span></button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next"><span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next</span></button>
        <?php endif; ?>
    </div>
</section>

<!-- =======================
Shop by Category Section (with Images)
======================== -->
<?php if (!empty($featured_categories)): ?>
<section class="py-5">
    <div class="container">
        <h2 class="text-center section-title mb-4"><span>Shop by Category</span></h2>
        <div class="row row-cols-2 row-cols-md-4 g-4 text-center">
            <?php foreach ($featured_categories as $category): ?>
                <div class="col" data-aos="fade-up">
                    <a href="/category/<?php echo e($category['slug']); ?>" class="category-card-amazon-v2">
                        <div class="category-card-image" style="background-image: url('<?php echo e($category['image_url'] ?? 'https://placehold.co/300x200/e2e8f0/333?text=Explore'); ?>');"></div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo e($category['name']); ?></h5>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- =======================
Deal of the Day & Featured Product
======================== -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row g-4 align-items-stretch">
            <?php if ($deal_of_the_day): ?>
            <div class="col-lg-6" data-aos="fade-right">
                <div class="p-4 rounded-3 deal-card-amazon h-100">
                    <h2>Deal of the Day</h2><p>Don't miss today's best offer!</p>
                    <div class="d-flex align-items-center"><img src="<?php echo e($deal_of_the_day['image_url']); ?>" alt="<?php echo e($deal_of_the_day['title']); ?>" class="deal-img me-3"><div><h6><?php echo e($deal_of_the_day['title']); ?></h6><div class="price-container"><span class="deal-price">$<?php echo e($deal_of_the_day['price']); ?></span><span class="text-muted text-decoration-line-through ms-2">$<?php echo round($deal_of_the_day['price'] / (1 - ($deal_of_the_day['discount_percentage'] / 100)), 2); ?></span></div></div></div><a href="/product/<?php echo e($deal_of_the_day['slug']); ?>" class="btn btn-sm btn-dark mt-3">View Deal</a>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($featured_product): ?>
            <div class="col-lg-6" data-aos="fade-left">
                <div class="p-4 rounded-3 featured-card-amazon h-100">
                    <h2>Trending Now</h2><p>Check out what's popular right now.</p>
                     <div class="d-flex align-items-center"><img src="<?php echo e($featured_product['image_url']); ?>" alt="<?php echo e($featured_product['title']); ?>" class="deal-img me-3"><div><h6><?php echo e($featured_product['title']); ?></h6><p class="mb-0">Rating: <?php echo e($featured_product['rating']); ?>/5.0</p></div></div><a href="/product/<?php echo e($featured_product['slug']); ?>" class="btn btn-sm btn-outline-dark mt-3">See Details</a>
                </div>
            </div>
             <?php endif; ?>
        </div>
    </div>
</section>

<!-- =======================
Brand Carousel Section
======================== -->
<?php if (!empty($top_brands)): ?>
<section class="py-5">
    <div class="container">
        <h2 class="text-center section-title mb-5"><span>Trusted Brands</span></h2>
        <div class="brand-carousel" data-aos="fade-up">
            <?php foreach ($top_brands as $brand): ?>
                <div class="brand-item text-center">
                    <a href="/brand/<?php echo e($brand['slug']); ?>"><img src="<?php echo e($brand['brand_logo_url']); ?>" alt="<?php echo e($brand['name']); ?> Logo" class="img-fluid"></a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- =======================
More Products to Discover
======================== -->
<?php if (!empty($more_products)): ?>
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center section-title mb-4"><span>More Products to Discover</span></h2>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($more_products as $index => $product): ?>
                <div data-aos="fade-up" data-aos-delay="<?php echo $index * 50; ?>"><?php include __DIR__ . '/../includes/_product_card.php'; ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- =======================
Final Call to Action (CTA)
======================== -->
<section class="cta-section-final text-white text-center py-5">
    <div class="container" data-aos="zoom-in">
        <h2 class="display-5 fw-bold">Ready to Find Your Next Favorite Thing?</h2>
        <p class="lead col-lg-8 mx-auto">Our AI is constantly discovering new trends and unbeatable deals. Start exploring now and let us guide you to the best products on the web.</p>
        <div class="d-grid gap-2 d-sm-flex justify-content-sm-center mt-4">
            <a href="/category/tech-gadgets" class="btn btn-primary btn-lg px-4 gap-3">Explore Tech</a>
            <a href="/category/fashion-style" class="btn btn-outline-light btn-lg px-4">Discover Fashion</a>
        </div>
    </div>
</section>

<?php 
// Pass required JS/CSS to the footer for the carousel
$extra_js = ['https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js'];
require_once __DIR__ . '/../includes/footer.php'; 
?>