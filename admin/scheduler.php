<?php
$page_title = "Task Scheduler";
require_once 'includes/auth.php';
require_once 'includes/admin_header.php';

// Define the list of available cron jobs/tasks
$cron_jobs = [
    'product_import_update' => [
        'name' => 'Update Existing Products (No Images)',
        'description' => 'Runs the main cron job to update text data (price, rating etc.) for existing products. Does NOT fetch images.',
        'script_path' => realpath(__DIR__ . '/../jobs/cron_job.php'),
        'params' => '--no-images' // New parameter
    ],
    'product_import_new' => [
        'name' => 'Add New Products (with Images)',
        'description' => 'Runs the main cron job to fetch and add ONLY new products with images.',
        'script_path' => realpath(__DIR__ . '/../jobs/cron_job.php'),
        'params' => '--force-new'
    ],
    'refetch_images' => [
        'name' => 'Refetch All Product Images',
        'description' => 'Force re-fetches images from Pexels for ALL existing products. Use this if images are missing.',
        'script_path' => realpath(__DIR__ . '/../jobs/cron_job.php'),
        'params' => '--refetch-images' // New parameter
    ],
];

if (!is_dir(realpath(__DIR__ . '/../jobs/logs'))) {
    mkdir(realpath(__DIR__ . '/../jobs/logs'), 0755, true);
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?php echo e($page_title); ?></h1>
</div>

<div class="alert alert-info">
    <h4 class="alert-heading"><i class="fas fa-info-circle me-2"></i>How this works?</h4>
    <p>This page allows you to manually run automated tasks. Each button runs the same core script but with different flags to perform specific actions.</p>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Available Tasks</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr><th>Task Name</th><th>Description</th><th class="text-center">Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($cron_jobs as $key => $job): ?>
                        <tr>
                            <td class="align-middle"><strong><?php echo e($job['name']); ?></strong><br><small class="text-muted"><code><?php echo e(basename($job['script_path'])); ?> <?php echo e($job['params']); ?></code></small></td>
                            <td class="align-middle"><?php echo e($job['description']); ?></td>
                            <td class="text-center align-middle">
                                <?php if (file_exists($job['script_path'])): ?>
                                    <button class="btn btn-primary run-task-btn" data-task-key="<?php echo e($key); ?>"><i class="fas fa-play-circle me-2"></i>Run Now</button>
                                <?php else: ?>
                                    <button class="btn btn-secondary" disabled><i class="fas fa-exclamation-triangle me-2"></i>Not Found</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal and JS remains the same as previous answers -->
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

<!-- AJAX script for running tasks -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const runButtons = document.querySelectorAll('.run-task-btn');
    const outputModal = new bootstrap.Modal(document.getElementById('taskOutputModal'));
    const outputContent = document.getElementById('task-output-content');
    const modalTitle = document.getElementById('taskOutputModalLabel');
    
    runButtons.forEach(button => {
        button.addEventListener('click', function() {
            const taskKey = this.dataset.taskKey;
            const originalButtonHTML = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Running...';
            this.disabled = true;
            outputContent.textContent = 'Executing task, please wait...';
            modalTitle.textContent = `Output for: ${taskKey}`;
            outputModal.show();
            const formData = new FormData();
            formData.append('task_key', taskKey);
            formData.append('csrf_token', '<?php echo e($csrf_token); ?>');
            fetch('run_task_handler.php', { method: 'POST', body: formData })
            .then(response => response.ok ? response.json() : Promise.reject(`HTTP error! status: ${response.status}`))
            .then(data => { outputContent.textContent = data.status === 'success' ? data.output : `Error: ${data.message}`; })
            .catch(error => { outputContent.textContent = `An unexpected error occurred: ${error}`; })
            .finally(() => { this.innerHTML = originalButtonHTML; this.disabled = false; });
        });
    });
});
</script>

<?php require_once 'includes/admin_footer.php'; ?>