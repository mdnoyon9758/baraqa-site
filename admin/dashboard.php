<?php
$page_title = "Dashboard";
require_once 'includes/auth.php';
require_once 'includes/admin_header.php';

// --- Fetch all necessary data for the dashboard using PDO ---
try {
    // --- Define the base URL for admin pages for consistency ---
    $admin_base_url = '/admin/';

    // Fetch total counts for the main statistics cards
    $total_products = $pdo->query("SELECT COUNT(id) FROM products WHERE is_published = 1")->fetchColumn();
    $unpublished_products = $pdo->query("SELECT COUNT(id) FROM products WHERE is_published = 0")->fetchColumn();
    $total_categories = $pdo->query("SELECT COUNT(id) FROM categories")->fetchColumn();
    $total_brands = $pdo->query("SELECT COUNT(id) FROM brands")->fetchColumn();

    // Fetch total clicks for today (PostgreSQL compatible)
    // CHANGED: CURDATE() to CURRENT_DATE and DATE(click_time) to click_time::date
    $today_clicks = $pdo->query("SELECT COUNT(id) FROM affiliate_clicks WHERE click_time::date = CURRENT_DATE")->fetchColumn();

    // Fetch top 5 trending products
    $stmt_trending = $pdo->query("SELECT id, slug, title, trend_score FROM products WHERE is_published = 1 ORDER BY trend_score DESC LIMIT 5");
    $top_trending_products = $stmt_trending->fetchAll(PDO::FETCH_ASSOC);

    // Fetch top 5 most clicked products
    $stmt_clicked = $pdo->query("
        SELECT p.id, p.slug, p.title as product_title, COUNT(ac.product_id) as click_count 
        FROM affiliate_clicks ac
        JOIN products p ON ac.product_id = p.id
        WHERE p.is_published = 1
        GROUP BY p.id, p.slug, p.title 
        ORDER BY click_count DESC 
        LIMIT 5
    ");
    $top_clicked_products = $stmt_clicked->fetchAll(PDO::FETCH_ASSOC);

    // Fetch click data for the last 7 days for the chart
    $clicks_last_7_days = ['labels' => [], 'data' => []];
    // CHANGED: DATE(click_time) to click_time::date for PostgreSQL
    $chart_sql = "SELECT COUNT(*) FROM affiliate_clicks WHERE click_time::date = ?";
    $stmt_chart = $pdo->prepare($chart_sql);

    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $stmt_chart->execute([$date]);
        $clicks_last_7_days['labels'][] = date('M d', strtotime($date));
        $clicks_last_7_days['data'][] = (int)$stmt_chart->fetchColumn();
    }
    $chart_data_json = json_encode($clicks_last_7_days['data']);
    $chart_labels_json = json_encode($clicks_last_7_days['labels']);

} catch (PDOException $e) {
    // Set a session message instead of echoing directly
    $_SESSION['error_message'] = 'A database error occurred on the dashboard. Please check the logs.';
    // Log the detailed error for the admin, but don't show it to the user
    error_log("Dashboard PDOException: " . $e->getMessage());
    // Use a redirect to show the message cleanly
    header("Location: " . $admin_base_url . "dashboard.php");
    exit;
}
?>

<!-- Main content header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo e($page_title); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <!-- CHANGED: Paths are now absolute -->
        <a href="<?php echo $admin_base_url; ?>products.php?action=add" class="btn btn-sm btn-primary"><i class="fas fa-plus me-2"></i>Add Product</a>
        <a href="<?php echo $admin_base_url; ?>settings.php" class="btn btn-sm btn-outline-secondary ms-2"><i class="fas fa-cog"></i> Site Settings</a>
    </div>
</div>

<!-- Statistic Cards Row -->
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Live Products</div><div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo e($total_products); ?></div></div><div class="col-auto"><i class="fas fa-box-open fa-2x text-gray-300"></i></div></div><!-- CHANGED: Path to absolute --><a href="<?php echo $admin_base_url; ?>products.php" class="stretched-link"></a></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Brands</div><div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo e($total_brands); ?></div></div><div class="col-auto"><i class="fas fa-bookmark fa-2x text-gray-300"></i></div></div><!-- CHANGED: Path to absolute --><a href="<?php echo $admin_base_url; ?>manage_brands.php" class="stretched-link"></a></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Categories</div><div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo e($total_categories); ?></div></div><div class="col-auto"><i class="fas fa-tags fa-2x text-gray-300"></i></div></div><!-- CHANGED: Path to absolute --><a href="<?php echo $admin_base_url; ?>categories.php" class="stretched-link"></a></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Clicks (Today)</div><div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo e($today_clicks); ?></div></div><div class="col-auto"><i class="fas fa-mouse-pointer fa-2x text-gray-300"></i></div></div><!-- CHANGED: Path to absolute --><a href="<?php echo $admin_base_url; ?>clicked_leads.php" class="stretched-link"></a></div>
        </div>
    </div>
</div>

<!-- Chart and Tables Row -->
<div class="row mt-2">
    <div class="col-xl-12 col-lg-12 mb-4">
        <div class="card shadow">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Affiliate Clicks (Last 7 Days)</h6></div>
            <div class="card-body"><div class="chart-area"><canvas id="clicksChart"></canvas></div></div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header fw-bold">Top 5 Trending Products</div>
            <div class="card-body p-2">
                <ul class="list-group list-group-flush">
                    <?php if (!empty($top_trending_products)): foreach ($top_trending_products as $product): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <!-- CHANGED: Path from ../product.php to /product.php -->
                            <a href="/product.php?slug=<?php echo e($product['slug']); ?>" target="_blank"><?php echo e(substr($product['title'], 0, 45)); ?>...</a>
                            <span class="badge bg-success rounded-pill"><?php echo e(round($product['trend_score'], 1)); ?></span>
                        </li>
                    <?php endforeach; else: ?>
                        <li class="list-group-item text-center text-muted">No trending data available.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header fw-bold">Top 5 Clicked Products</div>
            <div class="card-body p-2">
                <ul class="list-group list-group-flush">
                    <?php if (!empty($top_clicked_products)): foreach ($top_clicked_products as $product): ?>
                         <li class="list-group-item d-flex justify-content-between align-items-center">
                            <!-- CHANGED: Path from ../product.php to /product.php -->
                            <a href="/product.php?slug=<?php echo e($product['slug']); ?>" target="_blank"><?php echo e(substr($product['product_title'], 0, 45)); ?>...</a>
                            <span class="badge bg-primary rounded-pill"><?php echo e($product['click_count']); ?> Clicks</span>
                        </li>
                    <?php endforeach; else: ?>
                        <li class="list-group-item text-center text-muted">No click data available yet.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('clicksChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo $chart_labels_json; ?>,
            datasets: [{
                label: 'Clicks',
                data: <?php echo $chart_data_json; ?>,
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                borderColor: 'rgba(78, 115, 223, 1)',
                pointRadius: 3,
                pointBackgroundColor: "rgba(78, 115, 223, 1)",
                pointHoverRadius: 5,
                tension: 0.3,
                fill: true,
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {y: {beginAtZero: true, ticks: {precision: 0}}},
            plugins: {legend: {display: false}}
        }
    });
});
</script>

<?php
require_once 'includes/admin_footer.php';
?>