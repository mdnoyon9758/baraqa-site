<?php
// Core application file is loaded first
require_once __DIR__ . '/../includes/app.php';

// Check if the admin is logged in
require_login();

// Get the user ID from the URL and validate it
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    set_flash_message('Invalid User ID.', 'danger');
    header('Location: users.php');
    exit();
}

// Fetch user details from the database using PDO
try {
    $stmt = $pdo->prepare("SELECT id, name, email, created_at, status FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch user's orders (this is for future use, initially will be empty)
    // $order_stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
    // $order_stmt->execute([$user_id]);
    // $orders = $order_stmt->fetchAll(PDO::FETCH_ASSOC);
    $orders = []; // Placeholder for orders

} catch (PDOException $e) {
    set_flash_message('Error fetching user data: ' . $e->getMessage(), 'danger');
    header('Location: users.php');
    exit();
}

// If no user is found with that ID, redirect back
if (!$user) {
    set_flash_message('No user found with the specified ID.', 'danger');
    header('Location: users.php');
    exit();
}

// Set page title
$page_title = 'View User: ' . e($user['name']);

// Include admin header
require_once 'includes/admin_header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">User Details</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="users.php">Users</a></li>
        <li class="breadcrumb-item active"><?php echo e($user['name']); ?></li>
    </ol>

    <div class="row">
        <!-- User Profile Card -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <img src="https://via.placeholder.com/150" alt="User Avatar" class="rounded-circle img-fluid" style="width: 150px;">
                    <h5 class="my-3"><?php echo e($user['name']); ?></h5>
                    <p class="text-muted mb-1">User ID: <?php echo $user['id']; ?></p>
                    <p class="text-muted mb-4">Status: 
                        <?php if ($user['status'] === 'suspended'): ?>
                            <span class="badge bg-warning text-dark">Suspended</span>
                        <?php else: ?>
                            <span class="badge bg-success">Active</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- User Information Details -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-3"><p class="mb-0 fw-bold">Full Name</p></div>
                        <div class="col-sm-9"><p class="text-muted mb-0"><?php echo e($user['name']); ?></p></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3"><p class="mb-0 fw-bold">Email</p></div>
                        <div class="col-sm-9"><p class="text-muted mb-0"><?php echo e($user['email']); ?></p></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-3"><p class="mb-0 fw-bold">Registration Date</p></div>
                        <div class="col-sm-9"><p class="text-muted mb-0"><?php echo date('d F Y, h:i A', strtotime($user['created_at'])); ?></p></div>
                    </div>
                </div>
            </div>
            
            <!-- User Order History Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-history me-1"></i>
                    Order History
                </div>
                <div class="card-body">
                    <?php if (empty($orders)): ?>
                        <p class="text-center text-muted">This user has not placed any orders yet.</p>
                    <?php else: ?>
                        <!-- We will implement the order list table here in the future -->
                        <table class="table">
                            <thead>
                                <tr><th>Order ID</th><th>Date</th><th>Total</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <!-- Loop through orders here -->
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Admin footer
require_once 'includes/admin_footer.php'; 
?>