<?php
// Our front controller (index.php) handles app initialization.

$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    http_response_code(400);
    require __DIR__ . '/views/404.php';
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
        require __DIR__ . '/views/404.php';
        exit;
    }

    $page_title = $product['title'];
    $product_id = $product['id'];

    // Fetch gallery images
    $gallery_stmt = $pdo->prepare("SELECT image_url FROM product_gallery WHERE product_id = ? ORDER BY id ASC");
    $gallery_stmt->execute([$product_id]);
    $gallery_images = $gallery_stmt->fetchAll(PDO::FETCH_COLUMN);
    array_unshift($gallery_images, $product['image_url']);
    $gallery_images = array_unique(array_filter($gallery_images));

    // Fetch related products
    $related_products = [];
    if ($product['category_id']) {
        $related_stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? AND is_published = 1 ORDER BY trend_score DESC LIMIT 4");
        $related_stmt->execute([$product['category_id'], $product_id]);
        $related_products = $related_stmt->fetchAll();
    }
    
    // Calculate price details
    $original_price = null;
    if ($product['discount_percentage'] > 0) {
        $original_price = round($product['price'] / (1 - ($product['discount_percentage'] / 100)), 2);
    }

} catch (PDOException $e) { /* Error Handling */ }

require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4 product-page-amazon">
    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <?php if($product['category_name']): ?>
            <li class="breadcrumb-item"><a href="/category/<?php echo e($product['category_slug']); ?>"><?php echo e($product['category_name']); ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page"><?php echo e(substr($product['title'], 0, 50)); ?>...</li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Left Column: Product Gallery -->
        <div class="col-lg-5">
             <div class="product-gallery">
                <div class="main-image-container mb-2">
                    <img id="mainImage" src="<?php echo e(!empty($gallery_images[0]) ? $gallery_images[0] : 'https://placehold.co/600x600/e2e8f0/333?text=No+Image'); ?>" class="img-fluid rounded" alt="<?php echo e($product['title']); ?>">
                </div>
                <?php if (count($gallery_images) > 1): ?>
                <div class="thumbnail-strip">
                    <?php foreach ($gallery_images as $index => $img_url): ?>
                        <img src="<?php echo e($img_url); ?>" onclick="changeImage(this)" class="img-thumbnail thumbnail-item <?php echo $index === 0 ? 'active' : ''; ?>" alt="Thumbnail <?php echo $index + 1; ?>">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Middle Column: Product Details -->
        <div class="col-lg-4">
            <h1 class="product-title-amazon"><?php echo e($product['title']); ?></h1>
            <?php if($product['brand_name']): ?>
            <div class="mb-2"><a href="/brand/<?php echo e($product['brand_slug']); ?>" class="text-decoration-none">Visit the <?php echo e($product['brand_name']); ?> Store</a></div>
            <?php endif; ?>
            <div class="d-flex align-items-center my-2">
                <div class="rating-stars text-warning"><?php echo str_repeat('★', round($product['rating'])); ?><?php echo str_repeat('☆', 5 - round($product['rating'])); ?></div>
                <span class="ms-2 text-muted"><?php echo e($product['reviews_count'] ?? 0); ?> ratings</span>
            </div>
            <hr>
            <!-- Price Section -->
            <div class="price-section">
                <?php if($original_price): ?>
                    <span class="badge bg-danger fs-6 me-2">-<?php echo e($product['discount_percentage']); ?>%</span>
                <?php endif; ?>
                <span class="product-price-amazon">$<?php echo e($product['price']); ?></span>
                <?php if($original_price): ?>
                    <div class="text-muted small">List Price: <span class="text-decoration-line-through">$<?php echo e($original_price); ?></span></div>
                <?php endif; ?>
            </div>
            <hr>
            <!-- About this item Section -->
            <h5 class="fw-bold">About this item</h5>
            <div class="product-description-amazon">
                <?php echo nl2br(e($product['description'])); ?>
            </div>
        </div>

        <!-- Right Column: Buy Box -->
        <div class="col-lg-3">
            <div class="card buy-box-amazon p-3">
                <div class="card-body">
                    <div class="price-section-buybox mb-2">
                        <span class="product-price-amazon">$<?php echo e($product['price']); ?></span>
                    </div>
                    <div class="delivery-info">
                        <p class="mb-1"><small>Get it as soon as <strong class="text-success">tomorrow, <?php echo date('M. d', strtotime('+1 day')); ?></strong></small></p>
                        <p class="mb-2"><small><i class="fas fa-map-marker-alt me-1"></i> Deliver to Your Location</small></p>
                    </div>
                    <div class="stock-status-amazon mb-3 <?php echo ($product['stock_quantity'] > 0) ? 'in-stock' : 'out-of-stock'; ?>">
                        <?php echo ($product['stock_quantity'] > 0) ? 'In Stock' : 'Out of Stock'; ?>
                    </div>
                    <div class="d-grid gap-2">
                         <a href="<?php echo e($product['affiliate_link']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary track-click <?php echo ($product['stock_quantity'] <= 0) ? 'disabled' : ''; ?>" data-product-id="<?php echo e($product['id']); ?>">
                            Buy Now
                         </a>
                         <button class="btn btn-secondary wishlist-btn" data-product-id="<?php echo e($product['id']); ?>">
                            <i class="<?php echo isset($_SESSION['wishlist']) && in_array($product['id'], $_SESSION['wishlist']) ? 'fas' : 'far'; ?> fa-heart"></i> <span><?php echo isset($_SESSION['wishlist']) && in_array($product['id'], $_SESSION['wishlist']) ? 'In Wishlist' : 'Add to Wishlist'; ?></span>
                         </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Related Products -->
    <?php if (!empty($related_products)): ?>
    <hr class="my-5">
    <div class="related-products-section">
        <h3 class="section-title text-center"><span>Related Products</span></h3>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            <?php foreach ($related_products as $related_item) { $product = $related_item; include __DIR__ . '/includes/_product_card.php'; } ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
    function changeImage(thumbnailElement) {
        const mainImage = document.getElementById('mainImage');
        if (mainImage) {
            mainImage.style.opacity = 0;
            setTimeout(() => { 
                mainImage.src = thumbnailElement.src.replace('150x150', '600x600'); // Optional: Load larger image
                mainImage.style.opacity = 1; 
            }, 200);
            document.querySelectorAll('.thumbnail-strip .thumbnail-item').forEach(img => img.classList.remove('active'));
            thumbnailElement.classList.add('active');
        }
    }
</script>