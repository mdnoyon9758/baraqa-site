<?php
set_time_limit(600);
header('Content-Type: application/json');
require_once 'includes/auth.php';

$response = ['status' => 'error', 'message' => 'Invalid task key.', 'output' => ''];
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['task_key'])) {
    echo json_encode($response); exit;
}
$task_key = $_POST['task_key'];

// This list MUST match scheduler.php
$cron_jobs = [
    'product_import_update' => ['script_path' => realpath(__DIR__ . '/../jobs/cron_job.php'), 'params' => '--no-images'],
    'product_import_new' => ['script_path' => realpath(__DIR__ . '/../jobs/cron_job.php'), 'params' => '--force-new'],
    'refetch_images' => ['script_path' => realpath(__DIR__ . '/../jobs/cron_job.php'), 'params' => '--refetch-images'],
];

if (!array_key_exists($task_key, $cron_jobs)) {
    echo json_encode($response); exit;
}
$job = $cron_jobs[$task_key];
$script_path = $job['script_path'];
$params = $job['params'];

if (!$script_path || !file_exists($script_path)) {
    $response['message'] = 'Error: The target script file could not be found.';
    echo json_encode($response); exit;
}

try {
    $php_executable = 'C:\xampp\php\php.exe';
    $command = '"' . $php_executable . '" "' . $script_path . '" ' . $params;
    $output = shell_exec($command . ' 2>&1');
    $response = ['status' => 'success', 'message' => 'Task executed.', 'output' => $output ?: 'Task completed with no output.'];
} catch (Exception $e) {
    $response = ['message' => 'A PHP exception occurred.', 'output' => $e->getMessage()];
}
echo json_encode($response);
?>