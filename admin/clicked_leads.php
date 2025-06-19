<?php
// =================================================================
// 1. PAGE CONFIGURATION AND CORE SETUP
// =================================================================
$page_key = 'clicked_leads'; // A unique key for this page
$page_title = 'Affiliate Clicks Log';

require_once __DIR__ . '/../includes/app.php';
require_login();

// =================================================================
// 2. DATA FETCHING AND PAGINATION
// =================================================================
try {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 25;
    $offset = ($page - 1) * $limit;

    // Get total number of clicks for pagination
    $total_clicks = $pdo->query("SELECT COUNT(id) FROM affiliate_clicks")->fetchColumn();
    $total_pages = ceil($total_clicks / $limit);

    // Fetch Click Data with Product Info using a JOIN
    $sql = "SELECT 
                ac.id, ac.click_time, ac.ip_address, 
                p.id as product_id, p.title as product_title, p.slug as product_slug
            FROM affiliate_clicks ac
            LEFT JOIN products p ON ac.product_id = p.id
            ORDER BY ac.click_time DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $leads = [];
    $total_pages = 0;
    $total_clicks = 0;
    set_flash_message("Database error fetching click logs: " . $e->getMessage(), 'danger');
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
            <h1 class="page-title">Affiliate Clicks Log <span class="text-muted">(Total: <?php echo $total_clicks; ?>)</span></h1>
        </div>
    </div>
</div>

<!-- Clicks Log Card -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
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
                                        <a href="/product/<?php echo e($lead['product_slug']); ?>" target="_blank" class="text-dark text-decoration-none">
                                            <?php echo e(mb_strimwidth($lead['product_title'], 0, 70, "...")); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted"><em>Product Deleted (ID: <?php echo e($lead['product_id']); ?>)</em></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($lead['ip_address'] ?? 'N/A'); ?></td>
                                <td><?php echo date("d M Y, h:i A", strtotime($lead['click_time'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center p-5">No affiliate clicks have been recorded yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="card-footer">
            <nav class="d-flex justify-content-center">
                <ul class="pagination mb-0">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php
// No page-specific scripts needed for this page
require_once 'includes/footer.php';
?>