<?php
// =================================================================
// 1. PAGE CONFIGURATION AND CORE SETUP
// =================================================================
$page_key = 'scheduler';
$page_title = 'Task Scheduler';

require_once __DIR__ . '/../includes/app.php';
require_login();

// =================================================================
// 2. PREPARE DATA AND RENDER THE VIEW
// =================================================================

require_once 'includes/header.php';

// A whitelist of approved tasks. This MUST match run_task_handler.php.
$cron_jobs = [
    'product_import_update' => [
        'name' => 'Update Existing Products',
        'description' => 'Updates text data (price, rating etc.) for existing products. Does NOT fetch images.',
        'script_path' => realpath(__DIR__ . '/../jobs/cron_job.php'),
        'params' => '--no-images'
    ],
    'product_import_new' => [
        'name' => 'Add New Products',
        'description' => 'Fetches and adds ONLY new products with images.',
        'script_path' => realpath(__DIR__ . '/../jobs/cron_job.php'),
        'params' => '--force-new'
    ],
    'refetch_images' => [
        'name' => 'Refetch All Images',
        'description' => 'Re-fetches images from APIs for ALL existing products. Use this if images are missing.',
        'script_path' => realpath(__DIR__ . '/../jobs/cron_job.php'),
        'params' => '--refetch-images'
    ],
    'analyze_trends' => [
        'name' => 'Analyze Product Trends',
        'description' => 'Calculates and updates the trend score for all products.',
        'script_path' => realpath(__DIR__ . '/../jobs/trend_analyzer.php'),
        'params' => ''
    ]
];
?>

<!-- Page Header -->
<div class="page-header mb-4">
    <h1 class="page-title"><?php echo e($page_title); ?></h1>
</div>

<div class="alert alert-info">
    <h4 class="alert-heading"><i class="fas fa-info-circle me-2"></i>How does this work?</h4>
    <p class="mb-0">This page allows you to manually run automated tasks (cron jobs) that are usually handled by the server. The output of each task will be shown in a popup window. This is useful for testing or forcing an immediate update.</p>
</div>

<div class="card shadow-sm">
    <div class="card-header"><h6 class="m-0 font-weight-bold">Available Tasks</h6></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Task Name</th>
                        <th>Description</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cron_jobs as $key => $job): ?>
                        <tr>
                            <td>
                                <strong class="text-dark"><?php echo e($job['name']); ?></strong>
                                <br>
                                <small class="text-muted"><code><?php echo e(basename($job['script_path'])); ?> <?php echo e($job['params']); ?></code></small>
                            </td>
                            <td><?php echo e($job['description']); ?></td>
                            <td class="text-center">
                                <?php if ($job['script_path'] && file_exists($job['script_path'])): ?>
                                    <button class="btn btn-primary run-task-btn" data-task-key="<?php echo e($key); ?>">
                                        <i class="fas fa-play-circle me-2"></i>Run Now
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-secondary" disabled title="Script not found at expected path">
                                        <i class="fas fa-exclamation-triangle me-2"></i>Not Found
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Task Output Modal -->
<div class="modal fade" id="taskOutputModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="taskOutputModalLabel">Task Output</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body bg-dark text-white"><pre id="task-output-content" style="white-space: pre-wrap; word-wrap: break-word;"></pre></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

<?php
// Page-specific JavaScript
$page_scripts = "
<script>
document.addEventListener('DOMContentLoaded', function() {
    const runButtons = document.querySelectorAll('.run-task-btn');
    const outputModalElement = document.getElementById('taskOutputModal');
    if (!outputModalElement) return;

    const outputModal = new bootstrap.Modal(outputModalElement);
    const outputContent = document.getElementById('task-output-content');
    const modalTitle = document.getElementById('taskOutputModalLabel');
    
    runButtons.forEach(button => {
        button.addEventListener('click', function() {
            const taskKey = this.dataset.taskKey;
            const originalButtonHTML = this.innerHTML;
            const taskName = this.closest('tr').querySelector('strong').textContent;

            this.innerHTML = '<span class=\"spinner-border spinner-border-sm\" role=\"status\" aria-hidden=\"true\"></span> Running...';
            this.disabled = true;
            
            outputContent.textContent = 'Executing task, please wait...';
            modalTitle.textContent = `Output for: ${taskName}`;
            outputModal.show();
            
            const formData = new FormData();
            formData.append('task_key', taskKey);
            formData.append('csrf_token', '" . generate_csrf_token() . "'); // Pass the CSRF token

            fetch('/admin/run_task_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => Promise.reject(`HTTP Error ${response.status}: ${text}`));
                }
                return response.json();
            })
            .then(data => {
                if(data.status === 'success') {
                    outputContent.textContent = data.output;
                } else {
                    outputContent.textContent = `Error: ${data.message}\\n\\n${data.output || ''}`;
                }
            })
            .catch(error => {
                outputContent.textContent = `An unexpected error occurred. Please check the browser console.\\n\\n${error}`;
            })
            .finally(() => {
                this.innerHTML = originalButtonHTML;
                this.disabled = false;
            });
        });
    });
});
</script>
";

require_once 'includes/footer.php';
?>