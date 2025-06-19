<?php
// =================================================================
// 1. PAGE CONFIGURATION AND CORE SETUP
// =================================================================
$page_key = 'dashboard';
$page_title = 'Dashboard';

require_once __DIR__ . '/../includes/app.php';
require_login();

// =================================================================
// 2. DATA FETCHING FOR WIDGETS
// =================================================================
try {
    // Fetch total counts for the main statistics cards
    $total_products = $pdo->query("SELECT COUNT(id) FROM products WHERE is_published = 1")->fetchColumn();
    $total_orders = $pdo->query("SELECT COUNT(id) FROM orders")->fetchColumn();
    $total_customers = $pdo->query("SELECT COUNT(id) FROM users")->fetchColumn();
    $today_clicks = $pdo->query("SELECT COUNT(id) FROM affiliate_clicks WHERE click_time::date = CURRENT_DATE")->fetchColumn();
    
    // Fetch top 5 trending products
    $stmt_trending = $pdo->query("SELECT slug, title, trend_score FROM products WHERE is_published = 1 ORDER BY trend_score DESC LIMIT 5");
    $top_trending_products = $stmt_trending->fetchAll(PDO::FETCH_ASSOC);

    // Fetch top 5 most clicked products
    $stmt_clicked = $pdo->query("
        SELECT p.slug, p.title as product_title, COUNT(ac.product_id) as click_count 
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
    $stmt_chart = $pdo->prepare("SELECT COUNT(*) FROM affiliate_clicks WHERE click_time::date = ?");
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $stmt_chart->execute([$date]);
        $clicks_last_7_days['labels'][] = date('M d', strtotime($date));
        $clicks_last_7_days['data'][] = (int)$stmt_chart->fetchColumn();
    }

} catch (PDOException $e) {
    // Gracefully handle database errors without crashing the page
    set_flash_message('A database error occurred on the dashboard: ' . $e->getMessage(), 'danger');
    // Set default values to avoid errors in the view
    $total_products = $total_orders = $total_customers = $today_clicks = 0;
    $top_trending_products = $top_clicked_products = [];
    $clicks_last_7_days = ['labels' => [], 'data' => []];
}

// =================================================================
// 3. RENDER THE VIEW
// =================================================================
require_once 'includes/header.php'; // This includes the new header and sidebar
?>

<!-- Page Header (Removed the duplicate one from your old file) -->
<div class="page-header mb-4">
    <div class="row align-items-center">
        <div class="col">
            <h1 class="page-title">Dashboard</h1>
        </div>
        <div class="col-auto">
            <div class="btn-toolbar">
                <a href="/admin/products.php?action=add" class="btn btn-primary"><i class="fas fa-plus me-2"></i>New Product</a>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard Widgets/Stats Cards -->
<div class="row">
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card shadow-sm stat-card border-left-primary h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="stat-card-title text-primary">Total Products</div>
                        <div class="stat-card-value"><?php echo e($total_products ?? 0); ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-box fa-2x text-gray-300"></i></div>
                </div>
                <a href="/admin/products.php" class="stretched-link" aria-label="View Products"></a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card shadow-sm stat-card border-left-success h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="stat-card-title text-success">Total Orders</div>
                        <div class="stat-card-value"><?php echo e($total_orders ?? 0); ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-shopping-bag fa-2x text-gray-300"></i></div>
                </div>
                <a href="/admin/orders.php" class="stretched-link" aria-label="View Orders"></a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card shadow-sm stat-card border-left-info h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="stat-card-title text-info">Total Customers</div>
                        <div class="stat-card-value"><?php echo e($total_customers ?? 0); ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-users fa-2x text-gray-300"></i></div>
                </div>
                <a href="/admin/users.php" class="stretched-link" aria-label="View Customers"></a>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card shadow-sm stat-card border-left-warning h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="stat-card-title text-warning">Clicks (Today)</div>
                        <div class="stat-card-value"><?php echo e($today_clicks ?? 0); ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-mouse-pointer fa-2x text-gray-300"></i></div>
                </div>
                <a href="/admin/clicked_leads.php" class="stretched-link" aria-label="View Click Logs"></a>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Row (Chart and Lists) -->
<div class="row">
    <!-- Clicks Chart -->
    <div class="col-lg-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-header"><h6 class="m-0 font-weight-bold">Affiliate Clicks (Last 7 Days)</h6></div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height:300px;"><canvas id="clicksChart"></canvas></div>
            </div>
        </div>
    </div>

    <!-- Top Trending & Clicked Products -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header"><h6 class="m-0 font-weight-bold">Top 5 Trending Products</h6></div>
            <div class="list-group list-group-flush">
                <?php if (!empty($top_trending_products)): foreach ($top_trending_products as $product): ?>
                    <a href="/product/<?php echo e($product['slug']); ?>" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <?php echo e(mb_strimwidth($product['title'], 0, 40, "...")); ?>
                        <span class="badge bg-success rounded-pill"><?php echo e(round($product['trend_score'])); ?></span>
                    </a>
                <?php endforeach; else: ?>
                    <div class="list-group-item text-center text-muted">No trending data available.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header"><h6 class="m-0 font-weight-bold">Top 5 Clicked Products</h6></div>
            <div class="list-group list-group-flush">
                <?php if (!empty($top_clicked_products)): foreach ($top_clicked_products as $product): ?>
                    <a href="/product/<?php echo e($product['slug']); ?>" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <?php echo e(mb_strimwidth($product['product_title'], 0, 40, "...")); ?>
                        <span class="badge bg-primary rounded-pill"><?php echo e($product['click_count']); ?> Clicks</span>
                    </a>
                <?php endforeach; else: ?>
                    <div class="list-group-item text-center text-muted">No click data available yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Define page-specific scripts to be loaded by footer.php
$page_scripts = "
<script src=\"https://cdn.jsdelivr.net/npm/chart.js\"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('clicksChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: " . json_encode($clicks_last_7_days['labels']) . ",
                datasets: [{
                    label: 'Clicks',
                    data: " . json_encode($clicks_last_7_days['data']) . ",
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                    pointRadius: 4,
                    tension: 0.4,
                    fill: true,
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: { 
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                    x: { grid: { display: false } }
                },
                plugins: { legend: { display: false } },
                interaction: { intersect: false, mode: 'index' },
            }
        });
    }
});
</script>
";

// Include the new, modern footer
require_once 'includes/footer.php';
?>