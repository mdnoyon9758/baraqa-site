<?php
// =================================================================
// 1. PAGE CONFIGURATION AND CORE SETUP
// =================================================================
$page_key = 'pages';
$page_title = 'Manage Pages';

require_once __DIR__ . '/../includes/app.php';
require_login();

// =================================================================
// 2. HANDLE POST REQUESTS (Add/Edit/Delete)
// =================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_flash_message('CSRF token mismatch. Action aborted.', 'danger');
        header('Location: /admin/pages.php');
        exit;
    }
    
    $action_type = $_POST['action_type'] ?? '';

    try {
        $pdo->beginTransaction();

        if ($action_type === 'add' || $action_type === 'edit') {
            $params = [
                'title' => trim($_POST['title']),
                'content' => $_POST['content'], // Note: Content should be purified if it's from a WYSIWYG editor
                'menu_order' => (int)$_POST['menu_order'],
                'is_published' => isset($_POST['is_published']) ? 1 : 0
            ];
            if (empty($params['title'])) throw new Exception("Page title cannot be empty.");
            
            // Your existing slug generation logic is excellent
            $slug = slugify($params['title']);
            $original_slug = $slug; $counter = 1;
            while (true) {
                $check_sql = "SELECT id FROM site_pages WHERE slug = :slug";
                $check_params = ['slug' => $slug];
                if ($action_type === 'edit') { $check_sql .= " AND id != :id"; $check_params['id'] = (int)$_POST['id']; }
                $stmt = $pdo->prepare($check_sql);
                $stmt->execute($check_params);
                if (!$stmt->fetch()) break;
                $slug = $original_slug . '-' . $counter++;
            }
            $params['slug'] = $slug;

            if ($action_type === 'add') {
                $sql = "INSERT INTO site_pages (title, slug, content, menu_order, is_published) VALUES (:title, :slug, :content, :menu_order, :is_published)";
            } else {
                $params['id'] = (int)$_POST['id'];
                $sql = "UPDATE site_pages SET title=:title, slug=:slug, content=:content, menu_order=:menu_order, is_published=:is_published WHERE id=:id";
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            set_flash_message($action_type === 'add' ? 'Page created successfully!' : 'Page updated successfully!', 'success');
        } elseif ($action_type === 'delete') {
            $id_to_delete = (int)$_POST['id'];
            if ($id_to_delete > 0) {
                $stmt = $pdo->prepare("DELETE FROM site_pages WHERE id = ?");
                $stmt->execute([$id_to_delete]);
                set_flash_message('Page deleted successfully.', 'success');
            } else { throw new Exception("Invalid ID for deletion."); }
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        set_flash_message('Operation failed: ' . $e->getMessage(), 'danger');
    }
    
    header('Location: /admin/pages.php');
    exit;
}

// =================================================================
// 3. PREPARE AND RENDER THE VIEW
// =================================================================
require_once 'includes/header.php';

$action = $_GET['action'] ?? 'list';
$page_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action === 'add' || $action === 'edit'):
    // --- ADD/EDIT FORM VIEW ---
    $page_data = null; $form_action = 'add';
    if ($action === 'edit' && $page_id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM site_pages WHERE id = ?"); $stmt->execute([$page_id]);
        $page_data = $stmt->fetch();
        if (!$page_data) { 
            set_flash_message('Page not found.', 'danger'); 
            header('Location: /admin/pages.php'); 
            exit; 
        }
        $form_action = 'edit';
    }
?>
    <!-- Page Header -->
    <div class="page-header mb-4">
        <h1 class="page-title"><?php echo $form_action === 'add' ? 'Create New Page' : 'Edit Page'; ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/admin/dashboard.php">Home</a></li>
                <li class="breadcrumb-item"><a href="/admin/pages.php">Pages</a></li>
                <li class="breadcrumb-item active"><?php echo $form_action === 'add' ? 'Create' : 'Edit'; ?></li>
            </ol>
        </nav>
    </div>

    <!-- Page Form Card -->
    <div class="card shadow-sm">
        <div class="card-body">
            <form action="/admin/pages.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="action_type" value="<?php echo $form_action; ?>">
                <?php if ($form_action === 'edit'): ?><input type="hidden" name="id" value="<?php echo e($page_data['id']); ?>"><?php endif; ?>
                
                <div class="mb-3">
                    <label for="title" class="form-label">Page Title</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo e($page_data['title'] ?? ''); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="content-editor" class="form-label">Content</label>
                    <textarea class="form-control" id="content-editor" name="content" rows="15"><?php echo htmlspecialchars($page_data['content'] ?? ''); ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="menu_order" class="form-label">Menu Order</label>
                        <input type="number" class="form-control" id="menu_order" name="menu_order" value="<?php echo e($page_data['menu_order'] ?? '0'); ?>">
                        <small class="text-muted">A lower number will appear first in menus.</small>
                    </div>
                    <div class="col-md-6 mb-3 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_published" name="is_published" value="1" <?php echo (!isset($page_data) || $page_data['is_published'] == 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_published">Publish this page</label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Save Page</button>
                <a href="/admin/pages.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
<?php else: // --- LIST VIEW ---
    $pages = $pdo->query("SELECT * FROM site_pages ORDER BY menu_order ASC, title ASC")->fetchAll();
?>
    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="row align-items-center">
            <div class="col"><h1 class="page-title">All Pages</h1></div>
            <div class="col-auto"><a href="/admin/pages.php?action=add" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Create New Page</a></div>
        </div>
    </div>

    <!-- Pages List Card -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Title</th>
                            <th>URL Slug</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pages)): ?>
                            <tr><td colspan="4" class="text-center p-5">No pages created yet.</td></tr>
                        <?php else: foreach ($pages as $page): ?>
                            <tr>
                                <td><strong class="text-dark"><?php echo e($page['title']); ?></strong></td>
                                <td><a href="/page/<?php echo e($page['slug']); ?>" target="_blank" class="text-muted">/page/<?php echo e($page['slug']); ?></a></td>
                                <td class="text-center"><?php echo $page['is_published'] ? '<span class="badge bg-light-success text-success">Published</span>' : '<span class="badge bg-light-warning text-warning">Draft</span>'; ?></td>
                                <td class="text-end">
                                    <a href="/admin/pages.php?action=edit&id=<?php echo e($page['id']); ?>" class="btn btn-sm btn-light" title="Edit"><i class="fas fa-edit"></i></a>
                                    <button class="btn btn-sm btn-light delete-btn" title="Delete" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal" data-id="<?php echo e($page['id']); ?>"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="/admin/pages.php" method="POST">
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">Confirm Deletion</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">Are you sure you want to delete this page? This action cannot be undone.</div>
                    <div class="modal-footer">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action_type" value="delete">
                        <input type="hidden" name="id" id="deleteId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Page</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
// Define page-specific scripts for this page
$page_scripts = "
<!-- TinyMCE WYSIWYG Editor -->
<script src=\"https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js\"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize TinyMCE
    tinymce.init({
        selector: '#content-editor',
        plugins: 'code table lists image link media',
        toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | indent outdent | bullist numlist | code | table | image media link',
        height: 400,
        menubar: false
    });

    // Logic for Delete Modal (reusing your existing logic)
    const deleteModal = document.getElementById('deleteConfirmationModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            document.getElementById('deleteId').value = event.relatedTarget.dataset.id;
        });
    }
});
</script>
";

require_once 'includes/footer.php';
?>