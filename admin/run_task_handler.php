<?php
/**
 * AJAX Handler for Manually Running Scheduled Tasks
 *
 * This script is called via AJAX from the scheduler page to execute a background PHP script.
 * It is a secure entry point for running approved cron jobs on demand.
 */

// Set a longer execution time for potentially long-running background tasks.
set_time_limit(600);
header('Content-Type: application/json');

// =================================================================
// 1. CORE SETUP AND SECURITY CHECKS
// =================================================================

// Load the core application and enforce admin authentication.
// Note: This script does NOT include any visual elements (header/footer).
require_once __DIR__ . '/../includes/app.php';
require_login();

$response = ['status' => 'error', 'message' => 'Invalid request or task key.'];

// This script only accepts POST requests with a valid CSRF token and a task key.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['task_key']) || !verify_csrf_token($_POST['csrf_token'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $response['message'] = 'Invalid security token. Please refresh the page and try again.';
    }
    echo json_encode($response);
    exit;
}

$task_key = $_POST['task_key'];

// =================================================================
// 2. TASK EXECUTION LOGIC
// =================================================================

// A whitelist of approved tasks. This MUST match the tasks defined in scheduler.php.
$cron_jobs = [
    'product_import_update' => ['script_path' => realpath(__DIR__ . '/../jobs/cron_job.php'), 'params' => '--no-images'],
    'product_import_new' => ['script_path' => realpath(__DIR__ . '/../jobs/cron_job.php'), 'params' => '--force-new'],
    'refetch_images' => ['script_path' => realpath(__DIR__ . '/../jobs/cron_job.php'), 'params' => '--refetch-images'],
    'analyze_trends' => ['script_path' => realpath(__DIR__ . '/../jobs/trend_analyzer.php'), 'params' => '']
];

if (!array_key_exists($task_key, $cron_jobs)) {
    echo json_encode($response);
    exit;
}

$job = $cron_jobs[$task_key];
$script_path = $job['script_path'];
$params = $job['params'];

if (!$script_path || !file_exists($script_path)) {
    $response['message'] = 'Error: The target script file could not be found.';
    echo json_encode($response);
    exit;
}

try {
    // Determine the PHP executable path. 'php' is standard for Linux/macOS.
    $php_executable = 'php';

    // Build the command securely.
    $command = escapeshellcmd($php_executable) . ' ' . escapeshellarg($script_path);
    if (!empty($params)) {
        // Parameters are passed directly. Ensure they are safe within the cron scripts.
        $command .= ' ' . $params;
    }
    
    // Execute the command and capture all output (STDOUT and STDERR).
    $output = shell_exec($command . ' 2>&1');

    log_admin_activity("Manually ran task: {$task_key}");

    $response = [
        'status' => 'success',
        'message' => 'Task executed successfully.',
        'output' => $output ?: 'Task completed with no output.'
    ];

} catch (Exception $e) {
    $response['message'] = 'A PHP exception occurred while trying to run the task.';
    $response['output'] = $e->getMessage();
    error_log("Task runner exception for '{$task_key}': " . $e->getMessage());
}

echo json_encode($response);
?>