<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/app.php';
require_login();

$response = ['status' => 'error', 'message' => 'Upload failed.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['media_files'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $response['message'] = 'CSRF token mismatch.';
        echo json_encode($response);
        exit;
    }

    $upload_dir_base = __DIR__ . '/../public/uploads/';
    $year_month_folder = date('Y') . '/' . date('m');
    $upload_path = $upload_dir_base . $year_month_folder;

    if (!is_dir($upload_path)) {
        if (!mkdir($upload_path, 0755, true)) {
            $response['message'] = 'Failed to create upload directory.';
            echo json_encode($response);
            exit;
        }
    }

    $uploaded_files = [];
    $files = $_FILES['media_files'];

    foreach ($files['name'] as $key => $name) {
        $file_name = time() . '_' . basename($name);
        $target_file = $upload_path . '/' . $file_name;
        $file_path_db = '/public/uploads/' . $year_month_folder . '/' . $file_name;

        if ($files['size'][$key] > 5000000) {
            $response['message'] = "File '{$name}' is too large (Max 5MB).";
            continue;
        }

        if (move_uploaded_file($files['tmp_name'][$key], $target_file)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO media (file_name, file_path, file_type, file_size, uploaded_by) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$file_name, $file_path_db, $files['type'][$key], $files['size'][$key], $_SESSION['admin_id']]);
                $uploaded_files[] = ['id' => $pdo->lastInsertId(), 'path' => $file_path_db, 'name' => $file_name];
            } catch (PDOException $e) {
                unlink($target_file);
            }
        }
    }

    if (!empty($uploaded_files)) {
        $response = ['status' => 'success', 'message' => 'Files uploaded successfully.', 'files' => $uploaded_files];
    } else if (empty($response['message'])) {
        $response['message'] = 'No files were uploaded successfully.';
    }
}

echo json_encode($response);