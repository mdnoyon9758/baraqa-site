 <?php
$page_title = "System Logs";
require_once 'includes/auth.php';
require_once 'includes/admin_header.php';

// Define log file paths
$log_dir = realpath(__DIR__ . '/../jobs/logs');
$cron_log_file = $log_dir ? $log_dir . '/product_import.log' : '';
$php_error_log = ini_get('error_log'); // Get path to PHP's error log

// Handle log clearing actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
     if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'CSRF token mismatch. Action aborted.';
        header('Location: logs.php');
        exit;
    }

    $log_type = $_POST['log_type'];
    try {
        if ($log_type === 'cron' && file_exists($cron_log_file)) {
            file_put_contents($cron_log_file, '[' . date('Y-m-d H:i:s') . '] Log cleared by admin.' . PHP_EOL);
            $_SESSION['success_message'] = 'Cron job log has been cleared.';
        } elseif ($log_type === 'activity') {
            $pdo->query("TRUNCATE TABLE admin_activity_log");
            $_SESSION['success_message'] = 'Admin activity log has been cleared.';
            log_admin_activity("Cleared the admin activity log.");
        }
        // Clearing PHP error log is often restricted by server permissions, so we avoid it.
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Failed to clear log: ' . $e->getMessage();
    }

    header('Location: logs.php');
    exit;
}

// Function to read log files safely
function read_log_file($file_path, $lines = 100) {
    if (!file_exists($file_path) || !is_readable($file_path)) {
        return "Log file not found or is not readable at: " . htmlspecialchars($file_path);
    }
    // Read the last N lines of the file for performance
    $file_content = '';
    $file = new SplFileObject($file_path, 'r');
    $file->seek(PHP_INT_MAX);
    $last_line = $file->key();
    $iterator = new LimitIterator($file, max(0, $last_line - $lines), $last_line);
    $file_content = implode('', iterator_to_array($iterator));
    return empty($file_content) ? 'Log file is empty.' : htmlspecialchars($file_content);
}

// Fetch admin activity logs
$activity_logs = $pdo->query("SELECT * FROM admin_activity_log ORDER BY created_at DESC LIMIT 100")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?php echo e($page_title); ?></h1>
</div>

<div class="card shadow">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="logTabs" role="tablist">
            <li class="nav-item" role="presentation"><button class="nav-link active" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button">Admin Activity</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="cron-tab" data-bs-toggle="tab" data-bs-target="#cron" type="button">Cron Job Log</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link" id="error-tab" data-bs-toggle="tab" data-bs-target="#error" type="button">PHP Error Log</button></li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="logTabsContent">
            <!-- Admin Activity Log Tab -->
            <div class="tab-pane fade show active" id="activity" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Latest 100 Admin Actions</h5>
                    <form action="logs.php" method="POST" onsubmit="return confirm('Are you sure you want to clear all activity logs?');">
                         <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                         <input type="hidden" name="log_type" value="activity">
                         <button type="submit" name="action" value="clear" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt me-2"></i>Clear Log</button>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>Time</th><th>Admin</th><th>Action</th><th>IP Address</th></tr></thead>
                        <tbody>
                            <?php foreach ($activity_logs as $log): ?>
                                <tr>
                                    <td><?php echo date("Y-m-d H:i:s", strtotime($log['created_at'])); ?></td>
                                    <td><?php echo e($log['admin_name']); ?></td>
                                    <td><?php echo e($log['action']); ?></td>
                                    <td><?php echo e($log['ip_address']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Cron Job Log Tab -->
            <div class="tab-pane fade" id="cron" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                     <h5 class="mb-0">Cron Job Output (Last 100 lines)</h5>
                     <form action="logs.php" method="POST" onsubmit="return confirm('Are you sure you want to clear the cron log file?');">
                         <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                         <input type="hidden" name="log_type" value="cron">
                         <button type="submit" name="action" value="clear" class="btn btn-sm btn-danger"><i class="fas fa-trash-alt me-2"></i>Clear Log</button>
                    </form>
                </div>
                <pre class="bg-dark text-white p-3 rounded" style="max-height: 500px; overflow-y: auto;"><?php echo read_log_file($cron_log_file); ?></pre>
            </div>
            <!-- PHP Error Log Tab -->
            <div class="tab-pane fade" id="error" role="tabpanel">
                <h5 class="mb-3">PHP Error Log (Last 100 lines)</h5>
                <pre class="bg-dark text-white p-3 rounded" style="max-height: 500px; overflow-y: auto;"><?php echo read_log_file($php_error_log); ?></pre>
                <small class="text-muted">Log file location: <?php echo e($php_error_log); ?></small>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>
