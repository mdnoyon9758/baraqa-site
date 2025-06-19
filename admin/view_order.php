<?php
// =================================================================
// 1. PAGE CONFIGURATION AND CORE SETUP
// =================================================================
$page_key = 'orders'; // Keep the parent menu 'orders' active
$page_title = 'View Order';

require_once __DIR__ . '/../includes/app.php';
require_login();

// =================================================================
// 2. DATA FETCHING FOR A SINGLE ORDER
// =================================================================
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    set_flash_message('Invalid Order ID.', 'danger');
    header('Location: /admin/orders.php');
    exit();
}

try {
    // Fetch the main order details
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        set_flash_message('No order found with the specified ID.', 'danger');
        header('Location: /admin/orders.php');
        exit();
    }
    
    // Fetch all items associated with this order
    $items_stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $items_stmt->execute([$order_id]);
    $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    set_flash_message('Error fetching order data: ' . $e->getMessage(), 'danger');
    header('Location: /admin/orders.php');
    exit();
}

// Set the page title dynamically
$page_title = 'Order Details: #' . e($order['id']);

// =================================================================
// 3. RENDER THE VIEW
// =================================================================
require_once 'includes/header.php';
?>

<!-- Page Header with Breadcrumbs and Actions -->
<div class="page-header mb-4">
    <div class="row align-items-center">
        <div class="col">
            <h1 class="page-title">Order #<?php echo e($order['id']); ?></h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/admin/dashboard.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="/admin/orders.php">Orders</a></li>
                    <li class="breadcrumb-item active" aria-current="page">View Details</li>
                </ol>
            </nav>
        </div>
        <div class="col-auto">
            <a href="/admin/orders.php" class="btn btn-secondary">Back to List</a>
            <button class="btn btn-primary" onclick="window.print();"><i class="fas fa-print me-2"></i>Print Invoice</button>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Column: Order Details, Items -->
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold">Order Items</h6>
                <span><?php echo count($order_items); ?> items</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Price</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $subtotal = 0;
                            foreach ($order_items as $item): 
                                $item_total = $item['price_per_item'] * $item['quantity'];
                                $subtotal += $item_total;
                            ?>
                                <tr>
                                    <td>
                                        <a href="/admin/products.php?action=edit&id=<?php echo e($item['product_id']); ?>" class="text-dark fw-bold text-decoration-none">
                                            <?php echo e($item['product_name']); ?>
                                        </a>
                                        <small class="d-block text-muted">Product ID: <?php echo e($item['product_id'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td class="text-end">$<?php echo e(number_format($item['price_per_item'], 2)); ?></td>
                                    <td class="text-center"><?php echo e($item['quantity']); ?></td>
                                    <td class="text-end fw-bold">$<?php echo e(number_format($item_total, 2)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="row">
                    <div class="col-md-6 offset-md-6">
                        <dl class="row text-end">
                            <dt class="col-6">Subtotal:</dt>
                            <dd class="col-6">$<?php echo e(number_format($subtotal, 2)); ?></dd>
                            <dt class="col-6">Shipping:</dt>
                            <dd class="col-6">$0.00</dd> <!-- Placeholder -->
                            <dt class="col-6 border-top pt-2">Total:</dt>
                            <dd class="col-6 border-top pt-2 fw-bold fs-5">$<?php echo e(number_format($order['total_amount'], 2)); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Customer Details, Order Status -->
    <div class="col-lg-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold">Order & Customer</h6></div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <img src="https://via.placeholder.com/60/007bff/FFFFFF?text=<?php echo strtoupper(substr($order['customer_name'], 0, 1)); ?>" class="rounded-circle me-3" alt="Avatar">
                    <div>
                        <a href="/admin/view_user.php?id=<?php echo e($order['user_id']); ?>" class="text-dark fw-bold text-decoration-none"><?php echo e($order['customer_name']); ?></a>
                        <small class="d-block text-muted"><?php echo e($order['customer_email']); ?></small>
                    </div>
                </div>
                <hr>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Order Date:</dt>
                    <!-- CORRECTED: Changed $order['order_date'] to $order['created_at'] -->
                    <dd class="col-sm-7"><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></dd>
                    
                    <dt class="col-sm-5">Payment Method:</dt>
                    <dd class="col-sm-7"><?php echo e($order['payment_method'] ?? 'Not specified'); ?></dd>
                    
                    <dt class="col-sm-5">Transaction ID:</dt>
                    <dd class="col-sm-7"><code><?php echo e($order['transaction_id'] ?? 'N/A'); ?></code></dd>
                </dl>
                <hr>
                <form action="/admin/update_order_status.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="order_id" value="<?php echo e($order['id']); ?>">
                    <label for="order_status" class="form-label fw-bold">Update Order Status:</label>
                    <div class="input-group">
                        <select id="order_status" name="status" class="form-select">
                            <option value="pending" <?php if($order['status'] === 'pending') echo 'selected'; ?>>Pending</option>
                            <option value="processing" <?php if($order['status'] === 'processing') echo 'selected'; ?>>Processing</option>
                            <option value="completed" <?php if($order['status'] === 'completed') echo 'selected'; ?>>Completed</option>
                            <option value="cancelled" <?php if($order['status'] === 'cancelled') echo 'selected'; ?>>Cancelled</option>
                        </select>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-header"><h6 class="m-0 font-weight-bold">Shipping Address</h6></div>
            <div class="card-body">
                <address class="mb-0">
                    <?php echo nl2br(e($order['shipping_address'] ?? 'No shipping address provided.')); ?>
                </address>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>