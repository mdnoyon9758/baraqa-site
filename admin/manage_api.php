<?php
// --- 1. ALL PHP LOGIC FIRST ---
$page_title = "API & Affiliate Settings";
require_once 'includes/auth.php'; // This must come first
require_once __DIR__ . '/../config.php'; // Load encryption keys

// Handle POST requests for Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'CSRF token mismatch. Action aborted.';
        header('Location: manage_api.php');
        exit;
    }

    $action_type = $_POST['action_type'] ?? '';

    try {
        if ($action_type === 'add' || $action_type === 'edit') {
            $api_name = trim($_POST['api_name']);
            if (empty($api_name)) {
                throw new Exception("API Name cannot be empty.");
            }

            $params = [
                'api_name' => $api_name,
                'affiliate_tag' => trim($_POST['affiliate_tag']),
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            // Only add keys to params if they are being updated
            if (!empty($_POST['api_key'])) {
                $params['api_key'] = encrypt_data(trim($_POST['api_key']));
            }
            if (!empty($_POST['api_secret'])) {
                $params['api_secret'] = encrypt_data(trim($_POST['api_secret']));
            }

            if ($action_type === 'add') {
                $sql = "INSERT INTO affiliate_config (api_name, api_key, api_secret, affiliate_tag, is_active) VALUES (:api_name, :api_key, :api_secret, :affiliate_tag, :is_active)";
                $stmt = $pdo->prepare($sql);
                // Ensure all keys exist for INSERT
                $params['api_key'] = $params['api_key'] ?? null;
                $params['api_secret'] = $params['api_secret'] ?? null;
                $stmt->execute($params);
                $_SESSION['success_message'] = 'API Configuration added successfully!';
            } else { // 'edit'
                $params['id'] = (int)$_POST['id'];

                $update_parts = ["api_name=:api_name", "affiliate_tag=:affiliate_tag", "is_active=:is_active"];
                if (isset($params['api_key'])) $update_parts[] = "api_key=:api_key";
                if (isset($params['api_secret'])) $update_parts[] = "api_secret=:api_secret";
                
                $sql = "UPDATE affiliate_config SET " . implode(', ', $update_parts) . " WHERE id=:id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $_SESSION['success_message'] = 'API Configuration updated successfully!';
            }
        } elseif ($action_type === 'delete') {
            $id_to_delete = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM affiliate_config WHERE id = :id");
            $stmt->execute(['id' => $id_to_delete]);
            $_SESSION['success_message'] = 'API Configuration deleted successfully.';
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Operation failed: ' . $e->getMessage();
    }
    
    // Redirect after processing POST request
    header('Location: manage_api.php');
    exit;
}

// Fetch all API configurations for display
try {
    $configs = $pdo->query("SELECT * FROM affiliate_config ORDER BY api_name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $configs = [];
    $_SESSION['error_message'] = 'Could not fetch API configurations: ' . $e->getMessage();
}

// --- 2. HTML OUTPUT STARTS HERE ---
require_once 'includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">API & Affiliate Settings</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#apiConfigModal" data-action="add"><i class="fas fa-plus me-2"></i>Add New API</button>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%">
                <thead>
                    <tr>
                        <th>API Name</th>
                        <th>API Key (Encrypted)</th>
                        <th>Affiliate Tag</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($configs as $config): ?>
                        <tr>
                            <td><strong><?php echo e($config['api_name']); ?></strong></td>
                            <td><?php echo e(substr($config['api_key'], 0, 15)); ?>...</td>
                            <td><?php echo e($config['affiliate_tag']); ?></td>
                            <td class="text-center"><?php echo $config['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'; ?></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-info edit-btn" title="Edit" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#apiConfigModal"
                                    data-id="<?php echo e($config['id']); ?>"
                                    data-action="edit"
                                    data-api_name="<?php echo e($config['api_name']); ?>"
                                    data-affiliate_tag="<?php echo e($config['affiliate_tag']); ?>"
                                    data-is_active="<?php echo e($config['is_active']); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-btn" title="Delete" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal" data-id="<?php echo e($config['id']); ?>"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit API Modal -->
<div class="modal fade" id="apiConfigModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="manage_api.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">API Configuration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                    <input type="hidden" name="action_type" id="action_type" value="add">
                    <input type="hidden" name="id" id="config_id">
                    
                    <div class="mb-3">
                        <label for="api_name" class="form-label">API Name</label>
                        <input type="text" class="form-control" id="api_name" name="api_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="api_key" class="form-label">API Key</label>
                        <input type="password" class="form-control" id="api_key" name="api_key" placeholder="Leave blank to keep unchanged">
                    </div>
                    <div class="mb-3">
                        <label for="api_secret" class="form-label">API Secret</label>
                        <input type="password" class="form-control" id="api_secret" name="api_secret" placeholder="Leave blank to keep unchanged">
                    </div>
                    <div class="mb-3">
                        <label for="affiliate_tag" class="form-label">Affiliate Tag/ID</label>
                        <input type="text" class="form-control" id="affiliate_tag" name="affiliate_tag">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1">
                        <label class="form-check-label" for="is_active">Enable this API</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Configuration</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this API configuration? This cannot be undone.
            </div>
            <div class="modal-footer">
                <form action="manage_api.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                    <input type="hidden" name="action_type" value="delete">
                    <input type="hidden" name="id" id="deleteId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const apiModal = document.getElementById('apiConfigModal');
    apiModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const action = button.getAttribute('data-action');
        const modalTitle = apiModal.querySelector('.modal-title');
        
        document.getElementById('action_type').value = action;
        
        // Reset form fields first
        apiModal.querySelector('form').reset();
        document.getElementById('config_id').value = '';

        if (action === 'edit') {
            modalTitle.textContent = 'Edit API Configuration';
            document.getElementById('config_id').value = button.dataset.id;
            document.getElementById('api_name').value = button.dataset.api_name;
            document.getElementById('affiliate_tag').value = button.dataset.affiliate_tag;
            document.getElementById('is_active').checked = button.dataset.is_active == '1';
            // Note: We do NOT populate the key/secret fields for security.
            // The user must re-enter them if they wish to change them.
        } else { // 'add'
            modalTitle.textContent = 'Add New API Configuration';
        }
    });

    const deleteModal = document.getElementById('deleteConfirmationModal');
    deleteModal.addEventListener('show.bs.modal', function(event) {
        document.getElementById('deleteId').value = event.relatedTarget.getAttribute('data-id');
    });
});
</script>

<?php require_once 'includes/admin_footer.php'; ?>