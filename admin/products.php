<?php
// --- 1. ALL PHP LOGIC GOES HERE ---
// This part handles the form submission and redirects BEFORE any HTML is sent.

$page_title = "Manage Products";
require_once 'includes/auth.php'; // Authentication and session start

// --- Handle POST requests for Add/Edit/Delete ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'CSRF token mismatch. Action aborted.';
        header('Location: products.php');
        exit;
    }

    $action_type = $_POST['action_type'] ?? '';

    try {
        // --- Add/Edit Product Logic ---
        if ($action_type === 'add' || $action_type === 'edit') {
            $params = [
                'title' => trim($_POST['title']),
                'description' => trim($_POST['description']),
                'price' => (float)$_POST['price'],
                'stock_quantity' => (int)$_POST['stock_quantity'],
                'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
                'brand_id' => !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null,
                'image_url' => trim($_POST['image_url']),
                'is_published' => isset($_POST['is_published']) ? 1 : 0
            ];

            // Generate slug from title
            $slug = slugify($params['title']);
            $original_slug = $slug;
            $counter = 1;

            // Ensure slug is unique
            while(true) {
                $check_sql = "SELECT id FROM products WHERE slug = :slug";
                $check_params = ['slug' => $slug];
                if ($action_type === 'edit') {
                    $check_sql .= " AND id != :id";
                    $check_params['id'] = (int)$_POST['id'];
                }
                $stmt = $pdo->prepare($check_slug_sql);
                $stmt->execute($check_params);
                if (!$stmt->fetch()) {
                    break; 
                }
                $slug = $original_slug . '-' . $counter++;
            }
            $params['slug'] = $slug;


            if ($action_type === 'add') {
                $params['is_manual'] = 1; // Mark as manually added
                $sql = "INSERT INTO products (title, slug, description, price, stock_quantity, category_id, brand_id, image_url, is_published, is_manual) VALUES (:title, :slug, :description, :price, :stock_quantity, :category_id, :brand_id, :image_url, :is_published, :is_manual)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $_SESSION['success_message'] = 'Product added successfully!';
            } else { // 'edit'
                $params['id'] = (int)$_POST['id'];
                $sql = "UPDATE products SET title=:title, slug=:slug, description=:description, price=:price, stock_quantity=:stock_quantity, category_id=:category_id, brand_id=:brand_id, image_url=:image_url, is_published=:is_published WHERE id=:id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $_SESSION['success_message'] = 'Product updated successfully!';
            }
        }
        // --- Delete Product Logic ---
        elseif ($action_type === 'delete') {
            $id_to_delete = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
            $stmt->execute(['id' => $id_to_delete]);
            $_SESSION['success_message'] = 'Product deleted successfully.';
        }

    } catch (PDOException $e) {
        // Log the detailed error for the admin, show a generic message to the user.
        error_log("Product management error: " . $e->getMessage());
        $_SESSION['error_message'] = 'A database error occurred. Please try again.';
    }
    
    // Redirect after processing the POST request to prevent re-submission on refresh
    header('Location: products.php');
    exit;
}


// --- 2. START HTML OUTPUT AND DISPLAY LOGIC (for GET requests) ---
// Now that all processing and potential redirects are done, we can safely include the header.
require_once 'includes/admin_header.php';

$action = $_GET['action'] ?? 'list';
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action === 'add' || $action === 'edit'):
    // --- ADD/EDIT FORM VIEW ---
    $product_data = null;
    $form_action = 'add';
    if ($action === 'edit' && $product_id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product_data = $stmt->fetch();
        if (!$product_data) {
            $_SESSION['error_message'] = 'Product not found.';
            // Use a meta refresh or JS redirect since headers are already sent
            echo '<meta http-equiv="refresh" content="0;url=products.php">';
            exit;
        }
        $form_action = 'edit';
    }

    // Fetch categories and brands for dropdowns
    $categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
    $brands = $pdo->query("SELECT id, name FROM brands ORDER BY name ASC")->fetchAll();
?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?php echo $form_action === 'add' ? 'Add New Product' : 'Edit Product'; ?></h1>
        <a href="products.php" class="btn btn-secondary">Back to List</a>
    </div>
    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="products.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                <input type="hidden" name="action_type" value="<?php echo $form_action; ?>">
                <?php if ($form_action === 'edit'): ?>
                    <input type="hidden" name="id" value="<?php echo e($product_data['id']); ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label for="title" class="form-label">Product Title</label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo e($product_data['title'] ?? ''); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="5"><?php echo e($product_data['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="price" class="form-label">Price ($)</label>
                        <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo e($product_data['price'] ?? '0.00'); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="stock_quantity" class="form-label">Stock Quantity</label>
                        <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" value="<?php echo e($product_data['stock_quantity'] ?? '100'); ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select class="form-select" id="category_id" name="category_id">
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo e($cat['id']); ?>" <?php echo (isset($product_data) && $product_data['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo e($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                     <div class="col-md-6 mb-3">
                        <label for="brand_id" class="form-label">Brand</label>
                        <select class="form-select" id="brand_id" name="brand_id">
                            <option value="">Select a brand</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo e($brand['id']); ?>" <?php echo (isset($product_data) && $product_data['brand_id'] == $brand['id']) ? 'selected' : ''; ?>>
                                    <?php echo e($brand['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="image_url" class="form-label">Main Image URL</label>
                    <input type="url" class="form-control" id="image_url" name="image_url" value="<?php echo e($product_data['image_url'] ?? ''); ?>" placeholder="https://example.com/image.jpg">
                </div>

                <div class="form-check form-switch mb-3">
                     <input class="form-check-input" type="checkbox" role="switch" id="is_published" name="is_published" value="1" <?php echo (!isset($product_data) || $product_data['is_published'] == 1) ? 'checked' : ''; ?>>
                     <label class="form-check-label" for="is_published">Publish this product</label>
                </div>

                <button type="submit" class="btn btn-primary">Save Product</button>
                <a href="products.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
<?php else: // LIST VIEW ?>
    <?php
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 15;
        $offset = ($page - 1) * $limit;
        
        $total_products = $pdo->query("SELECT COUNT(id) FROM products")->fetchColumn();
        $total_pages = ceil($total_products / $limit);
        
        $stmt = $pdo->prepare("SELECT p.id, p.title, p.slug, p.price, p.stock_quantity, p.is_published, p.is_manual, c.name as category_name, b.name as brand_name FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN brands b ON p.brand_id = b.id ORDER BY p.id DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll();
    ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">All Products (<?php echo $total_products; ?>)</h1>
        <a href="products.php?action=add" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Add New Product</a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" width="100%">
                    <thead>
                        <tr><th>ID</th><th>Title</th><th>Category</th><th>Brand</th><th>Price</th><th>Stock</th><th>Status</th><th>Type</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php if ($products): foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo e($product['id']); ?></td>
                                <td><a href="../product/<?php echo e($product['slug']); ?>" target="_blank"><?php echo e(substr($product['title'], 0, 40)); ?>...</a></td>
                                <td><?php echo e($product['category_name'] ?? 'N/A'); ?></td>
                                <td><?php echo e($product['brand_name'] ?? 'N/A'); ?></td>
                                <td>$<?php echo e(number_format($product['price'], 2)); ?></td>
                                <td><?php echo e($product['stock_quantity']); ?></td>
                                <td><?php echo $product['is_published'] ? '<span class="badge bg-success">Published</span>' : '<span class="badge bg-warning text-dark">Draft</span>'; ?></td>
                                <td><?php echo $product['is_manual'] ? '<span class="badge bg-primary">Manual</span>' : '<span class="badge bg-info text-dark">API</span>'; ?></td>
                                <td>
                                    <a href="products.php?action=edit&id=<?php echo e($product['id']); ?>" class="btn btn-sm btn-info" title="Edit"><i class="fas fa-edit"></i></a>
                                    <button class="btn btn-sm btn-danger delete-btn" title="Delete" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal" data-id="<?php echo e($product['id']); ?>"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="9" class="text-center">No products found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
             <!-- Pagination -->
            <nav class="mt-4 d-flex justify-content-center">
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                    <?php endfor; ?>
                </ul>
            </nav>
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
                    Are you sure you want to delete this product? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <form action="products.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                        <input type="hidden" name="action_type" value="delete">
                        <input type="hidden" name="id" id="deleteId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Product</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const deleteModal = document.getElementById('deleteConfirmationModal');
        if (deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const deleteIdInput = document.getElementById('deleteId');
                deleteIdInput.value = id;
            });
        }
    });
    </script>
<?php endif; ?>

<?php require_once 'includes/admin_footer.php'; ?>