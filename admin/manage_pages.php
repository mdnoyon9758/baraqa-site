<?php
// --- 1. ALL PHP LOGIC GOES HERE ---
$page_title = "Manage Pages";
require_once 'includes/auth.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'CSRF token mismatch. Action aborted.';
        header('Location: manage_pages.php');
        exit;
    }
    
    $action_type = $_POST['action_type'] ?? '';

    try {
        $pdo->beginTransaction();

        if ($action_type === 'add' || $action_type === 'edit') {
            $params = ['title' => trim($_POST['title']),'content' => $_POST['content'],'menu_order' => (int)$_POST['menu_order'],'is_published' => isset($_POST['is_published']) ? 1 : 0];
            if (empty($params['title'])) throw new Exception("Page title cannot be empty.");
            
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

            if ($action_type === 'add') {
                $_SESSION['success_message'] = 'Page created successfully!';
                log_admin_activity("Created page: " . e($params['title']), 'page', $pdo->lastInsertId());
            } else {
                $_SESSION['success_message'] = 'Page updated successfully!';
                log_admin_activity("Updated page: " . e($params['title']), 'page', $params['id']);
            }
        } elseif ($action_type === 'delete') {
            $id_to_delete = (int)$_POST['id'];
            if ($id_to_delete > 0) {
                $stmt = $pdo->prepare("DELETE FROM site_pages WHERE id = :id");
                $stmt->execute(['id' => $id_to_delete]);
                $_SESSION['success_message'] = 'Page deleted successfully.';
                log_admin_activity("Deleted page ID: " . $id_to_delete, 'page', $id_to_delete);
            } else { throw new Exception("Invalid ID for deletion."); }
        }
        
        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = 'Operation failed: ' . $e->getMessage();
    }
    
    header('Location: manage_pages.php');
    exit;
}

$action = $_GET['action'] ?? 'list';
$page_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// --- START: HTML OUTPUT ---
require_once 'includes/admin_header.php';
?>


<?php if ($action === 'add' || $action === 'edit'):
    // --- ADD/EDIT FORM VIEW ---
    $page_data = null; $form_action = 'add';
    if ($action === 'edit' && $page_id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM site_pages WHERE id = ?"); $stmt->execute([$page_id]);
        $page_data = $stmt->fetch();
        if (!$page_data) { $_SESSION['error_message'] = 'Page not found.'; header('Location: manage_pages.php'); exit; }
        $form_action = 'edit';
    }
?>
    <div class="d-flex justify-content-between align-items-center mb-4"><h1 class="h3 mb-0 text-gray-800"><?php echo $form_action === 'add' ? 'Create New Page' : 'Edit Page'; ?></h1></div>
    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="manage_pages.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                <input type="hidden" name="action_type" value="<?php echo $form_action; ?>">
                <?php if ($form_action === 'edit'): ?><input type="hidden" name="id" value="<?php echo e($page_data['id']); ?>"><?php endif; ?>
                <div class="mb-3"><label for="title" class="form-label">Page Title</label><input type="text" class="form-control" id="title" name="title" value="<?php echo e($page_data['title'] ?? ''); ?>" required></div>
                <div class="mb-3"><label for="content" class="form-label">Content (Raw HTML)</label><textarea class="form-control" id="content" name="content" rows="15" style="font-family: monospace;"><?php echo htmlspecialchars($page_data['content'] ?? ''); ?></textarea></div>
                <div class="row"><div class="col-md-6 mb-3"><label for="menu_order" class="form-label">Menu Order</label><input type="number" class="form-control" id="menu_order" name="menu_order" value="<?php echo e($page_data['menu_order'] ?? '0'); ?>"></div><div class="col-md-6 mb-3 d-flex align-items-end"><div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" role="switch" id="is_published" name="is_published" value="1" <?php echo (!isset($page_data) || $page_data['is_published'] == 1) ? 'checked' : ''; ?>><label class="form-check-label" for="is_published">Publish page</label></div></div></div>
                <button type="submit" class="btn btn-primary">Save Page</button>
                <a href="manage_pages.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
<?php else: // LIST VIEW
    $pages = $pdo->query("SELECT * FROM site_pages ORDER BY menu_order ASC, title ASC")->fetchAll();
?>
    <div class="d-flex justify-content-between align-items-center mb-4"><h1 class="h3 mb-0 text-gray-800">All Pages</h1><a href="manage_pages.php?action=add" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Create New Page</a></div>
    <div class="card shadow mb-4"><div class="card-body"><div class="table-responsive"><table class="table table-bordered table-hover" width="100%"><thead><tr><th>Title</th><th>URL</th><th class="text-center">Status</th><th class="text-center">Actions</th></tr></thead><tbody>
        <?php if (empty($pages)): ?>
            <tr><td colspan="4" class="text-center">No pages created yet.</td></tr>
        <?php else: foreach ($pages as $page): ?>
            <tr>
            <td><?php echo e($page['title']); ?></td>
            <td><a href="../page/<?php echo e($page['slug']); ?>" target="_blank">/page/<?php echo e($page['slug']); ?></a></td>
            <td class="text-center"><?php echo $page['is_published'] ? '<span class="badge bg-success">Published</span>' : '<span class="badge bg-warning text-dark">Draft</span>'; ?></td>
            <td><a href="manage_pages.php?action=edit&id=<?php echo e($page['id']); ?>" class="btn btn-sm btn-info" title="Edit"><i class="fas fa-edit"></i></a><button class="btn btn-sm btn-danger delete-btn" title="Delete" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal" data-id="<?php echo e($page['id']); ?>"><i class="fas fa-trash"></i></button></td>
            </tr>
        <?php endforeach; endif; ?>
    </tbody></table></div></div></div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Confirm Deletion</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">Are you sure you want to delete this page? This action cannot be undone.</div>
                <div class="modal-footer">
                    <form id="deleteForm" action="manage_pages.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
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

<?php require_once 'includes/admin_footer.php'; ?>