<?php
// =================================================================
// 1. PAGE CONFIGURATION AND CORE SETUP
// =================================================================
$page_key = 'categories';
$page_title = 'Manage Categories';

require_once __DIR__ . '/../includes/app.php';
require_login();

// =================================================================
// 2. HANDLE POST REQUESTS (FORM SUBMISSIONS FROM MODALS)
// =================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_flash_message('CSRF token mismatch. Action aborted.', 'danger');
        header('Location: /admin/categories.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add' || $action === 'edit') {
            // Your existing, well-written logic for add/edit.
            // No changes needed here, just ensuring flash messages are used.
            $name = trim($_POST['name']);
            if (empty($name)) throw new Exception("Category name cannot be empty.");

            $params = [
                'name' => $name,
                'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
                'is_published' => isset($_POST['is_published']) ? 1 : 0,
                'is_featured' => isset($_POST['is_featured']) ? 1 : 0
            ];

            $slug = slugify($params['name']);
            $original_slug = $slug;
            $counter = 1;

            while (true) {
                $check_sql = "SELECT id FROM categories WHERE slug = :slug";
                $check_params = ['slug' => $slug];
                if ($action === 'edit') {
                    $check_sql .= " AND id != :id";
                    $check_params['id'] = (int)$_POST['id'];
                }
                $stmt = $pdo->prepare($check_sql);
                $stmt->execute($check_params);
                if (!$stmt->fetch()) break;
                $slug = $original_slug . '-' . $counter++;
            }
            $params['slug'] = $slug;

            if ($action === 'add') {
                $sql = "INSERT INTO categories (name, slug, parent_id, is_published, is_featured) VALUES (:name, :slug, :parent_id, :is_published, :is_featured)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                set_flash_message("Category '" . e($name) . "' added successfully.", 'success');
            } else { // 'edit'
                $id = (int)$_POST['id'];
                if ($params['parent_id'] !== null && $id === (int)$params['parent_id']) {
                    throw new Exception("A category cannot be its own parent.");
                }
                
                $params['id'] = $id;
                $sql = "UPDATE categories SET name=:name, slug=:slug, parent_id=:parent_id, is_published=:is_published, is_featured=:is_featured WHERE id=:id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                set_flash_message('Category updated successfully.', 'success');
            }
        } elseif ($action === 'delete') {
            // Your existing delete logic
            $id = (int)$_POST['id'];
            if ($id > 0) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
                $stmt->execute([$id]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception("Cannot delete. This category has sub-categories.");
                }
                $pdo->prepare("UPDATE products SET category_id = NULL WHERE category_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
                set_flash_message('Category deleted successfully.', 'success');
            }
        }
    } catch (Exception $e) {
        set_flash_message('Operation failed: ' . $e->getMessage(), 'danger');
    }

    header('Location: /admin/categories.php');
    exit;
}

// =================================================================
// 3. PREPARE AND RENDER THE VIEW FOR GET REQUESTS
// =================================================================

require_once 'includes/header.php';

// Fetch all categories for the main list
$stmt = $pdo->query("SELECT c1.*, c2.name as parent_name FROM categories c1 LEFT JOIN categories c2 ON c1.parent_id = c2.id ORDER BY c1.name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch only top-level categories for the parent dropdown in modals
$parent_categories = $pdo->query("SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Page Header -->
<div class="page-header mb-4">
    <div class="row align-items-center">
        <div class="col">
            <h1 class="page-title">Manage Categories</h1>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="fas fa-plus me-2"></i>Add New Category
            </button>
        </div>
    </div>
</div>

<!-- Category List Card -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Category Name</th>
                        <th>Parent</th>
                        <th>Status</th>
                        <th class="text-center">Featured</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr><td colspan="5" class="text-center p-5">No categories found. Add one to get started!</td></tr>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>
                                    <strong class="text-dark"><?php echo e($cat['name']); ?></strong>
                                    <br><small class="text-muted">/<?php echo e($cat['slug']); ?></small>
                                </td>
                                <td><?php echo e($cat['parent_name'] ?? '—'); ?></td>
                                <td>
                                    <?php echo $cat['is_published'] ? '<span class="badge bg-light-success text-success">Published</span>' : '<span class="badge bg-light-warning text-warning">Draft</span>'; ?>
                                </td>
                                <td class="text-center">
                                    <?php echo $cat['is_featured'] ? '<i class="fas fa-star text-warning"></i>' : '<i class="far fa-star text-muted"></i>'; ?>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light edit-btn" title="Edit"
                                        data-bs-toggle="modal" data-bs-target="#editCategoryModal"
                                        data-id="<?php echo e($cat['id']); ?>" data-name="<?php echo e($cat['name']); ?>"
                                        data-parent-id="<?php echo e($cat['parent_id'] ?? '0'); ?>"
                                        data-is_published="<?php echo e($cat['is_published']); ?>"
                                        data-is_featured="<?php echo e($cat['is_featured']); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light delete-btn" title="Delete" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal" data-id="<?php echo e($cat['id']); ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- All Modals (Add, Edit, Delete) -->
<!-- Add Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="/admin/categories.php" method="POST">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Add New Category</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <div class="mb-3"><label for="addName" class="form-label">Category Name</label><input type="text" class="form-control" id="addName" name="name" required></div>
                    <div class="mb-3"><label for="addParentId" class="form-label">Parent Category</label><select class="form-select" id="addParentId" name="parent_id"><option value="">None (Top-level)</option><?php foreach ($parent_categories as $p_cat): ?><option value="<?php echo e($p_cat['id']); ?>"><?php echo e($p_cat['name']); ?></option><?php endforeach; ?></select></div>
                    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" role="switch" id="addIsPublished" name="is_published" value="1" checked><label class="form-check-label" for="addIsPublished">Publish this category</label></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="addIsFeatured" name="is_featured" value="1"><label class="form-check-label" for="addIsFeatured">Feature on homepage</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Add Category</button></div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="/admin/categories.php" method="POST" id="editCategoryForm">
            <div class="modal-content">
                 <div class="modal-header"><h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                 <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editId">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <div class="mb-3"><label for="editName" class="form-label">Category Name</label><input type="text" class="form-control" id="editName" name="name" required></div>
                    <div class="mb-3"><label for="editParentId" class="form-label">Parent Category</label><select class="form-select" id="editParentId" name="parent_id"><option value="">None (Top-level)</option><?php foreach ($parent_categories as $p_cat): ?><option value="<?php echo e($p_cat['id']); ?>"><?php echo e($p_cat['name']); ?></option><?php endforeach; ?></select></div>
                    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" role="switch" id="editIsPublished" name="is_published" value="1"><label class="form-check-label" for="editIsPublished">Publish this category</label></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="editIsFeatured" name="is_featured" value="1"><label class="form-check-label" for="editIsFeatured">Feature on homepage</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save Changes</button></div>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteConfirmationModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="confirmDeleteForm" action="/admin/categories.php" method="POST">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Confirm Deletion</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">Are you sure? Products in this category will be unlinked. Sub-categories must be removed first.</div>
                <div class="modal-footer">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteId">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
// We will move the modal-related JS to a global script file for better maintenance.
// This is done by defining $page_scripts which will be echoed in footer.php
$page_scripts = "
<script>
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editCategoryModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const catId = button.dataset.id;
            document.getElementById('editId').value = catId;
            document.getElementById('editName').value = button.dataset.name;
            document.getElementById('editParentId').value = button.dataset.parentId;
            document.getElementById('editIsPublished').checked = button.dataset.is_published == '1';
            document.getElementById('editIsFeatured').checked = button.dataset.is_featured == '1';
            
            const parentSelect = document.getElementById('editParentId');
            for (let i = 0; i < parentSelect.options.length; i++) {
                parentSelect.options[i].disabled = (parentSelect.options[i].value == catId);
            }
        });
    }
    
    const deleteModal = document.getElementById('deleteConfirmationModal');
    if(deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            document.getElementById('deleteId').value = event.relatedTarget.getAttribute('data-id');
        });
    }
});
</script>
";

// Include the new, modern footer
require_once 'includes/footer.php';
?>