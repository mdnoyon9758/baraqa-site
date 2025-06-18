<?php
// Core application file handles session, db, and functions
require_once __DIR__ . '/../includes/app.php';

// Check if the user is logged in
if (!is_user_logged_in()) {
    header('Location: /auth/login');
    exit();
}

// Set page title for the header
$page_title = 'My Account';

// User-specific header
require_once __DIR__ . '/../includes/header.php';

$user_id = $_SESSION['user_id'];
$user_name = '';

// Fetch user's name using PDO
try {
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $user_name = e($user['name']);
    } else {
        // If user not found, something is wrong, log them out.
        session_destroy();
        header('Location: /auth/login');
        exit();
    }
} catch (PDOException $e) {
    // Gracefully handle error and show a generic message
    echo "There was an error loading your dashboard. Please try again later.";
    // Optional: Log the detailed error for the admin
    error_log($e->getMessage());
    exit();
}
?>

<!-- Your HTML and CSS for the dashboard -->
<style>
    .account-hub-card { border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 1.5rem; height: 100%; transition: box-shadow 0.2s ease-in-out, transform 0.2s ease; background-color: #fff; }
    .account-hub-card:hover { transform: translateY(-5px); box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); }
    .account-hub a { text-decoration: none; color: inherit; }
    .account-hub a.disabled-link { pointer-events: none; opacity: 0.65; }
    .account-hub-card .icon { font-size: 2.5rem; color: #495057; }
    .account-hub-card h5 { font-size: 1.1rem; font-weight: 600; margin-bottom: 0.25rem; }
    .account-hub-card p { font-size: 0.9rem; color: #6c757d; margin-bottom: 0; }
    @media (max-width: 767.98px) { .account-sidebar { margin-bottom: 2rem; } }
</style>

<main class="container my-5">
    <div class="row">
        <aside class="col-md-3 account-sidebar">
            <div class="list-group">
                <a href="/user/dashboard" class="list-group-item list-group-item-action active" aria-current="true"><i class="fas fa-user-circle fa-fw me-2"></i>My Account</a>
                <a href="/user/orders" class="list-group-item list-group-item-action"><i class="fas fa-box fa-fw me-2"></i>My Orders</a>
                <a href="/user/security" class="list-group-item list-group-item-action"><i class="fas fa-shield-alt fa-fw me-2"></i>Login & Security</a>
                <a href="#" class="list-group-item list-group-item-action disabled" tabindex="-1" aria-disabled="true"><i class="fas fa-map-marker-alt fa-fw me-2"></i>Your Addresses</a>
                <a href="/auth/logout.php" class="list-group-item list-group-item-action text-danger"><i class="fas fa-sign-out-alt fa-fw me-2"></i>Logout</a>
            </div>
        </aside>

        <section class="col-md-9">
            <h1 class="h3 mb-4">Hello, <?php echo $user_name; ?>!</h1>
            <div class="row row-cols-1 row-cols-lg-2 g-4 account-hub">
                <div class="col">
                    <a href="/user/orders">
                        <div class="account-hub-card"><div class="d-flex align-items-center"><i class="fas fa-box icon me-4"></i><div><h5>Your Orders</h5><p>Track, return, or see details of your orders.</p></div></div></div>
                    </a>
                </div>
                <div class="col">
                    <a href="/user/security">
                        <div class="account-hub-card"><div class="d-flex align-items-center"><i class="fas fa-shield-alt icon me-4"></i><div><h5>Login & Security</h5><p>Edit login, name, and password.</p></div></div></div>
                    </a>
                </div>
                <div class="col">
                    <a href="#" class="disabled-link">
                        <div class="account-hub-card"><div class="d-flex align-items-center"><i class="fas fa-map-marker-alt icon me-4"></i><div><h5>Your Addresses</h5><p>Manage and edit your shipping addresses.</p></div></div></div>
                    </a>
                </div>
                <div class="col">
                     <a href="#" class="disabled-link">
                        <div class="account-hub-card"><div class="d-flex align-items-center"><i class="fas fa-credit-card icon me-4"></i><div><h5>Payment Options</h5><p>Add or edit your payment methods.</p></div></div></div>
                    </a>
                </div>
            </div>
        </section>
    </div>
</main>

<?php
// Site footer
require_once __DIR__ . '/../includes/footer.php'; 
?>