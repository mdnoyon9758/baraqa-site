<?php
// =================================================================
// 1. PAGE CONFIGURATION AND CORE SETUP
// =================================================================
$page_key = 'menus';
$page_title = 'Menu Management';

require_once __DIR__ . '/../includes/app.php';
require_login();

// Fetch available menus (e.g., Header Menu, Footer Menu)
$menus = $pdo->query("SELECT * FROM menus ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Determine the currently selected menu for editing, default to the first one
$current_menu_id = isset($_GET['menu_id']) ? (int)$_GET['id'] : ($menus[0]['id'] ?? 0);

// Fetch items for the currently selected menu
$menu_items = [];
if ($current_menu_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE menu_id = ? ORDER BY item_order ASC");
    $stmt->execute([$current_menu_id]);
    $menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch pages and categories to add to the menu
$pages = $pdo->query("SELECT title, slug FROM site_pages WHERE is_published = 1 ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
$categories = $pdo->query("SELECT name, slug FROM categories WHERE is_published = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// =================================================================
// 2. RENDER THE VIEW
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
            <form method="GET" action="/admin/menus.php">
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
                    <div class="accordion-body">
                        <?php foreach($pages as $page): ?>
                            <div class="form-check"><input class="form-check-input" type="checkbox" value="/page/<?php echo e($page['slug']); ?>" data-title="<?php echo e($page['title']); ?>"> <label><?php echo e($page['title']); ?></label></div>
                        <?php endforeach; ?>
                        <button class="btn btn-sm btn-secondary mt-2 add-to-menu-btn" data-type="page">Add to Menu</button>
                    </div>
                </div>
            </div>
            <!-- Categories -->
            <div class="accordion-item">
                <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-categories">Categories</button></h2>
                <div id="collapse-categories" class="accordion-collapse collapse" data-bs-parent="#add-items-accordion">
                    <div class="accordion-body">
                        <?php foreach($categories as $category): ?>
                            <div class="form-check"><input class="form-check-input" type="checkbox" value="/category/<?php echo e($category['slug']); ?>" data-title="<?php echo e($category['name']); ?>"> <label><?php echo e($category['name']); ?></label></div>
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
                        <div class="mb-2"><label>URL</label><input type="url" class="form-control" id="custom-link-url" placeholder="https://"></div>
                        <div class="mb-2"><label>Link Text</label><input type="text" class="form-control" id="custom-link-text"></div>
                        <button class="btn btn-sm btn-secondary mt-2 add-to-menu-btn" data-type="custom">Add to Menu</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Menu Structure -->
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header"><h6 class="m-0 font-weight-bold">Menu Structure: <?php echo e($menus[array_search($current_menu_id, array_column($menus, 'id'))]['name']); ?></h6></div>
            <div class="card-body">
                <p class="text-muted">Drag and drop menu items to reorder them.</p>
                <div id="nestable-menu" class="dd">
                    <ol class="dd-list">
                        <?php
                        function build_menu_tree(array $elements, $parentId = 0) {
                            foreach ($elements as $element) {
                                if ($element['parent_id'] == $parentId) {
                                    echo '<li class="dd-item" data-id="' . e($element['id']) . '" data-title="' . e($element['title']) . '" data-url="' . e($element['url']) . '">';
                                    echo '<div class="dd-handle">' . e($element['title']) . '</div>';
                                    echo '<div class="dd-actions"><button class="btn btn-sm btn-light edit-item-btn"><i class="fas fa-edit"></i></button><button class="btn btn-sm btn-light text-danger remove-item-btn"><i class="fas fa-trash"></i></button></div>';
                                    
                                    // Recursive call for children
                                    build_menu_tree($elements, $element['id']);
                                    
                                    echo '</li>';
                                }
                            }
                        }
                        build_menu_tree($menu_items);
                        ?>
                    </ol>
                </div>
            </div>
            <div class="card-footer text-end">
                <button id="save-menu-structure" class="btn btn-primary">Save Menu</button>
            </div>
        </div>
    </div>
</div>

<?php
// Page-specific scripts
$page_scripts = "
<!-- Nestable2 for drag & drop functionality -->
<link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/nestable2@1.6.0/jquery.nestable.min.css\">
<script src=\"https://code.jquery.com/jquery-3.6.0.min.js\"></script> 
<script src=\"https://cdn.jsdelivr.net/npm/nestable2@1.6.0/jquery.nestable.min.js\"></script>
<script>
$(document).ready(function() {
    // Initialize Nestable
    $('#nestable-menu').nestable({ maxDepth: 2 });

    // Add items to menu
    $('.add-to-menu-btn').on('click', function() {
        const type = $(this).data('type');
        let items = [];

        if (type === 'custom') {
            const url = $('#custom-link-url').val();
            const text = $('#custom-link-text').val();
            if (url && text) items.push({ url: url, title: text });
        } else {
            $(this).siblings('.form-check').find('input:checked').each(function() {
                items.push({ url: $(this).val(), title: $(this).data('title') });
                $(this).prop('checked', false);
            });
        }

        if (items.length > 0) {
            $.ajax({
                url: '/admin/menu_action.php',
                method: 'POST',
                data: {
                    action: 'add_items',
                    menu_id: {$current_menu_id},
                    items: items,
                    csrf_token: '" . generate_csrf_token() . "'
                },
                success: function() { location.reload(); }
            });
        }
    });

    // Save menu structure
    $('#save-menu-structure').on('click', function() {
        const structure = $('#nestable-menu').nestable('serialize');
        $.ajax({
            url: '/admin/menu_action.php',
            method: 'POST',
            data: {
                action: 'save_structure',
                menu_id: {$current_menu_id},
                structure: JSON.stringify(structure),
                csrf_token: '" . generate_csrf_token() . "'
            },
            success: function() { alert('Menu saved successfully!'); }
        });
    });

    // Remove item
    $('#nestable-menu').on('click', '.remove-item-btn', function() {
        if (confirm('Are you sure you want to remove this menu item?')) {
            const item = $(this).closest('.dd-item');
            $.ajax({
                url: '/admin/menu_action.php',
                method: 'POST',
                data: {
                    action: 'delete_item',
                    item_id: item.data('id'),
                    csrf_token: '" . generate_csrf_token() . "'
                },
                success: function() { item.remove(); }
            });
        }
    });
});
</script>
";

require_once 'includes/footer.php';
?>