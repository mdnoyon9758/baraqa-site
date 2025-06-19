<?php
// =================================================================
// 1. PAGE CONFIGURATION AND CORE SETUP
// =================================================================
$page_key = 'users'; // Keep the parent menu 'users' (Customers) active
$page_title = 'View Customer';

require_once __DIR__ . '/../includes/app.php';
require_login();

// =================================================================
// 2. DATA FETCHING FOR A SINGLE USER
// =================================================================
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    set_flash_message('Invalid Customer ID.', 'danger');
    header('Location: /admin/users.php');
    exit();
}

try {
    // Fetch the specific user's details
    $stmt = $pdo->prepare("SELECT id, name, email, created_at, status FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no user is found with that ID, redirect back to the user list
    if (!$user) {
        set_flash_message('No customer found with the specified ID.', 'danger');
        header('Location: /admin/users.php');
        exit();
    }
    
    // Fetch the user's recent order history
    $orders = []; // Default to empty array
    // This try-catch block prevents an error if the 'orders' table does not exist yet
    try {
        $order_stmt = $pdo->prepare("SELECT id, order_date, total_amount, status FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 10");
        $order_stmt->execute([$user_id]);
        $orders = $order_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Silently ignore if 'orders' table is not found, as it might be a future feature
    }

} catch (PDOException $e) {
    set_flash_message('Error fetching customer data: ' . $e->getMessage(), 'danger');
    header('Location: /admin/users.php');
    exit();
}

// Set the page title dynamically after fetching the user's name
$page_title = 'View Customer: ' . e($user['name']);

// =================================================================
// 3. RENDER THE VIEW
// =================================================================
require_once 'includes/header.php';
?>

<!-- Page Header with Breadcrumbs -->
<div class="page-header mb-4">
    <div class="row align-items-center">
        <div class="col">
            <h1 class="page-title"><?php echo e($user['name']); ?></h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/admin/dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="/admin/users.php">Customers</a></li>
                    <li class="breadcrumb-item active" aria-current="page">View Details</li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            <a href="/admin/users.php" class="btn btn-secondary">Back to List</a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Column: User Profile Card -->
    <div class="col-lg-4">
        <div class="card shadow-sm mb-4">
            <div class="card-body text-center">
                <img src="https://via.placeholder.com/150/007bff/FFFFFF?text=<?php echo strtoupper(substr($user['name'], 0, 1)); ?>" alt="User Avatar" class="rounded-circle img-fluid mb-3" style="width: 120px;">
                <h5 class="my-3"><?php echo e($user['name']); ?></h5>
                <p class="text-muted mb-1">Customer ID: <?php echo $user['id']; ?></p>
                <p class="text-muted mb-2">Status: 
                    <?php if ($user['status'] === 'suspended'): ?>
                        <span class="badge bg-light-warning text-warning">Suspended</span>
                    <?php else: ?>
                        <span class="badge bg-light-success text-success">Active</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Right Column: User Information and Order History -->
    <div class="col-lg-8">
        <!-- User Information Details -->
        <div class="card shadow-sm mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold">Customer Information</h6></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Full Name</dt>
                    <dd class="col-sm-9"><?php echo e($user['name']); ?></dd>
                    <hr class="my-2">
                    <dt class="col-sm-3">Email</dt>
                    <dd class="col-sm-9"><?php echo e($user['email']); ?></dd>
                    <hr class="my-2">
                    <dt class="col-sm-3">Registered On</dt>
                    <dd class="col-sm-9"><?php echo date('d F Y, h:i A', strtotime($user['created_at'])); ?></dd>
                </dl>
            </div>
        </div>
        
        <!-- User Order History Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold">Recent Order History</h6></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr><td colspan="4" class="text-center p-4">This customer has not placed any orders yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><a href="/admin/orders.php?action=view&id=<?php echo e($order['id']); ?>">#<?php echo e($order['id']); ?></a></td>
                                        <td><?php echo date('d M, Y', strtotime($order['order_date'])); ?></td>
                                        <td>$<?php echo e(number_format($order['total_amount'], 2)); ?></td>
                                        <td class="text-center"><span class="badge bg-light-primary text-primary"><?php echo e(ucfirst($order['status'])); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>