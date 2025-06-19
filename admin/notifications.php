<?php
// =================================================================
// 1. PAGE CONFIGURATION AND CORE SETUP
// =================================================================
$page_key = 'notifications';
$page_title = 'Deal Notification Generator';

require_once __DIR__ . '/../includes/app.php';
require_login();

// =================================================================
// 2. HANDLE POST REQUEST (GENERATE NOTIFICATION)
// =================================================================
$notification_text = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_flash_message('CSRF token mismatch. Action aborted.', 'danger');
        header('Location: /admin/notifications.php');
        exit;
    }

    $limit = isset($_POST['deal_limit']) ? (int)$_POST['deal_limit'] : 5;
    $min_discount = isset($_POST['min_discount']) ? (int)$_POST['min_discount'] : 20;

    try {
        $stmt = $pdo->prepare(
            "SELECT slug, title, price, discount_percentage 
             FROM products 
             WHERE discount_percentage >= :min_discount AND is_published = 1 
             ORDER BY discount_percentage DESC, trend_score DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':min_discount', $min_discount, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $deals = $stmt->fetchAll();

        if ($deals) {
            $deal_lines = [];
            $deal_lines[] = "🔥🔥 **TOP DEALS OF THE DAY!** 🔥🔥\nDon't miss out on these amazing discounts!\n";
            $counter = 1;
            
            $site_url = rtrim(get_setting('site_url', $_SERVER['HTTP_HOST']), '/');
            
            foreach ($deals as $deal) {
                $original_price = round($deal['price'] / (1 - ($deal['discount_percentage'] / 100)), 2);
                $product_url = $site_url . '/product/' . $deal['slug'];
                
                $deal_lines[] = sprintf(
                    "%d. **%s**\n" .
                    "   💰 **Price:** $%.2f (~~was $%.2f~~) - **%d%% OFF!**\n" .
                    "   🔗 **Grab it here:** %s\n",
                    $counter++,
                    e($deal['title']),
                    $deal['price'],
                    $original_price,
                    $deal['discount_percentage'],
                    $product_url
                );
            }
            $notification_text = implode("\n", $deal_lines);
        } else {
            $notification_text = "No deals found matching the criteria (Minimum {$min_discount}% discount).";
        }
    } catch (PDOException $e) {
        set_flash_message("Database Error: Could not fetch deals. " . $e->getMessage(), 'danger');
    }
}

// =================================================================
// 3. RENDER THE VIEW
// =================================================================
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header mb-4">
    <h1 class="page-title"><?php echo e($page_title); ?></h1>
</div>

<div class="row">
    <!-- Generator Options Card -->
    <div class="col-lg-5">
        <div class="card shadow-sm mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold">Generator Options</h6></div>
            <div class="card-body">
                <p class="text-muted">Generate formatted text of top deals to share on social media or messaging apps.</p>
                <form action="/admin/notifications.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div class="mb-3">
                        <label for="deal_limit" class="form-label">Number of Deals:</label>
                        <input type="number" class="form-control" id="deal_limit" name="deal_limit" value="<?php echo e($_POST['deal_limit'] ?? 5); ?>" min="1" max="20">
                    </div>

                    <div class="mb-3">
                        <label for="min_discount" class="form-label">Minimum Discount (%):</label>
                        <input type="number" class="form-control" id="min_discount" name="min_discount" value="<?php echo e($_POST['min_discount'] ?? 20); ?>" min="1" max="99">
                    </div>
                    
                    <button type="submit" name="generate_deals" class="btn btn-primary w-100">
                        <i class="fas fa-cogs me-2"></i>Generate Message
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Generated Message Card -->
    <div class="col-lg-7">
        <div class="card shadow-sm mb-4">
             <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold">Generated Message</h6>
                <?php if ($notification_text): ?>
                    <button class="btn btn-sm btn-outline-secondary" id="copy-button">
                        <i class="fas fa-copy me-2"></i>Copy Text
                    </button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <textarea id="notification-textarea" class="form-control" readonly style="height: 380px; font-family: 'Courier New', monospace; white-space: pre; background-color: #f8f9fa;"><?php echo e($notification_text); ?></textarea>
            </div>
        </div>
    </div>
</div>

<?php
// Page-specific JavaScript for the copy button
$page_scripts = "
<script>
document.addEventListener('DOMContentLoaded', function() {
    const copyButton = document.getElementById('copy-button');
    if (copyButton) {
        copyButton.addEventListener('click', function() {
            const textarea = document.getElementById('notification-textarea');
            const originalText = this.innerHTML;

            navigator.clipboard.writeText(textarea.value).then(() => {
                this.innerHTML = '<i class=\"fas fa-check me-2\"></i>Copied!';
                this.classList.remove('btn-outline-secondary');
                this.classList.add('btn-success');
                
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.classList.remove('btn-success');
                    this.classList.add('btn-outline-secondary');
                }, 2000);
            }).catch(err => {
                // Handle copy failure
            });
        });
    }
});
</script>
";

require_once 'includes/footer.php';
?>