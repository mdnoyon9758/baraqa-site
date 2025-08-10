<?php
// Helper function to fetch and build a hierarchical menu from a given location
function get_menu_by_location($location) {
    global $pdo;
    
    // 1. Get the menu ID assigned to the theme location from settings
    $setting_key = 'menu_location_' . $location;
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
    $stmt->execute([$setting_key]);
    $menu_id = $stmt->fetchColumn();
    
    if (!$menu_id) {
        return []; // Return empty if no menu is assigned to this location
    }
    
    // 2. Get all menu items for the found menu ID
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE menu_id = ? ORDER BY item_order ASC");
    $stmt->execute([$menu_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($items)) {
        return [];
    }

    // 3. Build a hierarchical tree structure from the flat array
    $tree = [];
    $lookup = [];
    foreach ($items as $item) {
        $lookup[$item['id']] = $item;
        $lookup[$item['id']]['children'] = [];
    }
    
    foreach ($lookup as $id => &$node) {
        if ($node['parent_id'] != 0 && isset($lookup[$node['parent_id']])) {
            $lookup[$node['parent_id']]['children'][] =& $node;
        } else {
            $tree[] =& $node;
        }
    }
    
    return $tree;
}

// Fetch the menu items for the primary navigation location
$primary_menu_items = get_menu_by_location('primary');

// Get wishlist count
$wishlist_count = isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? e($page_title) . ' - ' : ''; ?><?php echo e(get_setting('site_name', 'AI Affiliate Hub')); ?></title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/style.css"> <!-- Your main stylesheet -->
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- JS (jQuery is needed if you use plugins that depend on it) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>

<header class="site-header sticky-top">
    <!-- Top Bar: For secondary info and user account links -->
    <div class="top-bar py-1 bg-light border-bottom">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="top-bar-links">
                <a href="#hot-deals" class="top-link small">Today's Deals</a>
                <a href="/page/customer-service" class="top-link small">Customer Service</a>
            </div>
            <div class="top-bar-account d-flex align-items-center">
                <a href="/wishlist" class="top-link position-relative me-3">
                    <i class="bi bi-heart-fill"></i> Wishlist
                    <span id="wishlist-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6em;"><?php echo $wishlist_count; ?></span>
                </a>

                <?php if (is_user_logged_in()): ?>
                    <div class="dropdown">
                        <a href="#" class="top-link dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo e($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/user/dashboard">My Account</a></li>
                            <li><a class="dropdown-item" href="/user/orders">My Orders</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/auth/logout">Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="/auth/login" class="top-link">Login / Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Navigation Bar: For logo, search, and primary actions -->
    <nav class="main-nav-bar navbar navbar-expand-lg bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold fs-4 me-4" href="/"><?php echo e(get_setting('site_name')); ?></a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main-nav-content">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="main-nav-content">
                <form action="/search" method="GET" class="w-100 mx-lg-4 my-2 my-lg-0 position-relative">
                    <div class="input-group">
                        <input type="text" class="form-control" name="q" placeholder="Search for products..." autocomplete="off">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </form>

                <!-- Mobile Navigation Links (will use the same dynamic menu) -->
                <ul class="navbar-nav ms-auto d-lg-none mt-3">
                    <?php foreach ($primary_menu_items as $item): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo e($item['url']); ?>"><?php echo e($item['title']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Sub Navigation Bar: For main menu items -->
    <div class="sub-nav-bar d-none d-lg-block py-2 bg-dark text-white">
        <div class="container">
             <ul class="nav">
                <?php foreach ($primary_menu_items as $item): ?>
                    <?php if (empty($item['children'])): ?>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="<?php echo e($item['url']); ?>">
                                <?php if (!empty($item['icon_class'])): ?><i class="<?php echo e($item['icon_class']); ?> me-1"></i><?php endif; ?>
                                <?php echo e($item['title']); ?>
                            </a>
                        </li>
                    <?php else: // Item has children, so create a dropdown ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link text-white dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <?php if (!empty($item['icon_class'])): ?><i class="<?php echo e($item['icon_class']); ?> me-1"></i><?php endif; ?>
                                <?php echo e($item['title']); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <?php foreach ($item['children'] as $child): ?>
                                    <li><a class="dropdown-item" href="<?php echo e($child['url']); ?>"><?php echo e($child['title']); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
             </ul>
        </div>
    </div>
</header>
<main class="main-content py-4">