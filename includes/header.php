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
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/bs/public/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
</head>
<body>
<div class="menu-overlay"></div> <!-- Overlay for mobile menu -->
<header class="site-header sticky-top">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/bs/"><?php echo e($SITE_SETTINGS['site_name']); ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main-nav" aria-controls="main-nav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="main-nav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_uri == '/bs/' || $current_uri == '/bs/index.php') ? 'active' : ''; ?>" href="/bs/">Home</a>
                    </li>

                    <?php // --- START: THE ONLY CHANGE IS ON THIS LINE --- ?>
                    <?php foreach ($nav_categories_for_header as $main_cat): // Use the new limited array from app.php ?>
                        <?php 
                            $has_sub_categories = !empty($nav_sub_categories[$main_cat['id']]);
                            $category_url = "/bs/category/" . e($main_cat['slug']);
                        ?>
                        <li class="nav-item <?php echo $has_sub_categories ? 'dropdown' : ''; ?>">
                            <a class="nav-link <?php echo $has_sub_categories ? 'dropdown-toggle' : ''; ?> <?php echo (strpos($current_uri, $category_url) !== false) ? 'active' : ''; ?>" href="<?php echo $category_url; ?>" <?php if ($has_sub_categories): ?> id="navbarDropdown-<?php echo e($main_cat['id']); ?>" role="button" data-bs-toggle="dropdown" aria-expanded="false" <?php endif; ?>>
                                <?php echo e($main_cat['name']); ?>
                            </a>
                            <?php if ($has_sub_categories): ?>
                                <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="navbarDropdown-<?php echo e($main_cat['id']); ?>">
                                    <li><a class="dropdown-item fw-bold" href="<?php echo $category_url; ?>">All <?php echo e($main_cat['name']); ?></a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <?php foreach ($nav_sub_categories[$main_cat['id']] as $sub_cat): ?>
                                        <li><a class="dropdown-item" href="/bs/category/<?php echo e($sub_cat['slug']); ?>"><?php echo e($sub_cat['name']); ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                    <?php // --- END OF THE CHANGE --- ?>
                    
                    <?php if (!empty($nav_pages)): ?>
                        <li class="nav-item dropdown">
                             <a class="nav-link dropdown-toggle" href="#" id="pagesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">More</a>
                            <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="pagesDropdown">
                                <?php foreach ($nav_pages as $nav_page): ?>
                                    <?php $page_url = "/bs/page/" . e($nav_page['slug']); ?>
                                    <li><a class="dropdown-item <?php echo ($current_uri == $page_url) ? 'active' : ''; ?>" href="<?php echo $page_url; ?>"><?php echo e($nav_page['title']); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex align-items-center ms-lg-3">
                    <form action="/bs/search" method="GET" class="d-flex me-3 position-relative search-container" role="search">
                        <input class="form-control me-2 bg-dark text-white border-secondary" type="search" id="live-search-box" name="q" placeholder="Search..." aria-label="Search" autocomplete="off">
                        <div id="live-search-results" class="position-absolute top-100 start-0 w-100 list-group shadow" style="z-index: 1050; display: none;"></div>
                    </form>
                    <a href="/bs/wishlist" class="text-white position-relative me-3" aria-label="Wishlist"><i class="fas fa-heart fa-lg"></i><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="wishlist-count"><?php echo $wishlist_count; ?></span></a>
                    <a href="#contact" class="btn btn-primary cta-button d-none d-lg-inline-block">Get Started</a>
                </div>
            </div>
        </div>
    </nav>
</header>
<main class="main-content">