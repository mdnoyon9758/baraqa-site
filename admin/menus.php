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
    $menus = $pdo->query("SELECT * FROM menus ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    // Set a default current menu ID, only if menus exist
    $current_menu_id = $_GET['menu_id'] ?? ($menus[0]['id'] ?? 0);
    $current_menu_id = (int)$current_menu_id;

    // Fetch items for the currently selected menu
    $menu_items = [];
    if ($current_menu_id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE menu_id = ? ORDER BY parent_id ASC, item_order ASC");
        $stmt->execute([$current_menu_id]);
        $menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $pages = $pdo->query("SELECT title, slug FROM site_pages WHERE is_published = 1 ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
    $categories = $pdo->query("SELECT name, slug FROM categories WHERE is_published = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

    // CORRECTED: Safely find the name of the current menu
    $current_menu_name = 'No menu selected';
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
        <div class="col"><h1 class="page-title">Menu Builder</h1></div>
        <div class="col-auto">
            <?php if (!empty($menus)): ?>
            <form method="GET" action="/admin/menus.php" id="menu-selector-form">
                <div class="input-group">
                    <label class="input-group-text" for="menu-selector">Select Menu:</label>
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
        <!-- ... (Your accordion HTML remains the same) ... -->
    </div>

    <!-- Right Column: Menu Structure -->
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header"><h6 class="m-0 font-weight-bold">Menu Structure: <?php echo e($current_menu_name); ?></h6></div>
            <div class="card-body">
                <p class="text-muted">Drag and drop to reorder. Click <i class="fas fa-chevron-down"></i> to edit.</p>
                <div class="dd" id="nestable-menu">
                    <ol class="dd-list">
                        <!-- Items will be built by JS -->
                    </ol>
                </div>
            </div>
            <div class="card-footer text-end">
                <button id="save-menu-structure" class="btn btn-primary" <?php if($current_menu_id === 0) echo 'disabled'; ?>>Save Menu</button>
            </div>
        </div>
    </div>
</div>

<?php
$js_data = [
    'menu_items' => $menu_items,
    'current_menu_id' => $current_menu_id,
    'csrf_token' => generate_csrf_token()
];

$page_scripts = "
<link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/nestable2@1.6.0/jquery.nestable.min.css\">
<script src=\"https://code.jquery.com/jquery-3.7.1.min.js\"></script>
<script src=\"https://cdn.jsdelivr.net/npm/nestable2@1.6.0/jquery.nestable.min.js\"></script>
<style>.dd-actions{position:absolute;right:10px;top:6px;z-index:2;}.item-details{padding:10px;background:#f5f5f5;border-top:1px solid #ddd;}</style>
<!-- We assume menu-builder.js exists in /admin/assets/js/ -->
<script> const menuData = " . json_encode($js_data) . "; </script>
<script src=\"/admin/assets/js/menu-builder.js\"></script>
";

require_once 'includes/footer.php';
?>