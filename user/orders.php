<?php
require_once __DIR__ . '/../includes/app.php';

if (!is_user_logged_in()) {
    header('Location: /auth/login');
    exit();
}

$page_title = 'My Orders';
require_once __DIR__ . '/../includes/header.php';

$user_id = $_SESSION['user_id'];
$orders = []; // Initially empty, will be populated from DB later
?>

<!-- Custom styles -->
<style>
    .order-card { border: 1px solid #dee2e6; border-radius: 0.375rem; margin-bottom: 1.5rem; background-color: #fff; }
    .order-card-header { background-color: #f8f9fa; padding: 0.75rem 1.25rem; border-bottom: 1px solid #dee2e6; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 1rem; font-size: 0.85rem; color: #6c757d; }
    .order-card-body { padding: 1.25rem; }
    .order-product-item { display: flex; gap: 1rem; margin-bottom: 1rem; }
    .order-product-item:last-child { margin-bottom: 0; }
    .order-product-image { width: 80px; height: 80px; object-fit: cover; }
    .order-product-details { flex-grow: 1; }
    .order-product-details a { font-weight: 600; color: #0d6efd; text-decoration: none; }
    .order-product-details a:hover { text-decoration: underline; }
    .order-actions .btn { margin-bottom: 0.5rem; width: 100%; }
</style>

<main class="container my-5">
    <div class="row">
        <aside class="col-md-3 account-sidebar">
            <div class="list-group">
                <a href="/user/dashboard" class="list-group-item list-group-item-action"><i class="fas fa-user-circle fa-fw me-2"></i>My Account</a>
                <a href="/user/orders" class="list-group-item list-group-item-action active" aria-current="true"><i class="fas fa-box fa-fw me-2"></i>My Orders</a>
                <a href="/user/security" class="list-group-item list-group-item-action"><i class="fas fa-shield-alt fa-fw me-2"></i>Login & Security</a>
                <a href="#" class="list-group-item list-group-item-action disabled" tabindex="-1" aria-disabled="true"><i class="fas fa-map-marker-alt fa-fw me-2"></i>Your Addresses</a>
                <a href="/auth/logout.php" class="list-group-item list-group-item-action text-danger"><i class="fas fa-sign-out-alt fa-fw me-2"></i>Logout</a>
            </div>
        </aside>

        <section class="col-md-9">
            <h1 class="h3 mb-4">Your Orders</h1>

            <ul class="nav nav-tabs mb-4">
                <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">All Orders</a></li>
                <li class="nav-item"><a class="nav-link disabled" href="#">In Progress</a></li>
                <li class="nav-item"><a class="nav-link disabled" href="#">Completed</a></li>
            </ul>

            <?php if (empty($orders)): ?>
                <div class="alert alert-info text-center" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    You have not placed any orders yet. <a href="/" class="alert-link">Start shopping now!</a>
                </div>
            <?php else: ?>
                <!-- Loop through actual orders here -->
            <?php endif; ?>

        </section>
    </div>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php'; 
?>