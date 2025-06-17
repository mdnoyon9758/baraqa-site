<?php
// --- 1. ALL PHP LOGIC GOES HERE ---
// This part handles the form submission BEFORE any HTML is sent.

$page_title = "Manage Brands";
require_once 'includes/auth.php'; // Authentication and session start

// --- Handle POST requests for Add/Edit/Delete ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token check must be done first
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'CSRF token mismatch. Action aborted.';
        header('Location: manage_brands.php');
        exit;
    }

    $action_type = $_POST['action_type'] ?? '';

    try {
        if ($action_type === 'add' || $action_type === 'edit') {
            $name = trim($_POST['name']);
            if (empty($name)) {
                throw new Exception("Brand name cannot be empty.");
            }

            $params = [
                'name' => $name,
                'brand_logo_url' => trim($_POST['brand_logo_url']),
                'description' => trim($_POST['description']),
                'is_featured' => isset($_POST['is_featured']) ? 1 : 0
            ];

            $slug = slugify($params['name']);
            
            // Check for duplicate slug
            $original_slug = $slug;
            $counter = 1;
            while(true) {
                $check_slug_sql = "SELECT id FROM brands WHERE slug = :slug";
                $check_params = ['slug' => $slug];
                if ($action_type === 'edit') {
                    $check_slug_sql .= " AND id != :id";
                    $check_params['id'] = (int)$_POST['id'];
                }
                $stmt = $pdo->prepare($check_slug_sql);
                $stmt->execute($check_params);
                if (!$stmt->fetch()) {
                    break; // Slug is unique
                }
                $slug = $original_slug . '-' . $counter++;
            }
            $params['slug'] = $slug;

            if ($action_type === 'add') {
                $sql = "INSERT INTO brands (name, slug, brand_logo_url, description, is_featured) VALUES (:name, :slug, :brand_logo_url, :description, :is_featured)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $_SESSION['success_message'] = 'Brand added successfully!';
            } else { // 'edit'
                $params['id'] = (int)$_POST['id'];
                $sql = "UPDATE brands SET name=:name, slug=:slug, brand_logo_url=:brand_logo_url, description=:description, is_featured=:is_featured WHERE id=:id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $_SESSION['success_message'] = 'Brand updated successfully!';
            }
        } elseif ($action_type === 'delete') {
            $id_to_delete = (int)$_POST['id'];
            $pdo->prepare("UPDATE products SET brand_id = NULL WHERE brand_id = ?")->execute([$id_to_delete]);
            
            $stmt = $pdo->prepare("DELETE FROM brands WHERE id = :id");
            $stmt->execute(['id' => $id_to_delete]);
            $_SESSION['success_message'] = 'Brand deleted successfully. Associated products have been unlinked.';
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Operation failed: ' . $e->getMessage();
    }
    
    // Redirect after processing POST request to prevent re-submission on refresh
    header('Location: manage_brands.php');
    exit;
}

// --- 2. START HTML OUTPUT ---
// Now that all logic is done, we can safely include the header.
require_once 'includes/admin_header.php';

// Fetch all brands for the list view (This is for displaying the page, not for POST logic)
$brands = $pdo->query("SELECT * FROM brands ORDER BY name ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Manage Brands</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBrandModal"><i class="fas fa-plus me-2"></i>Add New Brand</button>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%">
                <thead>
                    <tr>
                        <th style="width: 10%;">Logo</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th class="text-center">Featured</th>
                        <th class="text-center" style="width: 15%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($brands)): ?>
                        <tr><td colspan="5" class="text-center">No brands found. Add one to get started!</td></tr>
                    <?php else: ?>
                        <?php foreach ($brands as $brand): ?>
                            <tr>
                                <td class="text-center"><img src="<?php echo e($brand['brand_logo_url'] ?: 'https://via.placeholder.com/100x40?text=No+Logo'); ?>" alt="<?php echo e($brand['name']); ?>" style="max-width: 100px; max-height: 40px; object-fit: contain;"></td>
                                <td><?php echo e($brand['name']); ?></td>
                                <td><?php echo e($brand['slug']); ?></td>
                                <td class="text-center"><?php echo $brand['is_featured'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>'; ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-info edit-btn" title="Edit" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editBrandModal"
                                        data-id="<?php echo e($brand['id']); ?>"
                                        data-name="<?php echo e($brand['name']); ?>"
                                        data-logo_url="<?php echo e($brand['brand_logo_url']); ?>"
                                        data-description="<?php echo e($brand['description']); ?>"
                                        data-is_featured="<?php echo e($brand['is_featured']); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-btn" title="Delete" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal" data-id="<?php echo e($brand['id']); ?>"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Brand Modal -->
<div class="modal fade" id="addBrandModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="manage_brands.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                    <input type="hidden" name="action_type" value="add">
                    
                    <div class="mb-3"><label for="addName" class="form-label">Brand Name</label><input type="text" class="form-control" id="addName" name="name" required></div>
                    <div class="mb-3"><label for="addLogo" class="form-label">Logo URL</label><input type="url" class="form-control" id="addLogo" name="brand_logo_url" placeholder="https://example.com/logo.png"></div>
                    <div class="mb-3"><label for="addDescription" class="form-label">Description</label><textarea class="form-control" id="addDescription" name="description" rows="3"></textarea></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="addIsFeatured" name="is_featured" value="1"><label class="form-check-label" for="addIsFeatured">Feature this brand on homepage</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Add Brand</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Brand Modal -->
<div class="modal fade" id="editBrandModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="manage_brands.php" method="POST">
                <div class="modal-header"><h5 class="modal-title">Edit Brand</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                    <input type="hidden" name="action_type" value="edit">
                    <input type="hidden" name="id" id="editId">
                    
                    <div class="mb-3"><label for="editName" class="form-label">Brand Name</label><input type="text" class="form-control" id="editName" name="name" required></div>
                    <div class="mb-3"><label for="editLogo" class="form-label">Logo URL</label><input type="url" class="form-control" id="editLogo" name="brand_logo_url"></div>
                    <div class="mb-3"><label for="editDescription" class="form-label">Description</label><textarea class="form-control" id="editDescription" name="description" rows="3"></textarea></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="editIsFeatured" name="is_featured" value="1"><label class="form-check-label" for="editIsFeatured">Feature this brand on homepage</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save Changes</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Confirm Deletion</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">Are you sure you want to delete this brand? Products associated with this brand will be unlinked.</div>
            <div class="modal-footer">
                <form action="manage_brands.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                    <input type="hidden" name="action_type" value="delete">
                    <input type="hidden" name="id" id="deleteId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Delete Brand</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editBrandModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            document.getElementById('editId').value = button.dataset.id;
            document.getElementById('editName').value = button.dataset.name;
            document.getElementById('editLogo').value = button.dataset.logo_url;
            document.getElementById('editDescription').value = button.dataset.description;
            document.getElementById('editIsFeatured').checked = button.dataset.is_featured == '1';
        });
    }

    const deleteModal = document.getElementById('deleteConfirmationModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            document.getElementById('deleteId').value = button.dataset.id;
        });
    }
});
</script>

<?php require_once 'includes/admin_footer.php'; ?>