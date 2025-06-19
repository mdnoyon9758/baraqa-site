<?php
// =================================================================
// 1. PAGE CONFIGURATION AND CORE SETUP
// =================================================================
$page_key = 'commissions';
$page_title = 'Manage Commissions';

require_once __DIR__ . '/../includes/app.php';
require_login();

// =================================================================
// 2. DATA FETCHING AND FILTERING
// =================================================================
$status_filter = $_GET['status'] ?? 'unpaid'; // Default to show unpaid commissions
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$sql_where_conditions = [];
$params = [];

if (!empty($status_filter) && in_array($status_filter, ['unpaid', 'paid', 'voided'])) {
    $sql_where_conditions[] = "c.status = :status";
    $params[':status'] = $status_filter;
}

$where_clause = !empty($sql_where_conditions) ? ' WHERE ' . implode(' AND ', $sql_where_conditions) : '';

try {
    // Fetch total count for pagination
    $total_commissions_stmt = $pdo->prepare("SELECT COUNT(c.id) FROM commissions c" . $where_clause);
    $total_commissions_stmt->execute($params);
    $total_commissions = $total_commissions_stmt->fetchColumn();
    $total_pages = ceil($total_commissions / $limit);

    // Fetch commissions for the current page
    $commissions_sql = "SELECT c.id, c.commission_amount, c.status, c.created_at, 
                               o.id as order_id, 
                               u.name as affiliate_name, a.id as affiliate_id
                        FROM commissions c
                        JOIN affiliates a ON c.affiliate_id = a.id
                        JOIN users u ON a.user_id = u.id
                        JOIN orders o ON c.order_id = o.id"
                        . $where_clause . 
                        " ORDER BY c.created_at DESC LIMIT :limit OFFSET :offset";
                   
    $commissions_stmt = $pdo->prepare($commissions_sql);
    foreach ($params as $key => &$val) {
        $commissions_stmt->bindParam($key, $val);
    }
    $commissions_stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $commissions_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $commissions_stmt->execute();
    $commissions = $commissions_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $commissions = [];
    $total_commissions = 0;
    $total_pages = 0;
    set_flash_message('Error fetching commissions: ' . $e->getMessage(), 'danger');
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
            <h1 class="page-title">Affiliate Commissions <span class="text-muted">(<?php echo $total_commissions; ?>)</span></h1>
        </div>
    </div>
</div>

<!-- Commissions List Card -->
<div class="card shadow-sm">
    <div class="card-header">
        <!-- Filter Tabs -->
        <ul class="nav nav-tabs card-header-tabs">
            <li class="nav-item">
                <a class="nav-link <?php if($status_filter === 'unpaid') echo 'active'; ?>" href="?status=unpaid">Unpaid</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php if($status_filter === 'paid') echo 'active'; ?>" href="?status=paid">Paid</a>
            </li>
             <li class="nav-item">
                <a class="nav-link <?php if($status_filter === 'voided') echo 'active'; ?>" href="?status=voided">Voided</a>
            </li>
        </ul>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Commission ID</th>
                        <th>Affiliate</th>
                        <th>Related Order</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($commissions): foreach ($commissions as $commission): ?>
                        <tr>
                            <td>#<?php echo e($commission['id']); ?></td>
                            <td><a href="/admin/view_affiliate.php?id=<?php echo e($commission['affiliate_id']); ?>"><?php echo e($commission['affiliate_name']); ?></a></td>
                            <td><a href="/admin/view_order.php?id=<?php echo e($commission['order_id']); ?>">Order #<?php echo e($commission['order_id']); ?></a></td>
                            <td class="fw-bold">$<?php echo e(number_format($commission['commission_amount'], 2)); ?></td>
                            <td><?php echo date('d M, Y', strtotime($commission['created_at'])); ?></td>
                            <td class="text-end">
                                <?php if($commission['status'] === 'unpaid'): ?>
                                    <a href="/admin/commission_action.php?action=mark_paid&id=<?php echo e($commission['id']); ?>" class="btn btn-sm btn-light text-success" title="Mark as Paid"><i class="fas fa-check-circle"></i></a>
                                    <a href="/admin/commission_action.php?action=mark_voided&id=<?php echo e($commission['id']); ?>" class="btn btn-sm btn-light text-danger" title="Void Commission"><i class="fas fa-times-circle"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" class="text-center p-5">No commissions found with status '<?php echo e($status_filter); ?>'.</td></tr>
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
                    $query_params = http_build_query(['status' => $status_filter]);
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