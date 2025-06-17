<?php
if (!isset($product) || !is_array($product)) {
    return;
}

// Ensure essential variables are set, with defaults
$product['id'] = $product['id'] ?? 0;
$product['slug'] = $product['slug'] ?? 'na';
$product['title'] = $product['title'] ?? 'Untitled Product';
$product['price'] = $product['price'] ?? 0.00;
$product['rating'] = $product['rating'] ?? 0;
$product['discount_percentage'] = $product['discount_percentage'] ?? 0;
$product['stock_quantity'] = $product['stock_quantity'] ?? 0;
$product['image_url'] = $product['image_url'] ?? '';

$original_price = null;
if ($product['discount_percentage'] > 0) {
    $original_price = round($product['price'] / (1 - ($product['discount_percentage'] / 100)), 2);
}

$image_to_display = !empty($product['image_url']) ? $product['image_url'] : "https://via.placeholder.com/400x400.png?text=No+Image";
$is_in_wishlist = isset($_SESSION['wishlist']) && in_array($product['id'], $_SESSION['wishlist']);

// --- New SEO-friendly URL ---
$product_url = "/bs/product/" . e($product['slug']); 
?>
<div class="col">
    <div class="card h-100 product-card shadow-sm border-0 position-relative">
        
        <div class="product-badges position-absolute top-0 end-0 m-2" style="z-index: 10;">
            <?php if ($original_price): ?>
                <span class="badge bg-danger"><?php echo e($product['discount_percentage']); ?>% OFF</span>
            <?php endif; ?>
            <?php if ($product['stock_quantity'] > 0 && $product['stock_quantity'] < 10): ?>
                 <span class="badge bg-warning text-dark">Low Stock</span>
            <?php endif; ?>
        </div>
        
        <button class="btn wishlist-btn position-absolute top-0 start-0 m-2 <?php echo $is_in_wishlist ? 'active' : ''; ?>" 
                data-product-id="<?php echo e($product['id']); ?>" 
                title="Add to Wishlist"
                style="z-index: 10; background-color: rgba(255,255,255,0.7);">
            <i class="<?php echo $is_in_wishlist ? 'fas' : 'far'; ?> fa-heart text-danger"></i>
        </button>

        <a href="<?php echo $product_url; ?>">
            <img src="<?php echo e($image_to_display); ?>" class="card-img-top" alt="<?php echo e($product['title']); ?>" loading="lazy">
        </a>
        
        <div class="card-body d-flex flex-column pb-0">
            <h5 class="card-title product-title flex-grow-1 mb-2">
                <a href="<?php echo $product_url; ?>" class="text-dark text-decoration-none">
                    <?php echo e($product['title']); ?>
                </a>
            </h5>

            <div class="rating-stars mb-2">
                <?php for($i = 1; $i <= 5; $i++): ?>
                    <i class="fa-star <?php echo ($i <= round($product['rating'])) ? 'fas text-warning' : 'far text-muted'; ?>"></i>
                <?php endfor; ?>
                <small class="text-muted">(<?php echo e($product['rating']); ?>)</small>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mt-auto">
                <p class="card-text fs-5 fw-bold text-primary mb-0">$<?php echo e($product['price']); ?></p>
                <?php if ($original_price): ?>
                    <p class="card-text text-muted text-decoration-line-through mb-0">$<?php echo e($original_price); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card-footer bg-transparent border-0 text-center pt-2 pb-3">
             <a href="<?php echo $product_url; ?>" class="btn btn-sm btn-outline-primary w-100">View Details</a>
        </div>
    </div>
</div>