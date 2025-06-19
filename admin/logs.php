<?php
// =================================================================
// 1. PAGE CONFIGURATION AND CORE SETUP
// =================================================================
$page_key = 'logs'; // A unique key for the sidebar
$page_title = 'System Logs';

require_once __DIR__ . '/../includes/app.php';
require_login();

// =================================================================
// 2. HANDLE POST REQUESTS (CLEAR LOGS)
// =================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_flash_message('CSRF token mismatch. Action aborted.', 'danger');
        header('Location: /admin/logs.php');
        exit;
    }

    $log_type = $_POST['log_type'] ?? '';
    try {
        if ($log_type === 'activity') {
            $pdo->query("TRUNCATE TABLE admin_activity_log");
            log_admin_activity("Cleared the admin activity log."); // Log this action after clearing
            set_flash_message('Admin activity log has been cleared.', 'success');
        } elseif ($log_type === 'cron') {
            $cron_log_file = realpath(__DIR__ . '/../jobs/logs') . '/product_import.log';
            if (file_exists($cron_log_file) && is_writable($cron_log_file)) {
                file_put_contents($cron_log_file, '[' . date('Y-m-d H:i:s') . '] Log cleared by admin.' . PHP_EOL);
                set_flash_message('Cron job log has been cleared.', 'success');
            } else {
                throw new Exception("Cron log file not found or is not writable.");
            }
        }
    } catch (Exception $e) {
        set_flash_message('Failed to clear log: ' . $e->getMessage(), 'danger');
    }

    header('Location: /admin/logs.php');
    exit;
}

// =================================================================
// 3. PREPARE DATA AND RENDER THE VIEW
// =================================================================

require_once 'includes/header.php';

// Define log file paths
$log_dir = realpath(__DIR__ . '/../jobs/logs');
$cron_log_file = $log_dir ? $log_dir . '/product_import.log' : '';
$php_error_log = ini_get('error_log');

// Function to read log files safely
function read_log_file($file_path, $lines = 100) {
    if (empty($file_path) || !file_exists($file_path) || !is_readable($file_path)) {
        return "Log file not found or is not readable at: " . e($file_path);
    }
    try {
        $file = new SplFileObject($file_path, 'r');
        $file->seek(PHP_INT_MAX);
        $last_line = $file->key();
        $iterator = new LimitIterator($file, max(0, $last_line - $lines), $last_line);
        $file_content = implode('', iterator_to_array($iterator));
        return empty(trim($file_content)) ? 'Log file is empty.' : e($file_content);
    } catch (Exception $e) {
        return "Error reading log file: " . e($e->getMessage());
    }
}

// Fetch admin activity logs
$activity_logs = $pdo->query("SELECT * FROM admin_activity_log ORDER BY created_at DESC LIMIT 100")->fetchAll();
?>

<!-- Page Header -->
<div class="page-header mb-4">
    <h1 class="page-title"><?php echo e($page_title); ?></h1>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="logTabs" role="tablist">
            <li class="nav-item" role="presentation"><button class="nav-link active" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity-pane" type="button">Admin Activity</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="cron-tab" data-bs-toggle="tab" data-bs-target="#cron-pane" type="button">Cron Job Log</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="error-tab" data-bs-toggle="tab" data-bs-target="#error-pane" type="button">PHP Error Log</button></li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="logTabsContent">
            <!-- Admin Activity Log Tab -->
            <div class="tab-pane fade show active" id="activity-pane" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Latest 100 Admin Actions</h5>
                    <form action="/admin/logs.php" method="POST" onsubmit="return confirm('Are you sure you want to clear all activity logs? This cannot be undone.');">
                         <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                         <input type="hidden" name="log_type" value="activity">
                         <button type="submit" name="action" value="clear" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt me-2"></i>Clear Activity Log</button>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>Time</th><th>Admin</th><th>Action</th><th>IP Address</th></tr></thead>
                        <tbody>
                            <?php if(empty($activity_logs)): ?>
                                <tr><td colspan="4" class="text-center p-4">No admin activity recorded yet.</td></tr>
                            <?php else: foreach ($activity_logs as $log): ?>
                                <tr>
                                    <td><?php echo date("d M Y, H:i:s", strtotime($log['created_at'])); ?></td>
                                    <td><?php echo e($log['admin_name']); ?></td>
                                    <td><?php echo e($log['action']); ?></td>
                                    <td><?php echo e($log['ip_address']); ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Cron Job Log Tab -->
            <div class="tab-pane fade" id="cron-pane" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                     <h5 class="mb-0">Cron Job Log (Last 100 lines)</h5>
                     <form action="/admin/logs.php" method="POST" onsubmit="return confirm('Are you sure you want to clear the cron log file?');">
                         <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                         <input type="hidden" name="log_type" value="cron">
                         <button type="submit" name="action" value="clear" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt me-2"></i>Clear Cron Log</button>
                    </form>
                </div>
                <pre class="bg-dark text-white p-3 rounded" style="max-height: 500px; overflow-y: auto;"><?php echo read_log_file($cron_log_file); ?></pre>
            </div>
            <!-- PHP Error Log Tab -->
            <div class="tab-pane fade" id="error-pane" role="tabpanel">
                <h5 class="mb-3">PHP Error Log (Last 100 lines)</h5>
                <pre class="bg-dark text-white p-3 rounded" style="max-height: 500px; overflow-y: auto;"><?php echo read_log_file($php_error_log); ?></pre>
                <small class="text-muted">Log file location: <code><?php echo e($php_error_log); ?></code></small>
            </div>
        </div>
    </div>
</div>

<?php
// No page-specific scripts needed
require_once 'includes/footer.php';
?>