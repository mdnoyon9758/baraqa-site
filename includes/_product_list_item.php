<?php
if (!isset($product) || !is_array($product)) { return; }

// CRITICAL FIX: Removed hardcoded '/bs/' from the product URL
$product_url = "/product/" . ($product['slug'] ?? 'na');
$image_to_display = !empty($product['image_url']) ? $product['image_url'] : "https://via.placeholder.com/150x150.png?text=No+Image";
?>
<div class="card product-list-item border-0 shadow-sm">
    <div class="row g-0">
        <div class="col-4">
            <a href="<?php echo $product_url; ?>"><img src="<?php echo e($image_to_display); ?>" class="img-fluid rounded-start" alt="<?php echo e($product['title']); ?>" loading="lazy"></a>
        </div>
        <div class="col-8">
            <div class="card-body p-2">
                <h6 class="card-title mb-1 small"><a href="<?php echo $product_url; ?>" class="text-dark text-decoration-none"><?php echo e($product['title']); ?></a></h6>
                <p class="card-text fs-6 fw-bold text-primary mb-0">$<?php echo e($product['price']); ?></p>
            </div>
        </div>
    </div>
</div>