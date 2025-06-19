<?php
// =================================================================
// 1. PAGE CONFIGURATION AND CORE SETUP
// =================================================================
$page_key = 'reviews';
$page_title = 'Manage Reviews';

require_once __DIR__ . '/../includes/app.php';
require_login();

// =================================================================
// 2. DATA FETCHING AND FILTERING
// =================================================================
$status_filter = $_GET['status'] ?? 'pending'; // Default to show pending reviews
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$sql_where_conditions = [];
$params = [];

if (!empty($status_filter) && in_array($status_filter, ['pending', 'approved', 'rejected'])) {
    $sql_where_conditions[] = "r.status = :status";
    $params[':status'] = $status_filter;
}

$where_clause = !empty($sql_where_conditions) ? ' WHERE ' . implode(' AND ', $sql_where_conditions) : '';

try {
    // Fetch total count for pagination
    $total_reviews_stmt = $pdo->prepare("SELECT COUNT(r.id) FROM reviews r" . $where_clause);
    $total_reviews_stmt->execute($params);
    $total_reviews = $total_reviews_stmt->fetchColumn();
    $total_pages = ceil($total_reviews / $limit);

    // Fetch reviews for the current page
    $reviews_sql = "SELECT r.id, r.rating, r.title, r.content, r.status, r.created_at, r.reviewer_name, p.title as product_title, p.slug as product_slug 
                    FROM reviews r
                    LEFT JOIN products p ON r.product_id = p.id"
                    . $where_clause . 
                    " ORDER BY r.created_at DESC LIMIT :limit OFFSET :offset";
                   
    $reviews_stmt = $pdo->prepare($reviews_sql);
    foreach ($params as $key => &$val) {
        $reviews_stmt->bindParam($key, $val);
    }
    $reviews_stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $reviews_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $reviews_stmt->execute();
    $reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $reviews = [];
    $total_reviews = 0;
    $total_pages = 0;
    set_flash_message('Error fetching reviews: ' . $e->getMessage(), 'danger');
}

// =================================================================
// 3. RENDER THE VIEW
// =================================================================
require_once 'includes/header.php';

// Helper function to generate star ratings
function render_star_rating($rating) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<i class="fas fa-star ' . ($i <= $rating ? 'text-warning' : 'text-muted') . '"></i>';
    }
    return $html;
}
?>

<!-- Page Header -->
<div class="page-header mb-4">
    <div class="row align-items-center">
        <div class="col">
            <h1 class="page-title">Product Reviews <span class="text-muted">(<?php echo $total_reviews; ?>)</span></h1>
        </div>
    </div>
</div>

<!-- Reviews List Card -->
<div class="card shadow-sm">
    <div class="card-header">
        <!-- Filter Tabs -->
        <ul class="nav nav-tabs card-header-tabs">
            <li class="nav-item">
                <a class="nav-link <?php if($status_filter === 'pending') echo 'active'; ?>" href="?status=pending">Pending</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php if($status_filter === 'approved') echo 'active'; ?>" href="?status=approved">Approved</a>
            </li>
             <li class="nav-item">
                <a class="nav-link <?php if($status_filter === 'rejected') echo 'active'; ?>" href="?status=rejected">Rejected</a>
            </li>
        </ul>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40%;">Review</th>
                        <th>Product</th>
                        <th class="text-center">Rating</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($reviews): foreach ($reviews as $review): ?>
                        <tr>
                            <td>
                                <strong class="text-dark"><?php echo e($review['reviewer_name']); ?></strong>
                                <?php if($review['title']): ?><p class="fw-bold mb-1"><?php echo e($review['title']); ?></p><?php endif; ?>
                                <p class="mb-1 text-muted"><?php echo e(mb_strimwidth($review['content'], 0, 150, "...")); ?></p>
                                <small class="text-muted"><?php echo date('d M Y, H:i', strtotime($review['created_at'])); ?></small>
                            </td>
                            <td>
                                <a href="/product/<?php echo e($review['product_slug']); ?>" target="_blank">
                                    <?php echo e(mb_strimwidth($review['product_title'], 0, 40, "...")); ?>
                                </a>
                            </td>
                            <td class="text-center">
                                <?php echo render_star_rating($review['rating']); ?>
                            </td>
                            <td class="text-end">
                                <?php if($review['status'] !== 'approved'): ?>
                                    <a href="/admin/review_action.php?action=approve&id=<?php echo e($review['id']); ?>" class="btn btn-sm btn-light text-success" title="Approve"><i class="fas fa-check"></i></a>
                                <?php endif; ?>
                                <?php if($review['status'] !== 'rejected'): ?>
                                    <a href="/admin/review_action.php?action=reject&id=<?php echo e($review['id']); ?>" class="btn btn-sm btn-light text-warning" title="Reject"><i class="fas fa-times"></i></a>
                                <?php endif; ?>
                                <a href="/admin/review_action.php?action=delete&id=<?php echo e($review['id']); ?>" class="btn btn-sm btn-light text-danger" title="Delete" onclick="return confirm('Are you sure you want to permanently delete this review?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="4" class="text-center p-5">No reviews found with status '<?php echo e($status_filter); ?>'.</td></tr>
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