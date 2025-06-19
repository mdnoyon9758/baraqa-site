<?php
// =================================================================
// 1. PAGE CONFIGURATION AND CORE SETUP
// =================================================================
$page_key = 'affiliates';
$page_title = 'Manage Affiliates';

require_once __DIR__ . '/../includes/app.php';
require_login();

// =================================================================
// 2. DATA FETCHING AND FILTERING
// =================================================================
$status_filter = $_GET['status'] ?? 'pending'; // Default to show pending requests
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$sql_where_conditions = [];
$params = [];

if (!empty($status_filter) && in_array($status_filter, ['pending', 'approved', 'suspended'])) {
    $sql_where_conditions[] = "a.status = :status";
    $params[':status'] = $status_filter;
}

$where_clause = !empty($sql_where_conditions) ? ' WHERE ' . implode(' AND ', $sql_where_conditions) : '';

try {
    // Fetch total count for pagination
    $total_affiliates_stmt = $pdo->prepare("SELECT COUNT(a.id) FROM affiliates a" . $where_clause);
    $total_affiliates_stmt->execute($params);
    $total_affiliates = $total_affiliates_stmt->fetchColumn();
    $total_pages = ceil($total_affiliates / $limit);

    // Fetch affiliates for the current page, joining with users table to get name/email
    $affiliates_sql = "SELECT a.id, a.status, a.commission_rate, a.created_at, u.name, u.email 
                       FROM affiliates a
                       JOIN users u ON a.user_id = u.id"
                       . $where_clause . 
                       " ORDER BY a.created_at DESC LIMIT :limit OFFSET :offset";
                   
    $affiliates_stmt = $pdo->prepare($affiliates_sql);
    foreach ($params as $key => &$val) {
        $affiliates_stmt->bindParam($key, $val);
    }
    $affiliates_stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $affiliates_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $affiliates_stmt->execute();
    $affiliates = $affiliates_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $affiliates = [];
    $total_affiliates = 0;
    $total_pages = 0;
    set_flash_message('Error fetching affiliates: ' . $e->getMessage(), 'danger');
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
            <h1 class="page-title">Affiliate Partners <span class="text-muted">(<?php echo $total_affiliates; ?>)</span></h1>
        </div>
    </div>
</div>

<!-- Affiliates List Card -->
<div class="card shadow-sm">
    <div class="card-header">
        <!-- Filter Tabs -->
        <ul class="nav nav-tabs card-header-tabs">
            <li class="nav-item">
                <a class="nav-link <?php if($status_filter === 'pending') echo 'active'; ?>" href="?status=pending">Pending Requests</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php if($status_filter === 'approved') echo 'active'; ?>" href="?status=approved">Approved</a>
            </li>
             <li class="nav-item">
                <a class="nav-link <?php if($status_filter === 'suspended') echo 'active'; ?>" href="?status=suspended">Suspended</a>
            </li>
        </ul>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Affiliate Name</th>
                        <th>Email</th>
                        <th>Commission Rate</th>
                        <th>Registered On</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($affiliates): foreach ($affiliates as $affiliate): ?>
                        <tr>
                            <td>
                                <strong class="text-dark"><?php echo e($affiliate['name']); ?></strong>
                                <br><small class="text-muted">Affiliate ID: #<?php echo e($affiliate['id']); ?></small>
                            </td>
                            <td><?php echo e($affiliate['email']); ?></td>
                            <td><?php echo e($affiliate['commission_rate']); ?>%</td>
                            <td><?php echo date('d M, Y', strtotime($affiliate['created_at'])); ?></td>
                            <td class="text-end">
                                <?php if($affiliate['status'] === 'pending' || $affiliate['status'] === 'suspended'): ?>
                                    <a href="/admin/affiliate_action.php?action=approve&id=<?php echo e($affiliate['id']); ?>" class="btn btn-sm btn-light text-success" title="Approve"><i class="fas fa-check"></i></a>
                                <?php endif; ?>
                                <?php if($affiliate['status'] === 'approved'): ?>
                                    <a href="/admin/affiliate_action.php?action=suspend&id=<?php echo e($affiliate['id']); ?>" class="btn btn-sm btn-light text-warning" title="Suspend"><i class="fas fa-ban"></i></a>
                                <?php endif; ?>
                                <a href="/admin/affiliate_action.php?action=delete&id=<?php echo e($affiliate['id']); ?>" class="btn btn-sm btn-light text-danger" title="Delete" onclick="return confirm('Are you sure you want to permanently delete this affiliate and all their commission data?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="5" class="text-center p-5">No affiliates found with status '<?php echo e($status_filter); ?>'.</td></tr>
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