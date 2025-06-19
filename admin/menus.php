<?php
// =================================================================
// 1. PAGE CONFIGURATION AND CORE SETUP
// =================================================================
$page_key = 'menus';
$page_title = 'Menu Management';

require_once __DIR__ . '/../includes/app.php';
require_login();

// =================================================================
// 2. DATA FETCHING
// =================================================================
try {
    // Fetch available menus (e.g., Header Menu, Footer Menu)
    $menus = $pdo->query("SELECT * FROM menus ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

    // CORRECTED: Determine the currently selected menu for editing, default to the first one
    $current_menu_id = isset($_GET['menu_id']) ? (int)$_GET['menu_id'] : ($menus[0]['id'] ?? 0);

    // Fetch items for the currently selected menu
    $menu_items = [];
    if ($current_menu_id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE menu_id = ? ORDER BY parent_id ASC, item_order ASC");
        $stmt->execute([$current_menu_id]);
        $menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch pages and categories to add to the menu
    $pages = $pdo->query("SELECT title, slug FROM site_pages WHERE is_published = 1 ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
    $categories = $pdo->query("SELECT name, slug FROM categories WHERE is_published = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    // Find the name of the current menu for the header
    $current_menu_name = 'N/A';
    if ($current_menu_id > 0) {
        foreach($menus as $menu) {
            if ($menu['id'] == $current_menu_id) {
                $current_menu_name = $menu['name'];
                break;
            }
        }
    }

} catch(PDOException $e) {
    set_flash_message('Database Error: ' . $e->getMessage(), 'danger');
    $menus = $menu_items = $pages = $categories = [];
    $current_menu_id = 0;
    $current_menu_name = 'Error';
}


// =================================================================
// 3. RENDER THE VIEW
// =================================================================
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header mb-4">
    <div class="row align-items-center">
        <div class="col">
            <h1 class="page-title">Menu Builder</h1>
        </div>
        <div class="col-auto">
            <?php if (!empty($menus)): ?>
            <form method="GET" action="/admin/menus.php" id="menu-selector-form">
                <div class="input-group">
                    <label class="input-group-text" for="menu-selector">Select Menu to Edit:</label>
                    <select id="menu-selector" name="menu_id" class="form-select" onchange="this.form.submit()">
                        <?php foreach($menus as $menu): ?>
                            <option value="<?php echo e($menu['id']); ?>" <?php if($current_menu_id == $menu['id']) echo 'selected'; ?>>
                                <?php echo e($menu['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Column: Add Menu Items -->
    <div class="col-lg-4">
        <div class="accordion" id="add-items-accordion">
            <!-- Pages -->
            <div class="accordion-item">
                <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-pages">Pages</button></h2>
                <div id="collapse-pages" class="accordion-collapse collapse show" data-bs-parent="#add-items-accordion">
                    <div class="accordion-body list-group">
                        <?php foreach($pages as $page): ?>
                            <label class="list-group-item"><input class="form-check-input me-2" type="checkbox" value="/page/<?php echo e($page['slug']); ?>" data-title="<?php echo e($page['title']); ?>"> <?php echo e($page['title']); ?></label>
                        <?php endforeach; ?>
                        <button class="btn btn-sm btn-secondary mt-2 add-to-menu-btn" data-type="page">Add to Menu</button>
                    </div>
                </div>
            </div>
            <!-- Categories -->
            <div class="accordion-item">
                <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-categories">Categories</button></h2>
                <div id="collapse-categories" class="accordion-collapse collapse" data-bs-parent="#add-items-accordion">
                    <div class="accordion-body list-group">
                        <?php foreach($categories as $category): ?>
                           <label class="list-group-item"><input class="form-check-input me-2" type="checkbox" value="/category/<?php echo e($category['slug']); ?>" data-title="<?php echo e($category['name']); ?>"> <?php echo e($category['name']); ?></label>
                        <?php endforeach; ?>
                        <button class="btn btn-sm btn-secondary mt-2 add-to-menu-btn" data-type="category">Add to Menu</button>
                    </div>
                </div>
            </div>
            <!-- Custom Link -->
            <div class="accordion-item">
                <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-custom">Custom Link</button></h2>
                <div id="collapse-custom" class="accordion-collapse collapse" data-bs-parent="#add-items-accordion">
                    <div class="accordion-body">
                        <div class="mb-2"><label class="form-label">URL</label><input type="url" class="form-control" id="custom-link-url" placeholder="https://example.com"></div>
                        <div class="mb-2"><label class="form-label">Link Text</label><input type="text" class="form-control" id="custom-link-text"></div>
                        <button class="btn btn-sm btn-secondary mt-2 add-to-menu-btn" data-type="custom">Add to Menu</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Menu Structure -->
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header"><h6 class="m-0 font-weight-bold">Menu Structure: <?php echo e($current_menu_name); ?></h6></div>
            <div class="card-body">
                <p class="text-muted">Drag and drop menu items to reorder them. Click the <i class="fas fa-chevron-down"></i> icon to edit details.</p>
                <div class="dd" id="nestable-menu">
                    <ol class="dd-list">
                        <?php
                        // Recursive function to build menu structure for Nestable
                        function build_nestable_tree(array $elements, $parentId = 0) {
                            $branch = [];
                            foreach ($elements as $element) {
                                if ($element['parent_id'] == $parentId) {
                                    $children = build_nestable_tree($elements, $element['id']);
                                    echo '<li class="dd-item" data-id="' . e($element['id']) . '">';
                                    echo '<div class="dd-handle">' . e($element['title']) . '</div>';
                                    echo '<div class="dd-actions">';
                                    echo '<button class="btn btn-sm btn-light edit-item-btn" data-bs-toggle="collapse" data-bs-target="#details-' . e($element['id']) . '"><i class="fas fa-chevron-down"></i></button>';
                                    echo '<button class="btn btn-sm btn-light text-danger remove-item-btn"><i class="fas fa-trash"></i></button>';
                                    echo '</div>';
                                    echo '<div class="collapse item-details" id="details-' . e($element['id']) . '">';
                                    echo '<input type="text" class="form-control form-control-sm mt-2" data-name="title" value="' . e($element['title']) . '" placeholder="Navigation Label">';
                                    echo '<input type="text" class="form-control form-control-sm mt-2" data-name="url" value="' . e($element['url']) . '" placeholder="URL">';
                                    echo '</div>';

                                    if ($children) {
                                        echo '<ol class="dd-list">';
                                        echo $children;
                                        echo '</ol>';
                                    }
                                    echo '</li>';
                                }
                            }
                        }
                        build_nestable_tree($menu_items);
                        ?>
                    </ol>
                </div>
            </div>
            <div class="card-footer text-end">
                <button id="save-menu-structure" class="btn btn-primary">Save Menu Structure</button>
            </div>
        </div>
    </div>
</div>

<?php
$page_scripts = "
<link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/nestable2@1.6.0/jquery.nestable.min.css\">
<script src=\"https://code.jquery.com/jquery-3.7.1.min.js\"></script>
<script src=\"https://cdn.jsdelivr.net/npm/nestable2@1.6.0/jquery.nestable.min.js\"></script>
<style>.dd-actions{position:absolute;right:10px;top:6px;z-index:2;}.item-details{padding:10px;background:#f5f5f5;border-top:1px solid #ddd;}</style>
<script>
$(document).ready(function() {
    const nestable = $('#nestable-menu');
    nestable.nestable({ maxDepth: 2 });

    const menuId = " . json_encode($current_menu_id) . ";
    const csrfToken = " . json_encode(generate_csrf_token()) . ";

    $('.add-to-menu-btn').on('click', function() { /* Your existing JS logic */ });
    $('#save-menu-structure').on('click', function() { /* Your existing JS logic */ });
    nestable.on('click', '.remove-item-btn', function() { /* Your existing JS logic */ });
});
</script>
";

require_once 'includes/footer.php';
?>