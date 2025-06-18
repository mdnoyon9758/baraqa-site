<?php
// Core application file handles session, db, and functions
require_once __DIR__ . '/../includes/app.php';

// Check if the user is logged in
if (!is_user_logged_in()) {
    header('Location: /auth/login');
    exit();
}

// User-specific header
require_once __DIR__ . '/../includes/header.php';

$user_id = $_SESSION['user_id'];

// Fetch user's name using PDO
try {
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Gracefully handle error
    $user = null;
}


if (!$user) {
    session_destroy();
    header('Location: /auth/login');
    exit();
}

$user_name = e($user['name']);
?>

<!-- Your HTML and CSS for the dashboard remain the same -->
<style>
    /* ... Your custom styles ... */
</style>

<main class="container my-5">
    <div class="row">
        <!-- Sidebar Menu -->
        <aside class="col-md-3 account-sidebar">
            <div class="list-group">
                <a href="/user/dashboard" class="list-group-item list-group-item-action active" aria-current="true">
                    <i class="fas fa-user-circle fa-fw me-2"></i>My Account
                </a>
                <a href="/user/orders" class="list-group-item list-group-item-action">
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

        <!-- Main Content -->
        <section class="col-md-9">
            <h1 class="h3 mb-4">Hello, <?php echo $user_name; ?>!</h1>
            <div class="row row-cols-1 row-cols-lg-2 g-4 account-hub">
                <!-- Order Card -->
                <div class="col">
                    <a href="/user/orders">
                        <div class="account-hub-card">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-box icon me-4"></i>
                                <div>
                                    <h5>Your Orders</h5>
                                    <p>Track, return, or see details of your orders.</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <!-- Login & Security Card -->
                <div class="col">
                    <a href="/user/security">
                        <div class="account-hub-card">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-shield-alt icon me-4"></i>
                                <div>
                                    <h5>Login & Security</h5>
                                    <p>Edit login, name, and password.</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <!-- Other cards... -->
            </div>
        </section>
    </div>
</main>

<?php
// Site footer
require_once __DIR__ . '/../includes/footer.php'; 
?>