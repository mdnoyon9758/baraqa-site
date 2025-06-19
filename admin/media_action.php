<?php
/**
 * AJAX Handler for Media Actions (Delete, Update Details)
 */
header('Content-Type: application/json');

// =================================================================
// 1. CORE SETUP AND SECURITY CHECKS
// =================================================================
require_once __DIR__ . '/../includes/app.php';
require_login();

$response = ['status' => 'error', 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'])) {
    $response['message'] = 'CSRF token mismatch. Please refresh the page.';
    echo json_encode($response);
    exit;
}

// =================================================================
// 2. PROCESS ACTION
// =================================================================
$action = $_POST['action'] ?? '';
$media_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($media_id <= 0) {
    $response['message'] = 'Invalid Media ID.';
    echo json_encode($response);
    exit;
}

try {
    switch ($action) {
        case 'delete':
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT file_path FROM media WHERE id = ?");
            $stmt->execute([$media_id]);
            $media = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($media) {
                $delete_stmt = $pdo->prepare("DELETE FROM media WHERE id = ?");
                $delete_stmt->execute([$media_id]);
                
                $file_server_path = realpath(__DIR__ . '/..' . $media['file_path']);
                if ($file_server_path && file_exists($file_server_path)) {
                    unlink($file_server_path);
                }
                $pdo->commit();
                $response = ['status' => 'success', 'message' => 'Media file deleted successfully.'];
            } else {
                throw new Exception("Media not found.");
            }
            break;

        case 'update_details':
            $alt_text = trim($_POST['alt_text'] ?? '');
            $stmt = $pdo->prepare("UPDATE media SET alt_text = ? WHERE id = ?");
            $stmt->execute([$alt_text, $media_id]);
            $response = ['status' => 'success', 'message' => 'Media details updated.'];
            break;

        case 'get_details':
            $stmt = $pdo->prepare("SELECT * FROM media WHERE id = ?");
            $stmt->execute([$media_id]);
            $media = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($media) {
                // To avoid sending sensitive info, only send what's needed.
                $response = [
                    'status' => 'success',
                    'data' => [
                        'id' => $media['id'],
                        'file_path' => $media['file_path'],
                        'file_name' => $media['file_name'],
                        'alt_text' => $media['alt_text'],
                        'file_type' => $media['file_type'],
                        'file_size' => round($media['file_size'] / 1024) . ' KB',
                        'uploaded_at' => date('d M Y, H:i', strtotime($media['uploaded_at']))
                    ]
                ];
            } else {
                $response['message'] = 'Media not found.';
            }
            break;

        default:
            $response['message'] = 'Invalid action specified.';
            break;
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $response['message'] = 'An error occurred: ' . $e->getMessage();
}

echo json_encode($response);