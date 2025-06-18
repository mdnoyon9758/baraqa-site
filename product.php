<?php
// Our front controller (index.php) handles app initialization.

// --- 1. Get Product Slug and Fetch Data ---
$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    http_response_code(400);
    require __DIR__ . '/views/404.php'; // Show 404 for invalid request
    exit;
}

try {
    // Fetch main product data with category and brand names
    $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug, b.name as brand_name, b.slug as brand_slug
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE p.slug = :slug AND p.is_published = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['slug' => $slug]);
    $product = $stmt->fetch();

    if (!$product) {
        http_response_code(404);
        require __DIR__ . '/views/404.php'; // Use our standard 404 page
        exit;
    }

    $page_title = $product['title'];
    $product_id = $product['id'];

    // Fetch gallery images safely
    $gallery_stmt = $pdo->prepare("SELECT image_url FROM product_gallery WHERE product_id = ? ORDER BY id ASC");
    $gallery_stmt->execute([$product_id]);
    $gallery_images = $gallery_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Add the main image to the beginning of the gallery array
    array_unshift($gallery_images, $product['image_url']);
    $gallery_images = array_unique($gallery_images); // Remove duplicates if main image is also in gallery

    // Fetch price history for the chart
    $history_stmt = $pdo->prepare("SELECT price, check_date FROM price_history WHERE product_id = ? ORDER BY check_date ASC");
    $history_stmt->execute([$product_id]);
    $price_history = $history_stmt->fetchAll();
    $price_history_labels = json_encode(array_map(fn($h) => date("M d", strtotime($h['check_date'])), $price_history));
    $price_history_data = json_encode(array_column($price_history, 'price'));

    // Fetch related products from the same category
    $related_products = [];
    if ($product['category_id']) {
        $related_stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? AND is_published = 1 ORDER BY trend_score DESC LIMIT 4");
        $related_stmt->execute([$product['category_id'], $product_id]);
        $related_products = $related_stmt->fetchAll();
    }

} catch (PDOException $e) { /* ... Error Handling ... */ }

require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="card p-lg-4 border-0 shadow-sm">
        <div class="row g-5">
            <!-- Product Gallery Column -->
            <div class="col-lg-6">
                <div class="main-image-container mb-3 text-center">
                    <!-- Use a reliable placeholder if no images are available -->
                    <img id="mainImage" src="<?php echo e(!empty($gallery_images[0]) ? $gallery_images[0] : 'https://placehold.co/600x600/e2e8f0/333?text=No+Image'); ?>" class="img-fluid rounded shadow-sm" alt="<?php echo e($product['title']); ?>">
                </div>
                <?php if (count($gallery_images) > 1): ?>
                <div class="row g-2 thumbnail-strip">
                    <?php foreach ($gallery_images as $index => $img_url): ?>
                    <div class="col-3"><img src="<?php echo e($img_url); ?>" onclick="changeImage(this)" class="img-thumbnail w-100 cursor-pointer <?php echo $index === 0 ? 'active' : ''; ?>" alt="Thumbnail <?php echo $index + 1; ?>"></div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Product Details Column -->
            <div class="col-lg-6">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-light p-2 rounded-2">
                        <!-- PATH FIX: Root-relative paths -->
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item"><a href="/category/<?php echo e($product['category_slug']); ?>"><?php echo e($product['category_name']); ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo e(substr($product['title'], 0, 40)); ?>...</li>
                    </ol>
                </nav>

                <h1 class="display-5 fw-bold"><?php echo e($product['title']); ?></h1>
                <!-- PATH FIX: Root-relative path -->
                <div class="mb-2"><a href="/brand/<?php echo e($product['brand_slug']); ?>" class="text-muted text-decoration-none">Brand: <?php echo e($product['brand_name']); ?></a></div>
                <div class="d-flex align-items-center my-3">
                    <div class="rating-stars me-2 text-warning"><?php echo str_repeat('★', round($product['rating'])); ?><?php echo str_repeat('☆', 5 - round($product['rating'])); ?></div>
                    <span class="text-muted">(<?php echo e($product['reviews_count'] ?? 0); ?> Reviews)</span>
                </div>
                
                <p class="fs-1 fw-bolder text-primary mb-3">$<?php echo e($product['price']); ?></p>
                
                <div class="stock-status fs-5 mb-4 fw-bold <?php echo ($product['stock_quantity'] > 0) ? 'text-success' : 'text-danger'; ?>">
                    <?php echo ($product['stock_quantity'] > 0) ? '<i class="fas fa-check-circle me-2"></i>In Stock' : '<i class="fas fa-times-circle me-2"></i>Out of Stock'; ?>
                    <?php if ($product['stock_quantity'] > 0 && $product['stock_quantity'] < 10): ?> <small class="text-warning ms-2">(Only <?php echo e($product['stock_quantity']); ?> left!)</small> <?php endif; ?>
                </div>

                <p class="lead"><?php echo nl2br(e($product['description'])); ?></p>
                
                <div class="d-grid gap-2 mt-4">
                    <a href="<?php echo e($product['affiliate_link']); ?>" target="_blank" rel="noopener noreferrer" 
                       class="btn btn-success btn-lg track-click <?php echo ($product['stock_quantity'] <= 0) ? 'disabled' : ''; ?>"
                       data-product-id="<?php echo e($product['id']); ?>">
                        <i class="fas fa-shopping-cart me-2"></i> BUY NOW on <?php echo e(ucfirst($product['platform'] ?? 'Store')); ?>
                    </a>
                    <button class="btn btn-outline-danger wishlist-btn" data-product-id="<?php echo e($product['id']); ?>">
                        <i class="<?php echo isset($_SESSION['wishlist']) && in_array($product['id'], $_SESSION['wishlist']) ? 'fas' : 'far'; ?> fa-heart me-2"></i> <span><?php echo isset($_SESSION['wishlist']) && in_array($product['id'], $_SESSION['wishlist']) ? 'In Wishlist' : 'Add to Wishlist'; ?></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Info Tabs -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card p-2 shadow-sm">
                <ul class="nav nav-tabs" id="productTabs" role="tablist">
                    <li class="nav-item" role="presentation"><button class="nav-link active" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">Price History</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="related-tab" data-bs-toggle="tab" data-bs-target="#related" type="button" role="tab">Related Products</button></li>
                </ul>
                <div class="tab-content p-3" id="productTabsContent">
                    <div class="tab-pane fade show active" id="history" role="tabpanel">
                        <?php if (!empty($price_history)): ?> <canvas id="priceChart"></canvas>
                        <?php else: ?> <p class="text-muted text-center p-4">No price history available.</p> <?php endif; ?>
                    </div>
                    <div class="tab-pane fade" id="related" role="tabpanel">
                        <?php if (!empty($related_products)): ?>
                            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                                <?php foreach ($related_products as $related_item) { $product = $related_item; include __DIR__ . '/includes/_product_card.php'; } ?>
                            </div>
                        <?php else: ?> <p class="text-muted text-center p-4">No related products found.</p> <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function changeImage(thumbnailElement) {
        // ... (JS code remains the same)
    }
    document.addEventListener('DOMContentLoaded', function() {
        // ... (Chart JS code remains the same)
    });
</script>