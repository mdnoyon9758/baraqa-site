<?php
// =================================================================
// 1. PAGE CONFIGURATION AND CORE SETUP
// =================================================================
$page_key = 'menus';
$page_title = 'Menu Builder';

require_once __DIR__ . '/../includes/app.php';
require_login();

// =================================================================
// 2. DATA FETCHING
// =================================================================
try {
    // সমস্ত মেনু এবং তাদের লোকেশন ফেচ করা
    $menus = $pdo->query("SELECT * FROM menus ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $menu_locations_stmt = $pdo->query("SELECT * FROM site_settings WHERE setting_key LIKE 'menu_location_%'");
    $saved_locations = [];
    foreach ($menu_locations_stmt as $loc) {
        $saved_locations[$loc['setting_key']] = $loc['setting_value'];
    }

    // আপনার থিমে উপলব্ধ মেনু লোকেশনগুলো এখানে নির্ধারণ করুন
    $registered_locations = [
        'menu_location_primary' => 'Primary Navigation',
        'menu_location_footer'  => 'Footer Menu',
        'menu_location_mobile' => 'Mobile Menu'
    ];

    // বর্তমান মেনু আইডি সেট করা
    $current_menu_id = $_GET['menu_id'] ?? ($menus[0]['id'] ?? 0);
    $current_menu_id = (int)$current_menu_id;

    // মেনুতে যোগ করার জন্য পেজ এবং ক্যাটাগরি ফেচ করা
    $pages = $pdo->query("SELECT id, title, slug FROM pages WHERE status = 'published' ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
    $categories = $pdo->query("SELECT id, name, slug FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

    // বর্তমান মেনুর নাম খুঁজে বের করা
    $current_menu_name = 'No menu selected';
    if ($current_menu_id > 0) {
        foreach ($menus as $menu) {
            if ($menu['id'] == $current_menu_id) {
                $current_menu_name = $menu['name'];
                break;
            }
        }
    }
} catch (PDOException $e) {
    set_flash_message('Database Error: ' . $e->getMessage(), 'danger');
    $menus = $pages = $categories = [];
    $current_menu_id = 0;
    $current_menu_name = 'Error';
}

// =================================================================
// 3. RENDER THE VIEW
// =================================================================
require_once 'includes/header.php';
?>

<!-- পেজের প্রধান শিরোনাম -->
<div class="page-header mb-4">
    <h1 class="page-title">Menu Builder</h1>
</div>

<!-- প্রধান মেনু কন্ট্রোল প্যানেল -->
<div class="card shadow-sm mb-4">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div>
            <form method="GET" action="/admin/menus.php" id="menu-selector-form" class="d-inline-block">
                <div class="input-group">
                    <label class="input-group-text" for="menu-selector">Edit Menu:</label>
                    <select id="menu-selector" name="menu_id" class="form-select" onchange="this.form.submit()">
                        <?php if (empty($menus)): ?>
                            <option>No menus created</option>
                        <?php else: ?>
                            <?php foreach($menus as $menu): ?>
                                <option value="<?php echo e($menu['id']); ?>" <?php if($current_menu_id == $menu['id']) echo 'selected'; ?>>
                                    <?php echo e($menu['name']); ?> (ID: <?php echo e($menu['id']); ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </form>
            <span class="text-muted mx-2">or</span>
            <a href="#" id="create-new-menu-link">create a new menu</a>.
        </div>
        <div>
            <?php if ($current_menu_id > 0): ?>
                <button class="btn btn-sm btn-outline-danger" id="delete-menu-btn">Delete Menu</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ট্যাব: স্ট্রাকচার ম্যানেজ এবং লোকেশন ম্যানেজ -->
<ul class="nav nav-tabs" id="menu-tabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="structure-tab" data-bs-toggle="tab" data-bs-target="#structure-pane" type="button" role="tab">Manage Structure</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="locations-tab" data-bs-toggle="tab" data-bs-target="#locations-pane" type="button" role="tab">Manage Locations</button>
    </li>
</ul>

<div class="tab-content pt-4">
    <!-- ট্যাব ১: মেনু স্ট্রাকচার -->
    <div class="tab-pane fade show active" id="structure-pane" role="tabpanel">
        <div class="row">
            <!-- বাম কলাম: মেনু আইটেম যোগ করার জন্য -->
            <div class="col-lg-4">
                <div class="accordion" id="add-menu-items-accordion">
                    <!-- কাস্টম লিঙ্ক -->
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-custom-links">Custom Links</button></h2>
                        <div id="collapse-custom-links" class="accordion-collapse collapse show">
                            <div class="accordion-body">
                                <form id="form-add-custom-link">
                                    <div class="mb-2"><label class="form-label">URL</label><input type="text" name="url" class="form-control" placeholder="https://..." required></div>
                                    <div class="mb-2"><label class="form-label">Link Text</label><input type="text" name="label" class="form-control" required></div>
                                    <button type="submit" class="btn btn-sm btn-primary">Add to Menu</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- পেজ -->
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-pages">Pages</button></h2>
                        <div id="collapse-pages" class="accordion-collapse collapse">
                            <div class="accordion-body" style="max-height: 250px; overflow-y: auto;">
                                <form id="form-add-pages">
                                    <?php foreach($pages as $page): ?>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="items[]" value="<?php echo e($page['id']); ?>" data-type="page" data-title="<?php echo e($page['title']); ?>"><label class="form-check-label"><?php echo e($page['title']); ?></label></div>
                                    <?php endforeach; ?>
                                    <button type="submit" class="btn btn-sm btn-primary mt-2">Add to Menu</button>
                                </form>
                            </div>
                        </div>
                    </div>
                     <!-- ক্যাটাগরি -->
                    <div class="accordion-item">
                        <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-categories">Categories</button></h2>
                        <div id="collapse-categories" class="accordion-collapse collapse">
                            <div class="accordion-body" style="max-height: 250px; overflow-y: auto;">
                                <form id="form-add-categories">
                                    <?php foreach($categories as $category): ?>
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="items[]" value="<?php echo e($category['id']); ?>" data-type="category" data-title="<?php echo e($category['name']); ?>"><label class="form-check-label"><?php echo e($category['name']); ?></label></div>
                                    <?php endforeach; ?>
                                    <button type="submit" class="btn btn-sm btn-primary mt-2">Add to Menu</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ডান কলাম: মেনু স্ট্রাকচার -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Menu Structure: <strong><?php echo e($current_menu_name); ?></strong></h5>
                        <button id="save-menu-structure" class="btn btn-primary" <?php if($current_menu_id === 0) echo 'disabled'; ?>>Save Menu</button>
                    </div>
                    <div class="card-body">
                        <?php if($current_menu_id > 0): ?>
                            <p class="text-muted small">Drag items to reorder. Click the <i class="bi bi-pencil-square"></i> icon to edit an item's details.</p>
                            <div class="dd" id="nestable-menu">
                                <!-- মেনু আইটেমগুলো জাভাস্ক্রিপ্ট দিয়ে এখানে লোড হবে -->
                                <div class="dd-empty"></div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">Please create a menu first or select one to start building its structure.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ট্যাব ২: মেনু লোকেশন -->
    <div class="tab-pane fade" id="locations-pane" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-header"><h5 class="mb-0">Theme Locations</h5></div>
            <div class="card-body">
                <p class="text-muted">You can assign a menu to a specific location in your theme.</p>
                <form id="menu-locations-form">
                    <?php foreach ($registered_locations as $key => $label): ?>
                    <div class="row mb-3 align-items-center">
                        <div class="col-md-3"><strong><?php echo e($label); ?></strong></div>
                        <div class="col-md-4">
                            <select name="<?php echo e($key); ?>" class="form-select">
                                <option value="0">— Select a Menu —</option>
                                <?php foreach ($menus as $menu): ?>
                                    <option value="<?php echo e($menu['id']); ?>" <?php if(isset($saved_locations[$key]) && $saved_locations[$key] == $menu['id']) echo 'selected'; ?>>
                                        <?php echo e($menu['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-primary">Save Locations</button>
                </form>
            </div>
        </div>
    </div>
</div>


<?php
// PHP থেকে জাভাস্ক্রিপ্টে ডেটা পাঠানোর জন্য
$js_data = [
    'current_menu_id' => $current_menu_id,
    'csrf_token'      => generate_csrf_token()
];

// পেজের জন্য নির্দিষ্ট স্ক্রিপ্ট এবং স্টাইল
$page_scripts = "
<!-- Nestable2 লাইব্রেরি -->
<link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/nestable2@1.6.0/jquery.nestable.min.css\">
<script src=\"https://code.jquery.com/jquery-3.7.1.min.js\"></script>
<script src=\"https://cdn.jsdelivr.net/npm/nestable2@1.6.0/jquery.nestable.min.js\"></script>
<!-- Bootstrap Icons -->
<link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css\">

<!-- মেনু বিল্ডারের জন্য কাস্টম স্টাইল (বুটস্ট্র্যাপের উপরে) -->
<style>
    .dd { position: relative; display: block; list-style: none; margin: 0; padding: 0; max-width: 100%; }
    .dd-list { display: block; position: relative; list-style: none; margin: 0; padding: 0; }
    .dd-list .dd-list { padding-left: 30px; }
    .dd-item, .dd-empty, .dd-placeholder { display: block; position: relative; margin: 0; padding: 0; min-height: 20px; font-size: 13px; line-height: 20px; }
    .dd-handle { 
        display: block; 
        height: auto; 
        margin: 5px 0; 
        padding: 8px 12px; 
        color: #333; 
        text-decoration: none; 
        font-weight: 500;
        border: 1px solid #ccc; 
        background: #fafafa; 
        border-radius: 3px; 
        box-sizing: border-box; 
        cursor: move;
    }
    .dd-handle:hover { background: #f5f5f5; }
    .dd-item > button { position: relative; cursor: pointer; float: left; width: 25px; height: 20px; margin: 5px 0; padding: 0; text-indent: 100%; white-space: nowrap; overflow: hidden; border: 0; background: transparent; font-size: 12px; line-height: 1; text-align: center; font-weight: bold; }
    .item-details { display: none; padding: 15px; background: #f9f9f9; border: 1px solid #ccc; border-top: none; margin-top: -6px; margin-bottom: 5px; }
    .item-details label { font-weight: 600; }
    .dd-actions { position: absolute; right: 10px; top: 8px; z-index: 2; }
    .dd-actions a { cursor: pointer; margin-left: 8px; font-size: 1.1rem; }
    .dd-actions a:hover { color: #0d6efd; }
</style>

<!-- মেনু বিল্ডার জাভাস্ক্রিপ্ট -->
<script> const menu_builder_data = " . json_encode($js_data) . "; </script>
<script src=\"/admin/assets/js/menu-builder.js\"></script>
";

require_once 'includes/footer.php';
?>