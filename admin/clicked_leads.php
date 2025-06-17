<?php
$page_title = "Affiliate Clicks Log";
require_once 'includes/auth.php';
require_once 'includes/admin_header.php';

try {
    // --- Pagination Logic ---
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;
    $limit = 25; // Number of clicks per page
    $offset = ($page - 1) * $limit;

    // Get total number of clicks for pagination
    $total_clicks = $pdo->query("SELECT COUNT(*) FROM affiliate_clicks")->fetchColumn();
    $total_pages = ceil($total_clicks / $limit);

    // --- Fetch Click Data with Product Info using a JOIN ---
    $sql = "SELECT 
                ac.id, 
                ac.click_time, 
                ac.ip_address, 
                p.id as product_id, 
                p.title as product_title, 
                p.slug as product_slug
            FROM 
                affiliate_clicks ac
            LEFT JOIN 
                products p ON ac.product_id = p.id
            ORDER BY 
                ac.click_time DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $leads = [];
    $total_pages = 0;
    $_SESSION['error_message'] = "Database error fetching click logs: " . $e->getMessage();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?php echo e($page_title); ?> (Total: <?php echo $total_clicks; ?>)</h1>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%">
                <thead class="table-light">
                    <tr>
                        <th>Click ID</th>
                        <th>Product Title</th>
                        <th>IP Address</th>
                        <th>Clicked At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($leads)): ?>
                        <?php foreach ($leads as $lead): ?>
                            <tr>
                                <td><?php echo e($lead['id']); ?></td>
                                <td>
                                    <?php if ($lead['product_title']): ?>
                                        <a href="../product.php?slug=<?php echo e($lead['product_slug']); ?>" target="_blank">
                                            <?php echo e($lead['product_title']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Product Deleted (ID: <?php echo e($lead['product_id']); ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($lead['ip_address'] ?? 'N/A'); ?></td>
                                <td><?php echo date("F j, Y, g:i a", strtotime($lead['click_time'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">No affiliate clicks have been recorded yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav class="mt-4 d-flex justify-content-center">
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>