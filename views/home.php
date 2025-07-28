<?php
$page_title = 'AI Curated Deals & Trends';

try {
    // 1. Hero Carousel: Top deals with high discounts
    $hero_deals_stmt = $pdo->query("SELECT id, title, slug, image_url, price, discount_percentage FROM products WHERE status = 'published' AND discount_percentage >= 25 ORDER BY trend_score DESC, discount_percentage DESC LIMIT 5");
    $hero_deals = $hero_deals_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Category Showcase: Fetch a few parent categories to showcase with multiple images.
    $showcase_categories_stmt = $pdo->query("SELECT id, name, slug FROM categories WHERE parent_id IS NULL AND is_featured = 1 AND status = 'published' LIMIT 4");
    $showcase_categories = $showcase_categories_stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($showcase_categories as $key => $category) {
        $product_images_stmt = $pdo->prepare("SELECT image_url FROM products WHERE category_id = ? AND status = 'published' AND image_url IS NOT NULL LIMIT 4");
        $product_images_stmt->execute([$category['id']]);
        $showcase_categories[$key]['product_images'] = $product_images_stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // 3. Dynamic Product Carousels by Category
    $homepage_sections = [];
    $product_carousels_data = [];
    
    // Fetch a few more featured categories to create product rows for them
    $carousel_categories_stmt = $pdo->query("SELECT id, name, slug FROM categories WHERE is_featured = 1 AND status = 'published' LIMIT 3");
    $homepage_sections = $carousel_categories_stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($homepage_sections as $section) {
        $products_stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ? AND status = 'published' ORDER BY trend_score DESC LIMIT 10");
        $products_stmt->execute([$section['id']]);
        $product_carousels_data[$section['slug']] = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    // Graceful error handling
    $hero_deals = []; $showcase_categories = []; $homepage_sections = []; $product_carousels_data = [];
    error_log("Homepage data fetch failed: " . $e->getMessage());
}

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Hero Section Carousel -->
<section class="hero-section">
    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
            <?php if (!empty($hero_deals)): ?>
                <?php foreach ($hero_deals as $index => $deal): ?>
                    <div class="carousel-item <?php echo ($index == 0) ? 'active' : ''; ?>">
                        <div class="hero-slide-item" style="background-image: url('<?php echo e($deal['image_url']); ?>');">
                            <div class="hero-slide-overlay"></div>
                            <div class="container text-center text-white">
                                <span class="badge bg-danger mb-2 fs-6">Save <?php echo e($deal['discount_percentage']); ?>% Today</span>
                                <h1 class="display-4 fw-bold"><?php echo e($deal['title']); ?></h1>
                                <a href="/product/<?php echo e($deal['slug']); ?>" class="btn btn-primary btn-lg mt-3">Shop This Deal <i class="bi bi-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="carousel-item active"><div class="hero-slide-item" style="background-color: #232f3e;"><div class="container text-center text-white"><h1 class="display-4 fw-bold">Smart Shopping, Smarter Savings</h1><p class="lead">Discover AI-powered deals and trending products daily.</p></div></div></div>
            <?php endif; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
    </div>
</section>

<div class="container main-container-amazon">
    <!-- Category Showcase Section -->
    <?php if (!empty($showcase_categories)): ?>
    <section class="py-5">
        <div class="row g-4">
            <?php foreach ($showcase_categories as $category): ?>
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 category-showcase-card">
                    <div class="card-body">
                        <h5 class="card-title fw-bold"><?php echo e($category['name']); ?></h5>
                        <div class="row g-2 mt-3">
                            <?php for ($i=0; $i < 4; $i++): ?>
                            <div class="col-6">
                                <a href="/category/<?php echo e($category['slug']); ?>">
                                    <img src="<?php echo e($category['product_images'][$i] ?? 'https://placehold.co/200x200/e2e8f0/333?text=View'); ?>" class="img-fluid rounded" alt="Product in <?php echo e($category['name']); ?>">
                                </a>
                            </div>
                            <?php endfor; ?>
                        </div>
                        <a href="/category/<?php echo e($category['slug']); ?>" class="stretched-link mt-3 d-inline-block">Shop now</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Dynamic Product Carousels by Category -->
    <?php foreach ($homepage_sections as $section): ?>
        <?php if (!empty($product_carousels_data[$section['slug']])): ?>
        <section class="py-4">
            <h2 class="section-title h4 mb-3">
                <span>Top Deals in <?php echo e($section['name']); ?></span>
                <a href="/category/<?php echo e($section['slug']); ?>" class="float-end small">See all</a>
            </h2>
            <div class="product-carousel">
                <?php foreach ($product_carousels_data[$section['slug']] as $product): ?>
                    <div class="p-2">
                        <?php include __DIR__ . '/../includes/_product_card.php'; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- Customer Testimonials Section -->
    <section class="py-5">
        <h2 class="section-title h3 mb-4">
            <span>What Our Customers Say</span>
        </h2>
        <div id="testimonialsCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="card testimonial-card text-center p-4">
                                <div class="card-body">
                                    <div class="testimonial-stars text-warning mb-3">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <blockquote class="blockquote mb-3">
                                        <p class="mb-0">"Amazing deals and fast delivery! I've saved hundreds of dollars using this platform. The AI recommendations are spot on!"</p>
                                    </blockquote>
                                    <footer class="blockquote-footer">
                                        <strong>Sarah Johnson</strong>
                                    </footer>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="card testimonial-card text-center p-4">
                                <div class="card-body">
                                    <div class="testimonial-stars text-warning mb-3">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <blockquote class="blockquote mb-3">
                                        <p class="mb-0">"Best shopping experience I've had online. The trending products section helped me discover items I never knew I needed!"</p>
                                    </blockquote>
                                    <footer class="blockquote-footer">
                                        <strong>Michael Chen</strong>
                                    </footer>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="card testimonial-card text-center p-4">
                                <div class="card-body">
                                    <div class="testimonial-stars text-warning mb-3">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="far fa-star"></i>
                                    </div>
                                    <blockquote class="blockquote mb-3">
                                        <p class="mb-0">"Great customer service and reliable products. The wishlist feature makes it easy to keep track of items I want to buy later."</p>
                                    </blockquote>
                                    <footer class="blockquote-footer">
                                        <strong>Emily Rodriguez</strong>
                                    </footer>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#testimonialsCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#testimonialsCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </section>

</div>

<?php 
// Pass required JS/CSS to the footer for the new carousels
$extra_js = ['https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js'];
$page_inline_script = "
    // Initialize ALL product carousels
    if (typeof $ !== 'undefined' && $.fn.slick) {
        $('.product-carousel').slick({
            infinite: false,
            slidesToShow: 4,
            slidesToScroll: 2,
            dots: false,
            arrows: true,
            responsive: [
                { breakpoint: 1200, settings: { slidesToShow: 3, slidesToScroll: 1 } },
                { breakpoint: 992, settings: { slidesToShow: 2, slidesToScroll: 1 } },
                { breakpoint: 576, settings: { slidesToShow: 1, slidesToScroll: 1 } }
            ]
        });
    }
";

// Add some custom CSS for new components
$page_styles = "
.category-showcase-card .card-body { padding: 1.5rem; }
.category-showcase-card img { aspect-ratio: 1/1; object-fit: cover; }
.product-carousel .slick-prev, .product-carousel .slick-next {
    background-color: white;
    border: 1px solid #ddd;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    z-index: 10;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.product-carousel .slick-prev:before, .product-carousel .slick-next:before {
    font-family: 'bootstrap-icons';
    color: #333;
    font-size: 20px;
}
.product-carousel .slick-prev:before { content: '\\f284'; }
.product-carousel .slick-next:before { content: '\\f285'; }
.product-carousel .slick-disabled { opacity: 0.3; }

/* Testimonials Section */
.testimonial-card {
    background-color: #fff;
    border: 1px solid #e3e6f0;
    border-radius: 10px;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    transition: transform 0.3s ease;
}
.testimonial-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.25rem 2rem 0 rgba(58, 59, 69, 0.2);
}
.testimonial-stars {
    font-size: 1.2rem;
}
#testimonialsCarousel .carousel-control-prev,
#testimonialsCarousel .carousel-control-next {
    width: 5%;
    color: #800080;
}
#testimonialsCarousel .carousel-control-prev-icon,
#testimonialsCarousel .carousel-control-next-icon {
    background-color: #800080;
    border-radius: 50%;
    padding: 20px;
}
";

require_once __DIR__ . '/../includes/footer.php'; 
?>