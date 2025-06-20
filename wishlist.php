<?php
// Our front controller (index.php) handles app initialization.

$page_title = 'Your Wishlist';

// Get wishlist product IDs from the session.
$wishlist_ids = $_SESSION['wishlist'] ?? [];
$products = [];

if (!empty($wishlist_ids)) {
    try {
        // Create placeholders for the IN clause to prevent SQL injection.
        $placeholders = implode(',', array_fill(0, count($wishlist_ids), '?'));
        
        // Fetch all products that are in the user's wishlist session and are published.
        $sql = "SELECT * FROM products WHERE id IN ($placeholders) AND is_published = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($wishlist_ids);
        $products = $stmt->fetchAll();

    } catch (PDOException $e) {
        // If there's a database error, ensure products array is empty and log the error.
        $products = [];
        error_log('Wishlist page error: ' . $e->getMessage());
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-5">
    <div class="text-center mb-5">
        <h1 class="display-5"><i class="fas fa-heart text-danger me-2"></i>Your Wishlist</h1>
        <p class="lead text-muted">Products you've saved for later.</p>
    </div>

    <?php if (!empty($products)): ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            <?php foreach ($products as $product) {
                // The product card partial is used to display each item consistently.
                include __DIR__ . '/includes/_product_card.php';
            } ?>
        </div>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-info text-center p-5" role="alert">
                <h4 class="alert-heading">Your Wishlist is Empty!</h4>
                <p>You haven't added any products to your wishlist yet. Start exploring and save your favorites!</p>
                <hr>
                <!-- PATH FIX: Link now points to the root (homepage). -->
                <a href="/" class="btn btn-primary">Explore Products</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>