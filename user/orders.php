<?php
session_start();
require_once __DIR__ . '/../includes/app.php';
require_once '../includes/db_connect.php'; 
require_once '../includes/header.php'; 

// ব্যবহারকারী লগইন করা আছে কিনা তা পরীক্ষা করা
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// ভবিষ্যতের জন্য: ডাটাবেস থেকে এই ব্যবহারকারীর অর্ডারগুলো আনা হবে
// $query = "SELECT * FROM orders WHERE user_id = $1 ORDER BY order_date DESC";
// $result = pg_query_params($db_conn, $query, array($user_id));
// $orders = pg_fetch_all($result);
$orders = []; // আপাতত খালি অ্যারে, কারণ অর্ডার সিস্টেম এখনো তৈরি হয়নি

?>

<!-- অর্ডার পেজের জন্য কাস্টম স্টাইল -->
<style>
    .order-card {
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        margin-bottom: 1.5rem;
        background-color: #fff;
    }
    .order-card-header {
        background-color: #f8f9fa;
        padding: 0.75rem 1.25rem;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        font-size: 0.85rem;
        color: #6c757d;
    }
    .order-card-body {
        padding: 1.25rem;
    }
    .order-product-item {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .order-product-item:last-child {
        margin-bottom: 0;
    }
    .order-product-image {
        width: 80px;
        height: 80px;
        object-fit: cover;
    }
    .order-product-details {
        flex-grow: 1;
    }
    .order-product-details a {
        font-weight: 600;
        color: #0d6efd;
        text-decoration: none;
    }
    .order-product-details a:hover {
        text-decoration: underline;
    }
    .order-actions .btn {
        margin-bottom: 0.5rem;
        width: 100%;
    }
</style>

<main class="container my-5">
    <div class="row">
        <!-- বাম কলাম: সাইডবার মেনু -->
        <aside class="col-md-3 account-sidebar">
            <div class="list-group">
                <a href="/user/dashboard" class="list-group-item list-group-item-action">
                    <i class="fas fa-user-circle fa-fw me-2"></i>My Account
                </a>
                <a href="/user/orders" class="list-group-item list-group-item-action active" aria-current="true">
                    <i class="fas fa-box fa-fw me-2"></i>My Orders
                </a>
                <a href="/user/security" class="list-group-item list-group-item-action">
                    <i class="fas fa-shield-alt fa-fw me-2"></i>Login & Security
                </a>
                <a href="#" class="list-group-item list-group-item-action disabled" tabindex="-1" aria-disabled="true">
                    <i class="fas fa-map-marker-alt fa-fw me-2"></i>Your Addresses
                </a>
                <a href="/auth/logout.php" class="list-group-item list-group-item-action text-danger">
                    <i class="fas fa-sign-out-alt fa-fw me-2"></i>Logout
                </a>
            </div>
        </aside>

        <!-- ডান কলাম: অর্ডার তালিকা -->
        <section class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Your Orders</h1>
                <!-- ভবিষ্যতে সার্চ বার যোগ করা যেতে পারে -->
            </div>

            <!-- অর্ডার ফিল্টার করার জন্য ট্যাব -->
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="#">All Orders</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link disabled" href="#">In Progress</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link disabled" href="#">Completed</a>
                </li>
            </ul>

            <?php if (empty($orders)): ?>
                <!-- যখন কোনো অর্ডার থাকবে না -->
                <div class="alert alert-info text-center" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    You have not placed any orders yet. <a href="/" class="alert-link">Start shopping now!</a>
                </div>

                <!-- ডিজাইনের ডেমো হিসেবে একটি উদাহরণ অর্ডার কার্ড (এটি আপনি পরে মুছে ফেলতে পারেন) -->
                <h5 class="text-muted mt-5 mb-3">Example Order Layout:</h5>
                <div class="order-card">
                    <div class="order-card-header">
                        <div>
                            <span class="fw-bold">ORDER PLACED</span><br>
                            15 October 2023
                        </div>
                        <div>
                            <span class="fw-bold">TOTAL</span><br>
                            $124.98
                        </div>
                        <div>
                            <span class="fw-bold">SHIP TO</span><br>
                            Your Name
                        </div>
                        <div>
                            <span class="fw-bold">ORDER #</span><br>
                             111-222-3333444
                        </div>
                    </div>
                    <div class="order-card-body">
                        <h5 class="fw-bold mb-3">Delivered 18-Oct-2023</h5>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="order-product-item">
                                    <img src="https://via.placeholder.com/80" alt="Product Image" class="order-product-image rounded">
                                    <div class="order-product-details">
                                        <a href="#">Example Product Name - High-Quality Wireless Headphone</a>
                                        <p class="text-muted small">Sold by: BARAQA Store</p>
                                        <p class="fw-bold text-danger">$99.99</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 order-actions">
                                <button class="btn btn-primary btn-sm">Track Package</button>
                                <button class="btn btn-secondary btn-sm">View Invoice</button>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- এখানে লুপ চালিয়ে আসল অর্ডারগুলো দেখানো হবে -->
                <?php foreach ($orders as $order): ?>
                    <!-- এই অংশটি পরে ডাটাবেস থেকে পাওয়া ডেটা দিয়ে পূর্ণ করা হবে -->
                <?php endforeach; ?>
            <?php endif; ?>

        </section>
    </div>
</main>

<?php
require_once '../includes/footer.php'; 
?>