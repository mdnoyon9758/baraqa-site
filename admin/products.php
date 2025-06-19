<?php
// =================================================================
// 1. PAGE CONFIGURATION AND CORE SETUP
// =================================================================

// Set the unique key and title for this page.
// The $page_key is used by the sidebar to highlight the active menu item.
$page_key = 'products';
$page_title = 'Product Management';

// Load the core application file which handles sessions, db connections, and all essential functions.
require_once __DIR__ . '/../includes/app.php';

// Ensure the user is an authenticated admin. This function will handle redirects if not logged in.
require_login();

// =================================================================
// 2. HANDLE POST REQUESTS (FORM SUBMISSIONS FOR ADD/EDIT/DELETE)
// =================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify the CSRF token to prevent cross-site request forgery attacks.
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_flash_message('CSRF token mismatch. Action aborted for security reasons.', 'danger');
        header('Location: /admin/products.php');
        exit;
    }

    $action_type = $_POST['action_type'] ?? '';

    try {
        if ($action_type === 'add' || $action_type === 'edit') {
            // Sanitize and prepare data for database insertion/update.
            $params = [
                'title' => trim($_POST['title']),
                'description' => trim($_POST['description']),
                'price' => (float)($_POST['price'] ?? 0),
                'stock_quantity' => (int)($_POST['stock_quantity'] ?? 0),
                'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
                'brand_id' => !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null,
                'image_url' => filter_var(trim($_POST['image_url']), FILTER_SANITIZE_URL),
                'is_published' => isset($_POST['is_published']) ? 1 : 0
            ];

            // Generate a unique slug. If a slug already exists, append a number.
            $slug = slugify($params['title']);
            $original_slug = $slug;
            $counter = 1;
            while (true) {
                $check_sql = "SELECT id FROM products WHERE slug = :slug";
                $check_params = ['slug' => $slug];
                if ($action_type === 'edit') {
                    $check_sql .= " AND id != :id";
                    $check_params['id'] = (int)$_POST['id'];
                }
                $stmt = $pdo->prepare($check_sql);
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
                set_flash_message('Product added successfully!', 'success');
            } else { // 'edit'
                $params['id'] = (int)$_POST['id'];
                $sql = "UPDATE products SET title=:title, slug=:slug, description=:description, price=:price, stock_quantity=:stock_quantity, category_id=:category_id, brand_id=:brand_id, image_url=:image_url, is_published=:is_published WHERE id=:id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                set_flash_message('Product updated successfully!', 'success');
            }
        } elseif ($action_type === 'delete') {
            $id_to_delete = (int)($_POST['id'] ?? 0);
            if ($id_to_delete > 0) {
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$id_to_delete]);
                set_flash_message('Product deleted successfully.', 'success');
            } else {
                set_flash_message('Invalid product ID for deletion.', 'danger');
            }
        }
    } catch (PDOException $e) {
        error_log("Product management error: " . $e->getMessage());
        set_flash_message('A database error occurred. Please try again or check the system logs.', 'danger');
    }
    
    // Redirect back to the product list to prevent form resubmission.
    header('Location: /admin/products.php');
    exit;
}

// =================================================================
// 3. PREPARE AND RENDER THE VIEW FOR GET REQUESTS
// =================================================================

// Include the new, modern header and sidebar
require_once 'includes/header.php';

// Determine which view to show: the list of products or the add/edit form.
$action = $_GET['action'] ?? 'list';

if ($action === 'add' || $action === 'edit'):
    // --- RENDER ADD/EDIT FORM VIEW ---
    $product_data = null;
    $form_action_type = 'add';
    if ($action === 'edit' && isset($_GET['id'])) {
        $product_id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product_data = $stmt->fetch();
        if (!$product_data) {
            set_flash_message('Product not found.', 'danger');
            header('Location: /admin/products.php');
            exit;
        }
        $form_action_type = 'edit';
    }
    // Fetch categories and brands for the dropdowns
    $categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
    $brands = $pdo->query("SELECT id, name FROM brands ORDER BY name ASC")->fetchAll();
?>
    <!-- Page Header with Title and Breadcrumbs -->
    <div class="page-header mb-4">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <h1 class="page-title"><?php echo $form_action_type === 'add' ? 'Add New Product' : 'Edit Product'; ?></h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/admin/dashboard.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="/admin/products.php">Products</a></li>
                        <li class="breadcrumb-item active"><?php echo $form_action_type === 'add' ? 'Add New' : 'Edit'; ?></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <!-- Form is placed inside a clean card -->
    <div class="card shadow-sm">
        <div class="card-body">
            <form action="/admin/products.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="action_type" value="<?php echo $form_action_type; ?>">
                <?php if ($form_action_type === 'edit'): ?><input type="hidden" name="id" value="<?php echo e($product_data['id']); ?>"><?php endif; ?>
                
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
                                <option value="<?php echo e($cat['id']); ?>" <?php echo (isset($product_data) && $product_data['category_id'] == $cat['id']) ? 'selected' : ''; ?>><?php echo e($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="brand_id" class="form-label">Brand</label>
                        <select class="form-select" id="brand_id" name="brand_id">
                            <option value="">Select a brand</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo e($brand['id']); ?>" <?php echo (isset($product_data) && $product_data['brand_id'] == $brand['id']) ? 'selected' : ''; ?>><?php echo e($brand['name']); ?></option>
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
                <a href="/admin/products.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>

<?php else: ?>
    <?php
    // --- RENDER PRODUCT LIST VIEW ---
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 15;
    $offset = ($page - 1) * $limit;
    
    // Fetch data for the list view
    $total_products = $pdo->query("SELECT COUNT(id) FROM products")->fetchColumn();
    $total_pages = ceil($total_products / $limit);
    
    $stmt = $pdo->prepare("SELECT p.id, p.title, p.slug, p.price, p.stock_quantity, p.is_published, p.image_url, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll();
    ?>
    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-title">All Products <span class="text-muted">(<?php echo $total_products; ?>)</span></h1>
            </div>
            <div class="col-auto">
                <a href="/admin/products.php?action=add" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Add New Product</a>
            </div>
        </div>
    </div>
    
    <!-- Product List Card -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-nowrap align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Stock</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($products): foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo e($product['image_url'] ? $product['image_url'] : 'https://via.placeholder.com/60'); ?>" alt="<?php echo e($product['title']); ?>" width="60" height="60" class="rounded object-fit-cover">
                                </td>
                                <td>
                                    <a href="/product/<?php echo e($product['slug']); ?>" target="_blank" class="text-dark fw-bold text-decoration-none">
                                        <?php echo e(mb_strimwidth($product['title'], 0, 45, "...")); ?>
                                    </a>
                                </td>
                                <td><?php echo e($product['category_name'] ?? 'N/A'); ?></td>
                                <td><?php echo e($product['stock_quantity']); ?></td>
                                <td>$<?php echo e(number_format($product['price'], 2)); ?></td>
                                <td>
                                    <?php if ($product['is_published']): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success">Published</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning">Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-h"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="/admin/products.php?action=edit&id=<?php echo e($product['id']); ?>">Edit</a></li>
                                            <li><a class="dropdown-item" href="/product/<?php echo e($product['slug']); ?>" target="_blank">View on Site</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button class="dropdown-item text-danger delete-btn" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal" data-id="<?php echo e($product['id']); ?>">
                                                    Delete
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="7" class="text-center p-5">No products found. <a href="/admin/products.php?action=add">Add your first product!</a></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($total_pages > 1): ?>
            <div class="card-footer">
                <nav class="d-flex justify-content-center">
                    <ul class="pagination mb-0">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Confirm Deletion</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">Are you sure you want to delete this product? This action cannot be undone.</div>
                <div class="modal-footer">
                    <form action="/admin/products.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action_type" value="delete">
                        <input type="hidden" name="id" id="deleteId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Product</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
// Include the new, modern footer
require_once 'includes/footer.php';
?>