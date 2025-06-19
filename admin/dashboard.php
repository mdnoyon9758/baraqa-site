<?php
// Set the unique key for this page, which will be used to highlight the active sidebar menu item
$page_key = 'dashboard';
$page_title = 'Dashboard';

// Load the core application file which handles sessions, db connections, and functions
require_once __DIR__ . '/../includes/app.php';

// Ensure the user is an authenticated admin
require_login();

// --- Fetch all necessary data for the dashboard widgets ---
try {
    // Fetch total counts for the main statistics cards
    $total_products = $pdo->query("SELECT COUNT(id) FROM products WHERE is_published = 1")->fetchColumn();
    $total_orders = $pdo->query("SELECT COUNT(id) FROM orders")->fetchColumn(); // Assuming you have an 'orders' table
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
    // Set a flash message for the error and redirect or log
    set_flash_message('A database error occurred on the dashboard: ' . $e->getMessage(), 'danger');
    // For a live site, you might just show a generic error and log the detailed one.
}

// Include the new, modern header
require_once 'includes/header.php';
?>

<!-- Page-specific header with title and breadcrumbs -->
<div class="page-header mb-4">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <h1 class="page-title"><?php echo e($page_title); ?></h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/admin/dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                </ol>
            </nav>
        </div>
        <div class="col-sm-6 text-sm-end">
            <div class="btn-toolbar justify-content-sm-end">
                <a href="/admin/products.php?action=add" class="btn btn-primary"><i class="fas fa-plus me-2"></i>New Product</a>
                <a href="/admin/settings.php" class="btn btn-outline-secondary ms-2"><i class="fas fa-cog"></i>Settings</a>
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
                <a href="/admin/products.php" class="stretched-link"></a>
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
                <a href="/admin/orders.php" class="stretched-link"></a>
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
                <a href="/admin/users.php" class="stretched-link"></a>
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
                <a href="/admin/clicked_leads.php" class="stretched-link"></a>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Row (Chart and Lists) -->
<div class="row">
    <!-- Clicks Chart -->
    <div class="col-lg-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Affiliate Clicks (Last 7 Days)</h6>
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height:300px;">
                    <canvas id="clicksChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Trending Products -->
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

    <!-- Top Clicked Products -->
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
// We will move the script to a separate file, but for now, we define it here to be included by the footer.
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