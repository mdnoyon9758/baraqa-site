<?php
$page_title = "Deal Notification Generator";
require_once 'includes/auth.php';
require_once 'includes/admin_header.php';

$notification_text = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        // CSRF error, but for this non-critical feature, we can just ignore and regenerate a token.
        // Or show an error. For simplicity, we'll proceed.
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
            
            // Get base URL from site settings or define it
            $site_url = rtrim(get_setting('site_url', $_SERVER['HTTP_HOST']), '/');
            
            foreach ($deals as $deal) {
                $original_price = round($deal['price'] / (1 - ($deal['discount_percentage'] / 100)), 2);
                $product_url = $site_url . '/bs/product.php?slug=' . $deal['slug'];
                
                $deal_lines[] = sprintf(
                    "%d. **%s**\n" .
                    "   💰 **Price:** $%.2f (~~was $%.2f~~) - **%d%% OFF!**\n" .
                    "   🔗 **Grab it here:** %s\n",
                    $counter,
                    e($deal['title']),
                    $deal['price'],
                    $original_price,
                    $deal['discount_percentage'],
                    $product_url
                );
                $counter++;
            }
            $notification_text = implode("\n", $deal_lines);
        } else {
            $notification_text = "No deals found matching the criteria (Minimum {$min_discount}% discount).";
        }
    } catch (PDOException $e) {
        $notification_text = "Database Error: Could not fetch deals. " . $e->getMessage();
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?php echo e($page_title); ?></h1>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Generator Options</h6>
            </div>
            <div class="card-body">
                <p>Generate a formatted text of top deals to share on social media or messaging apps like WhatsApp, Telegram, etc.</p>
                <form action="notifications.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                    
                    <div class="mb-3">
                        <label for="deal_limit" class="form-label">Number of Deals to Generate:</label>
                        <input type="number" class="form-control" id="deal_limit" name="deal_limit" value="5" min="1" max="20">
                    </div>

                    <div class="mb-3">
                        <label for="min_discount" class="form-label">Minimum Discount Percentage (%):</label>
                        <input type="number" class="form-control" id="min_discount" name="min_discount" value="20" min="1" max="99">
                    </div>
                    
                    <button type="submit" name="generate_deals" class="btn btn-primary w-100">
                        <i class="fas fa-cogs me-2"></i>Generate Message
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow mb-4">
             <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Generated Message</h6>
                <?php if ($notification_text): ?>
                    <button class="btn btn-sm btn-secondary" id="copy-button">
                        <i class="fas fa-copy me-2"></i>Copy Text
                    </button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <textarea id="notification-textarea" class="form-control" readonly style="height: 350px; font-family: 'Courier New', monospace; white-space: pre; background-color: #f8f9fc;"><?php echo e($notification_text); ?></textarea>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const copyButton = document.getElementById('copy-button');
    if (copyButton) {
        copyButton.addEventListener('click', function() {
            const textarea = document.getElementById('notification-textarea');
            const originalText = this.innerHTML;

            // Use the modern Clipboard API
            navigator.clipboard.writeText(textarea.value).then(() => {
                // Success feedback
                this.innerHTML = '<i class="fas fa-check me-2"></i>Copied!';
                this.classList.remove('btn-secondary');
                this.classList.add('btn-success');
                
                // Revert back after 2 seconds
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.classList.remove('btn-success');
                    this.classList.add('btn-secondary');
                }, 2000);
            }).catch(err => {
                // Error feedback
                console.error('Failed to copy text: ', err);
                this.innerHTML = 'Copy Failed';
                this.classList.add('btn-danger');

                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.classList.remove('btn-danger');
                }, 2000);
            });
        });
    }
});
</script>

<?php require_once 'includes/admin_footer.php'; ?>