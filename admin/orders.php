<?php
// =================================================================
// 1. PAGE CONFIGURATION AND CORE SETUP
// =================================================================
$page_key = 'orders';
$page_title = 'Manage Orders';

require_once __DIR__ . '/../includes/app.php';
require_login();

// =================================================================
// 2. DATA FETCHING AND FILTERING
// =================================================================
$search_term = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$sql_where_conditions = [];
$params = [];

if (!empty($search_term)) {
    $sql_where_conditions[] = "(o.id::text ILIKE :search OR u.name ILIKE :search OR u.email ILIKE :search)";
    $params[':search'] = '%' . $search_term . '%';
}
if (!empty($status_filter)) {
    // CORRECTED: The status column is in the 'orders' table, aliased as 'o'.
    $sql_where_conditions[] = "o.status = :status";
    $params[':status'] = $status_filter;
}

$where_clause = !empty($sql_where_conditions) ? ' WHERE ' . implode(' AND ', $sql_where_conditions) : '';

try {
    // To build the query safely with a JOIN, we must specify which table each column comes from.
    $count_sql = "SELECT COUNT(o.id) FROM orders o LEFT JOIN users u ON o.user_id = u.id" . $where_clause;
    $total_orders_stmt = $pdo->prepare($count_sql);
    $total_orders_stmt->execute($params);
    $total_orders = $total_orders_stmt->fetchColumn();
    $total_pages = ceil($total_orders / $limit);

    // CORRECTED SQL: Fetched 'status' from the 'orders' table (o.status)
    // and customer name from the 'users' table (u.name).
    $orders_sql = "SELECT 
                        o.id, 
                        o.created_at as order_date, 
                        o.total_amount, 
                        o.status, 
                        u.id as user_id,
                        u.name as customer_name
                   FROM orders o
                   LEFT JOIN users u ON o.user_id = u.id"
                   . $where_clause . 
                   " ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset";
                   
    $orders_stmt = $pdo->prepare($orders_sql);
    foreach ($params as $key => &$val) {
        $orders_stmt->bindParam($key, $val);
    }
    $orders_stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $orders_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $orders_stmt->execute();
    $orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $orders = [];
    $total_orders = 0;
    $total_pages = 0;
    set_flash_message('Error fetching orders: ' . $e->getMessage(), 'danger');
}

// =================================================================
// 3. RENDER THE VIEW
// =================================================================
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header mb-4">
    <div class="row align-items-center">
        <div class="col">
            <h1 class="page-title">Orders <span class="text-muted">(<?php echo $total_orders; ?>)</span></h1>
        </div>
    </div>
</div>

<!-- Orders List Card -->
<div class="card shadow-sm">
    <div class="card-header">
        <!-- Filter and Search Section -->
        <form method="GET" action="/admin/orders.php" class="py-2">
            <div class="row g-2 align-items-center">
                <div class="col-md-7"><input type="text" name="search" class="form-control form-control-sm" placeholder="Search by Order ID, Name, or Email..." value="<?php echo e($search_term); ?>"></div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php if($status_filter === 'pending') echo 'selected'; ?>>Pending</option>
                        <option value="processing" <?php if($status_filter === 'processing') echo 'selected'; ?>>Processing</option>
                        <option value="completed" <?php if($status_filter === 'completed') echo 'selected'; ?>>Completed</option>
                        <option value="cancelled" <?php if($status_filter === 'cancelled') echo 'selected'; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-sm btn-secondary w-100">Filter</button></div>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($orders): foreach ($orders as $order): ?>
                        <tr>
                            <td><a href="/admin/view_order.php?id=<?php echo e($order['id']); ?>" class="fw-bold text-dark text-decoration-none">#<?php echo e($order['id']); ?></a></td>
                            <td><a href="/admin/view_user.php?id=<?php echo e($order['user_id']); ?>"><?php echo e($order['customer_name']); ?></a></td>
                            <td><?php echo date('d M, Y', strtotime($order['order_date'])); ?></td>
                            <td>$<?php echo e(number_format($order['total_amount'], 2)); ?></td>
                            <td class="text-center">
                                <?php
                                $status_class = 'secondary';
                                if ($order['status'] === 'completed') $status_class = 'success';
                                elseif ($order['status'] === 'processing') $status_class = 'info';
                                elseif ($order['status'] === 'pending') $status_class = 'warning';
                                elseif ($order['status'] === 'cancelled') $status_class = 'danger';
                                ?>
                                <span class="badge bg-light-<?php echo $status_class; ?> text-<?php echo $status_class; ?>"><?php echo e(ucfirst($order['status'])); ?></span>
                            </td>
                            <td class="text-end">
                                <a href="/admin/view_order.php?id=<?php echo e($order['id']); ?>" class="btn btn-sm btn-light" title="View Details"><i class="fas fa-eye"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" class="text-center p-5">No orders found matching your criteria.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($total_pages > 1): ?>
        <div class="card-footer">
            <nav class="d-flex justify-content-center">
                <ul class="pagination mb-0">
                    <?php 
                    $query_params = http_build_query(['search' => $search_term, 'status' => $status_filter]);
                    for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo $query_params; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php
require_once 'includes/footer.php';
?>