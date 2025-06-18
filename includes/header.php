<?php
$wishlist_count = isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;
$current_uri = $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? e($page_title) . ' - ' : ''; ?><?php echo e($SITE_SETTINGS['site_name'] ?? 'AI Affiliate Hub'); ?></title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css"/>
    <link rel="stylesheet" href="/public/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- JS (jQuery is needed for Slick Carousel) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<header class="site-header-amazon sticky-top">
    <!-- Top Bar -->
    <div class="top-bar py-1">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="top-bar-links">
                <a href="#hot-deals" class="top-link">Today's Deals</a>
                <a href="/page/customer-service" class="top-link">Customer Service</a>
            </div>
            <div class="top-bar-account">
    <a href="/wishlist" class="top-link position-relative">
        <i class="fas fa-heart me-1"></i> Wishlist
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="wishlist-count" style="font-size: 0.6em;">
            <?php echo $wishlist_count; ?>
        </span>
    </a>

    <?php if (is_user_logged_in()): ?>
        <div class="dropdown">
            <a href="#" class="top-link dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-user-circle me-1"></i> <?php echo e($_SESSION['user_name']); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="/user/dashboard.php">My Account</a></li>
                <li><a class="dropdown-item" href="/user/orders.php">My Orders</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/auth/logout.php">Logout</a></li>
            </ul>
        </div>
    <?php else: ?>
        <a href="/auth/login.php" class="top-link">Login / Register</a>
    <?php endif; ?>
</div>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="main-nav-bar navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand me-4" href="/"><?php echo e($SITE_SETTINGS['site_name']); ?></a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main-nav-content" aria-controls="main-nav-content" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>

            <div class="collapse navbar-collapse" id="main-nav-content">
                <!-- Amazon-style Search Bar -->
                <form action="/search" method="GET" class="w-100 mx-lg-4 my-2 my-lg-0 search-form-amazon position-relative">
                    <div class="input-group">
                        <button class="btn btn-outline-secondary dropdown-toggle search-dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">All</button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">All Categories</a></li>
                            <?php foreach ($nav_categories_for_header as $cat): ?>
                            <li><a class="dropdown-item" href="#"><?php echo e($cat['name']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                        <input type="text" class="form-control" id="live-search-box" name="q" placeholder="Search for products..." aria-label="Search" autocomplete="off">
                        <button class="btn btn-primary search-button" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                    <div id="live-search-results" class="position-absolute top-100 start-0 w-100 list-group shadow" style="z-index: 1050; display: none;"></div>
                </form>

                <!-- Navigation Links (for mobile view primarily) -->
                <ul class="navbar-nav ms-auto d-lg-none">
                    <?php foreach ($nav_categories_for_header as $main_cat): ?>
                        <li class="nav-item"><a class="nav-link" href="/category/<?php echo e($main_cat['slug']); ?>"><?php echo e($main_cat['name']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Sub Navigation (Desktop only) -->
    <div class="sub-nav-bar d-none d-lg-block py-2">
        <div class="container">
             <ul class="nav">
                <?php foreach ($nav_categories_for_header as $main_cat): ?>
                    <li class="nav-item"><a class="nav-link sub-nav-link" href="/category/<?php echo e($main_cat['slug']); ?>"><?php echo e($main_cat['name']); ?></a></li>
                <?php endforeach; ?>
             </ul>
        </div>
    </div>
</header>
<main class="main-content">