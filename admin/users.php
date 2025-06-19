<?php
// =================================================================
// 1. PAGE CONFIGURATION AND CORE SETUP
// =================================================================
$page_key = 'users'; // This key matches the 'Customers' menu item in the sidebar
$page_title = 'Manage Customers';

require_once __DIR__ . '/../includes/app.php';
require_login();

// =================================================================
// 2. DATA FETCHING AND FILTERING LOGIC
// =================================================================
$search_term = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Build the WHERE clause for the SQL query based on filters
$sql_where_conditions = [];
$params = [];

if (!empty($search_term)) {
    // Using ILIKE for case-insensitive search in PostgreSQL
    $sql_where_conditions[] = "(name ILIKE :search OR email ILIKE :search)";
    $params[':search'] = '%' . $search_term . '%';
}
if ($status_filter !== '' && in_array($status_filter, ['active', 'suspended'])) {
    $sql_where_conditions[] = "status = :status";
    $params[':status'] = $status_filter;
}

$where_clause = !empty($sql_where_conditions) ? ' WHERE ' . implode(' AND ', $sql_where_conditions) : '';

try {
    // Fetch total count for pagination
    $total_users_stmt = $pdo->prepare("SELECT COUNT(id) FROM users" . $where_clause);
    $total_users_stmt->execute($params);
    $total_users = $total_users_stmt->fetchColumn();
    $total_pages = ceil($total_users / $limit);

    // Fetch users for the current page
    $users_stmt = $pdo->prepare("SELECT id, name, email, created_at, status FROM users" . $where_clause . " ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    foreach ($params as $key => &$val) {
        $users_stmt->bindParam($key, $val);
    }
    $users_stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $users_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $users_stmt->execute();
    $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
    $total_users = 0;
    set_flash_message('Error fetching customers: ' . $e->getMessage(), 'danger');
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
            <h1 class="page-title">Customers <span class="text-muted">(<?php echo $total_users; ?>)</span></h1>
        </div>
    </div>
</div>

<!-- Users List Card -->
<div class="card shadow-sm">
    <div class="card-header">
        <!-- Filter and Search Section -->
        <form method="GET" action="/admin/users.php" class="py-2">
            <div class="row g-2 align-items-center">
                <div class="col-md-7">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name or email..." value="<?php echo e($search_term); ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Any Status</option>
                        <option value="active" <?php if ($status_filter === 'active') echo 'selected'; ?>>Active</option>
                        <option value="suspended" <?php if ($status_filter === 'suspended') echo 'selected'; ?>>Suspended</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-secondary w-100">Filter</button>
                </div>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Registered On</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users): foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="https://via.placeholder.com/40/007bff/FFFFFF?text=<?php echo strtoupper(substr($user['name'], 0, 1)); ?>" class="rounded-circle me-3" width="40" height="40" alt="<?php echo e($user['name']); ?>">
                                    <div>
                                        <a href="/admin/view_user.php?id=<?php echo e($user['id']); ?>" class="text-dark fw-bold text-decoration-none"><?php echo e($user['name']); ?></a>
                                        <small class="d-block text-muted">ID: <?php echo e($user['id']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo e($user['email']); ?></td>
                            <td><?php echo date('d M, Y', strtotime($user['created_at'])); ?></td>
                            <td class="text-center">
                                <?php if ($user['status'] === 'suspended'): ?>
                                    <span class="badge bg-light-warning text-warning">Suspended</span>
                                <?php else: ?>
                                    <span class="badge bg-light-success text-success">Active</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="/admin/view_user.php?id=<?php echo e($user['id']); ?>" class="btn btn-sm btn-light" title="View Details"><i class="fas fa-eye"></i></a>
                                <?php if ($user['status'] === 'suspended'): ?>
                                    <a href="/admin/user_action.php?action=activate&id=<?php echo e($user['id']); ?>" class="btn btn-sm btn-light text-success" title="Activate User"><i class="fas fa-check-circle"></i></a>
                                <?php else: ?>
                                    <a href="/admin/user_action.php?action=suspend&id=<?php echo e($user['id']); ?>" class="btn btn-sm btn-light text-warning" title="Suspend User"><i class="fas fa-user-slash"></i></a>
                                <?php endif; ?>
                                <a href="/admin/user_action.php?action=delete&id=<?php echo e($user['id']); ?>" class="btn btn-sm btn-light text-danger" title="Delete User" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="5" class="text-center p-5">No customers found matching your criteria.</td></tr>
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
                    for ($i = 1; $i <= $total_pages; $i++): 
                    ?>
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