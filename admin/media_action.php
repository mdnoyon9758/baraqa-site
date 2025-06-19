<?php
require_once __DIR__ . '/../includes/app.php';
require_login();

header('Content-Type: application/json');

// Verify CSRF token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    echo json_encode(['status' => 'error', 'message' => 'CSRF token validation failed.']);
    exit;
}

$action = $_POST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Invalid action.'];

try {
    switch ($action) {
        // CASE 1: Get media details for modal
        case 'get_details':
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("SELECT * FROM media WHERE id = ?");
            $stmt->execute([$id]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($file) {
                // Generate HTML for modal body
                $file_url = '/' . $file['file_path'];
                $file_size_kb = round($file['file_size'] / 1024, 2);
                $upload_date = date('F j, Y, g:i a', strtotime($file['uploaded_at']));

                $html = '
                <div class="row">
                    <div class="col-md-5 text-center">
                        <img src="' . e($file_url) . '" class="img-fluid rounded mb-3" style="max-height: 300px;">
                        <p class="mb-1"><strong>Filename:</strong> ' . e($file['file_name']) . '</p>
                        <p class="mb-1"><strong>File Type:</strong> ' . e($file['file_type']) . '</p>
                        <p class="mb-1"><strong>File Size:</strong> ' . $file_size_kb . ' KB</p>
                        <p class="mb-1"><strong>Uploaded:</strong> ' . $upload_date . '</p>
                        <input type="text" readonly class="form-control form-control-sm" value="' . e(SITE_URL . '/' . $file['file_path']) . '">
                    </div>
                    <div class="col-md-7">
                        <form id="update-media-form">
                            <input type="hidden" name="id" value="' . e($file['id']) . '">
                            <div class="mb-3">
                                <label for="alt_text" class="form-label">Alt Text</label>
                                <input type="text" class="form-control" name="alt_text" value="' . e($file['alt_text'] ?? '') . '">
                            </div>
                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" name="title" value="' . e($file['title'] ?? '') . '">
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3">' . e($file['description'] ?? '') . '</textarea>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal-footer mt-3">
                    <button type="button" id="delete-media-btn" class="btn btn-danger me-auto" data-id="' . e($file['id']) . '">Delete Permanently</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="save-media-btn" class="btn btn-primary" data-id="' . e($file['id']) . '">Save Changes</button>
                </div>';
                $response = ['status' => 'success', 'html' => $html];
            } else {
                $response['message'] = 'File not found.';
            }
            break;

        // CASE 2: Update media details
        case 'update_details':
            $id = (int)$_POST['id'];
            $alt_text = $_POST['alt_text'];
            $title = $_POST['title'];
            $description = $_POST['description'];

            $stmt = $pdo->prepare("UPDATE media SET alt_text = ?, title = ?, description = ? WHERE id = ?");
            $stmt->execute([$alt_text, $title, $description, $id]);
            $response = ['status' => 'success', 'message' => 'Media details updated successfully.'];
            break;

        // CASE 3: Delete media (single or bulk)
        case 'delete_media':
            $ids = (array)($_POST['ids'] ?? []);
            if (empty($ids)) {
                 $response['message'] = 'No files selected for deletion.';
                 break;
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            // First, get file paths to delete from server
            $stmt = $pdo->prepare("SELECT file_path FROM media WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $files_to_delete = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Second, delete records from DB
            $stmt = $pdo->prepare("DELETE FROM media WHERE id IN ($placeholders)");
            $stmt->execute($ids);

            // Third, delete files from server
            foreach ($files_to_delete as $file_path) {
                if (file_exists(__DIR__ . '/../' . $file_path)) {
                    @unlink(__DIR__ . '/../' . $file_path);
                }
            }
            
            $response = ['status' => 'success', 'message' => count($ids) . ' file(s) deleted successfully.'];
            break;

        // CASE 4: Search media
        case 'search_media':
            $query = '%' . ($_POST['query'] ?? '') . '%';
            $stmt = $pdo->prepare("SELECT * FROM media WHERE file_name LIKE ? ORDER BY uploaded_at DESC LIMIT 50");
            $stmt->execute([$query]);
            $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $html = '';
            if (empty($files)) {
                $html = '<div class="col-12"><div class="alert alert-warning text-center">No files match your search.</div></div>';
            } else {
                foreach ($files as $file) {
                     $html .= '
                     <div class="col-lg-2 col-md-3 col-sm-4 col-6 media-item-container" data-id="'.e($file['id']).'">
                         <div class="card h-100 shadow-sm media-card" data-id="'.e($file['id']).'">
                             <div class="media-card-img-wrapper">
                                 <img src="/'.e($file['file_path']).'" class="card-img-top" loading="lazy" alt="'.e($file['alt_text'] ?? $file['file_name']).'">
                                 <div class="selection-overlay"><i class="bi bi-check-circle-fill"></i></div>
                             </div>
                         </div>
                     </div>';
                }
            }
            $response = ['status' => 'success', 'html' => $html];
            break;
    }
} catch (PDOException $e) {
    // Log the error for debugging
    error_log('Media Action Error: ' . $e->getMessage());
    $response['message'] = 'A database error occurred.';
} catch (Exception $e) {
    error_log('Media Action Error: ' . $e->getMessage());
    $response['message'] = 'A general error occurred.';
}

echo json_encode($response);
?>