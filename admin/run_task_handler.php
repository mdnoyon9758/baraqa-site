<?php
// Increase execution time for potentially long-running tasks
set_time_limit(600);
header('Content-Type: application/json');
require_once 'includes/auth.php'; // Ensures only logged-in admins can run tasks

$response = ['status' => 'error', 'message' => 'Invalid task key.', 'output' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['task_key'])) {
    echo json_encode($response);
    exit;
}
$task_key = $_POST['task_key'];

// This list MUST match scheduler.php for consistency
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
    // CRITICAL CHANGE: Use 'php' for Linux environments like Render.com
    // Removed the hardcoded Windows path 'C:\xampp\php\php.exe'
    $php_executable = 'php';

    // Build the command securely to prevent command injection vulnerabilities
    $command = escapeshellcmd($php_executable) . ' ' . escapeshellarg($script_path);
    if (!empty($params)) {
        // We assume params are safe, but it's good practice to be aware
        $command .= ' ' . $params;
    }
    
    // Execute the command and capture both STDOUT and STDERR
    $output = shell_exec($command . ' 2>&1');

    $response = [
        'status' => 'success',
        'message' => 'Task executed successfully.',
        'output' => $output ?: 'Task completed with no output.'
    ];

} catch (Exception $e) {
    $response['message'] = 'A PHP exception occurred while trying to run the task.';
    $response['output'] = $e->getMessage();
}

echo json_encode($response);
?>