<?php
// Note: This file should be included AFTER 'auth.php'.
// We assume that a session is active, the user is authenticated,
// and all necessary functions from functions.php are already loaded by auth.php.

// --- Dynamic Sidebar Menu with absolute paths ---
$admin_base_url = '/admin/';
$menu_items = [
    ['name' => 'Dashboard', 'url' => $admin_base_url . 'dashboard.php', 'icon' => 'speedometer2'],
    ['name' => 'Products', 'url' => $admin_base_url . 'products.php', 'icon' => 'grid'],
    ['name' => 'Categories', 'url' => $admin_base_url . 'categories.php', 'icon' => 'tags'],
    ['name' => 'Brands', 'url' => $admin_base_url . 'manage_brands.php', 'icon' => 'bookmark-star'],
    ['name' => 'Pages', 'url' => $admin_base_url . 'manage_pages.php', 'icon' => 'file-earmark-text'],
    
    // --- NEWLY ADDED FOR USER MANAGEMENT ---
    ['name' => 'User Management', 'url' => $admin_base_url . 'users.php', 'icon' => 'person-circle'],

    ['name' => 'API Config', 'url' => $admin_base_url . 'manage_api.php', 'icon' => 'cpu'],
    ['name' => 'Task Scheduler', 'url' => $admin_base_url . 'scheduler.php', 'icon' => 'clock-history'],
    ['name' => 'Site Settings', 'url' => $admin_base_url . 'settings.php', 'icon' => 'gear'],
    ['name' => 'Clicked Leads', 'url' => $admin_base_url . 'clicked_leads.php', 'icon' => 'graph-up-arrow'],
    ['name' => 'Notifications', 'url' => $admin_base_url . 'notifications.php', 'icon' => 'bell'],
    ['name' => 'System Logs', 'url' => $admin_base_url . 'logs.php', 'icon' => 'journal-text'],
];

// Match the current page against the absolute URL
$current_page_uri = $_SERVER['REQUEST_URI'];
$site_title = get_setting('site_name') ?? 'BARAQA Admin';
// The $page_title variable should be set on each page before including this header.
$full_title = isset($page_title) ? e($page_title) . ' - ' . e($site_title) : e($site_title);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $full_title; ?></title>
    <!-- SEO meta tags are not needed for admin area -->
    <meta name="robots" content="noindex, nofollow">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Corrected path for admin.css -->
    <link rel="stylesheet" href="/public/css/admin.css">
</head>
<body>
    <!-- SVG Icon Sprite for performance -->
    <svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
        <!-- All SVG symbols remain the same... -->
        <symbol id="speedometer2" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5V6a.5.5 0 0 1-1 0V4.5A.5.5 0 0 1 8 4zM3.732 5.732a.5.5 0 0 1 .707 0l.915.914a.5.5 0 1 1-.708.708l-.914-.915a.5.5 0 0 1 0-.707zM2 10a.5.5 0 0 1 .5-.5h1.586a.5.5 0 0 1 0 1H2.5A.5.5 0 0 1 2 10zm9.5 0a.5.5 0 0 1 .5-.5h1.5a.5.5 0 0 1 0 1H12a.5.5 0 0 1-.5-.5zm.754-4.246a.5.5 0 0 1 0 .708l-.914.915a.5.5 0 1 1-.707-.708l.914-.914a.5.5 0 0 1 .707 0zM8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1zM6.5 4.5v-1a1.5 1.5 0 1 1 3 0v1h-3zM8 16a6 6 0 1 1 0-12 6 6 0 0 1 0 12zM10 10.5a2.5 2.5 0 1 0-5 0 2.5 2.5 0 0 0 5 0z"/></symbol>
        <symbol id="grid" viewBox="0 0 16 16"><path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5v-3zM2.5 2a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zM9 2.5A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5v-3zM10.5 2a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zM1 10.5A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5v-3zM2.5 10a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zM9 10.5A1.5 1.5 0 0 1 10.5 9h3A1.5 1.5 0 0 1 15 10.5v3A1.5 1.5 0 0 1 13.5 15h-3A1.5 1.5 0 0 1 9 13.5v-3zM10.5 10a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3z"/></symbol>
        <symbol id="tags" viewBox="0 0 16 16"><path d="M3 2v4.586l7 7L14.586 9l-7-7H3zM2 2a1 1 0 0 1 1-1h4.586a1 1 0 0 1 .707.293l7 7a1 1 0 0 1 0 1.414l-4.586 4.586a1 1 0 0 1-1.414 0l-7-7A1 1 0 0 1 2 6.586V2z"/><path d="M5.5 5a.5.5 0 1 1 0-1 .5.5 0 0 1 0 1zm0 1a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zM1 7.086a1 1 0 0 0 .293.707l7 7a1 1 0 0 0 1.414 0l.043-.043-7.457-7.457A1 1 0 0 0 1 7.086V7z"/></symbol>
        <symbol id="bookmark-star" viewBox="0 0 16 16"><path d="M7.84 4.1a.178.178 0 0 1 .32 0l.634 1.285a.178.178 0 0 0 .134.098l1.42.206c.145.021.204.2.098.303L9.42 6.993a.178.178 0 0 0-.051.158l.242 1.414a.178.178 0 0 1-.258.187l-1.27-.668a.178.178 0 0 0-.165 0l-1.27.668a.178.178 0 0 1-.257-.187l.242-1.414a.178.178 0 0 0-.05-.158l-1.03-1.001a.178.178 0 0 1 .098-.303l1.42-.206a.178.178 0 0 0 .134-.098L7.84 4.1z"/><path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v13.5a.5.5 0 0 1-.777.416L8 13.101l-5.223 2.815A.5.5 0 0 1 2 15.5V2zm2-1a1 1 0 0 0-1 1v12.566l4.723-2.482a.5.5 0 0 1 .554 0L13 14.566V2a1 1 0 0 0-1-1H4z"/></symbol>
        <symbol id="file-earmark-text" viewBox="0 0 16 16"><path d="M5.5 7a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5zM5 9.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5z"/><path d="M9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.5L9.5 0zm0 1v2.5a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5V1h4zM3 2a1 1 0 0 1 1-1h5.5v2.5c0 .828.672 1.5 1.5 1.5H13a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2z"/></symbol>
        <symbol id="gear" viewBox="0 0 16 16"><path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/><path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.902 3.433 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.115 2.692l.319.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115l.094-.319z"/></symbol>
        <symbol id="cpu" viewBox="0 0 16 16"><path d="M5 0a.5.5 0 0 1 .5.5V2h1V.5a.5.5 0 0 1 1 0V2h1V.5a.5.5 0 0 1 1 0V2h1V.5a.5.5 0 0 1 1 0V2A2.5 2.5 0 0 1 14 4.5V6h1.5a.5.5 0 0 1 0 1H14v1h1.5a.5.5 0 0 1 0 1H14v1h1.5a.5.5 0 0 1 0 1H14v1.5a2.5 2.5 0 0 1-2.5 2.5H11v1.5a.5.5 0 0 1-1 0V14h-1v1.5a.5.5 0 0 1-1 0V14h-1v1.5a.5.5 0 0 1-1 0V14H4.5A2.5 2.5 0 0 1 2 11.5V10H.5a.5.5 0 0 1 0-1H2V8H.5a.5.5 0 0 1 0-1H2V6H.5a.5.5 0 0 1 0-1H2V4.5A2.5 2.5 0 0 1 4.5 2H5V.5A.5.5 0 0 1 5 0zm-.5 3h6A1.5 1.5 0 0 1 12 4.5V6h-1V4.5A.5.5 0 0 0 10.5 4h-5A.5.5 0 0 0 5 4.5V6H4V4.5A1.5 1.5 0 0 1 4.5 3zM4 7v1h1V7H4zm2 0v1h1V7H6zm2 0v1h1V7H8zm2 0v1h1V7h-1zM4 9v1h1V9H4zm2 0v1h1V9H6zm2 0v1h1V9H8zm2 0v1h1V9h-1zM4.5 13A1.5 1.5 0 0 1 3 11.5V10h1v1.5a.5.5 0 0 0 .5.5h5a.5.5 0 0 0 .5-.5V10h1v1.5A1.5 1.5 0 0 1 11.5 13h-7z"/></symbol>
        <symbol id="person-circle" viewBox="0 0 16 16"><path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/><path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/></symbol>
        <symbol id="graph-up-arrow" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M0 0h1v15h15v1H0V0Zm10 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0V4.9l-3.613 4.417a.5.5 0 0 1-.74.037L7.06 6.767l-3.656 5.027a.5.5 0 0 1-.808-.588l4-5.5a.5.5 0 0 1 .758-.06l2.609 2.61L13.445 4H10.5a.5.5 0 0 1-.5-.5Z"/></symbol>
        <symbol id="bell" viewBox="0 0 16 16"><path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zM8 1.918l-.797.161A4.002 4.002 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4.002 4.002 0 0 0-3.203-3.92L8 1.917zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5.002 5.002 0 0 1 13 6c0 .88.32 4.2 1.22 6z"/></symbol>
        <symbol id="journal-text" viewBox="0 0 16 16"><path d="M5 10.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5z"/><path d="M3 0h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v1H1V2a2 2 0 0 1 2-2z"/><path d="M1 5v-.5a.5.5 0 0 1 1 0V5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0V8h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0v.5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1z"/></symbol>
        <symbol id="clock-history" viewBox="0 0 16 16"><path d="M8.515 1.019A7 7 0 0 0 8 1V0a8 8 0 0 1 .586.023l8.07-8.07A.5.5 0 0 1 16 .5v2a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5V1.079l-2.004.92a.502.502 0 0 1-.36.024l-3.35-1.55a.5.5 0 0 1-.154-.362V1.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5v.693l1.89-1.393a.5.5 0 0 1 .64.773l-1.89 1.393z"/><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/></symbol>
    </svg>

    <div class="d-flex" id="admin-wrapper">
        <!-- Sidebar -->
        <div class="bg-dark border-end" id="sidebar-wrapper">
            <div class="sidebar-heading bg-dark text-white">
                <!-- Corrected path for dashboard link -->
                <a href="<?php echo $admin_base_url; ?>dashboard.php" class="text-white text-decoration-none h5"><?php echo e($site_title); ?></a>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($menu_items as $item): ?>
                    <?php 
                    // Check if the current page URI matches the menu item's URL exactly for better accuracy
                    $is_active = ($current_page_uri === $item['url']) ? 'active' : ''; 
                    ?>
                    <a href="<?php echo e($item['url']); ?>" class="list-group-item list-group-item-action bg-dark text-white <?php echo $is_active; ?>">
                        <svg class="bi me-2" width="16" height="16"><use xlink:href="#<?php echo e($item['icon']); ?>"/></svg> 
                        <?php echo e($item['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <!-- /#sidebar-wrapper -->

        <!-- Page Content Wrapper -->
        <div id="page-content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary" id="menu-toggle"><i class="fas fa-bars"></i></button>

                    <ul class="navbar-nav ms-auto mt-2 mt-lg-0">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <svg class="bi me-1" width="24" height="24"><use xlink:href="#person-circle"/></svg>
                                <?php echo e($_SESSION['admin_name'] ?? 'Admin'); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <!-- Corrected paths for profile and logout -->
                                <li><a class="dropdown-item" href="<?php echo $admin_base_url; ?>profile.php">Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo $admin_base_url; ?>logout.php">Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
            <div class="container-fluid p-4">
                <?php display_flash_messages(); ?>
                <!-- Main Content of each page will start here -->