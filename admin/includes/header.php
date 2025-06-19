<?php
// =================================================================
// ADMIN HEADER
// This file sets up the HTML head, loads assets, and creates the top navigation bar.
// It assumes that app.php has already been loaded.
// =================================================================

// Fetch essential settings for the header
$site_name = get_setting('site_name', 'BARAQA Admin');
$favicon_url = get_setting('site_favicon_url', '/admin/assets/images/favicon.ico'); // Default admin favicon

// Prepare dynamic page titles and body classes
$page_title = isset($page_title) ? e($page_title) : 'Dashboard';
$full_title = $page_title . ' - ' . e($site_name);
$body_class_key = isset($page_key) ? 'page-' . e($page_key) : 'page-dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $full_title; ?></title>

    <!-- Meta Tags: Important for security and to prevent indexing of admin pages -->
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/x-icon" href="<?php echo e($favicon_url); ?>">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Core CSS Assets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Custom Admin Stylesheet -->
    <link rel="stylesheet" href="/admin/assets/css/admin-style.css">
</head>
<body class="bg-light <?php echo $body_class_key; ?>">

    <!-- Main Admin Layout Wrapper -->
    <div class="admin-layout">
        
        <!-- The sidebar is included here, making the structure modular and clean. -->
        <?php require_once __DIR__ . '/sidebar.php'; ?>

        <!-- This wrapper contains the top navbar and the main page content. -->
        <div class="main-content-wrapper">
            
            <!-- Top Navigation Bar -->
            <header class="top-navbar">
                <div class="container-fluid">
                    <!-- Sidebar Toggle Button (for mobile and toggled view) -->
                    <button id="sidebar-toggle" type="button" class="btn btn-icon">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <!-- Right-aligned items -->
                    <div class="ms-auto d-flex align-items-center">
                        <!-- Visit Site Button -->
                        <a href="/" target="_blank" class="btn btn-sm btn-outline-secondary me-3">View Website</a>

                        <!-- User Profile Dropdown -->
                        <div class="dropdown">
                            <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="https://www.gravatar.com/avatar/<?php echo md5(strtolower(trim($_SESSION['admin_email'] ?? ''))); ?>?s=40&d=mp" class="rounded-circle me-2" width="32" height="32" alt="Admin Avatar">
                                <span class="d-none d-sm-inline"><?php echo e($_SESSION['admin_name'] ?? 'Admin'); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="/admin/profile.php"><i class="fas fa-user-circle me-2"></i>My Profile</a></li>
                                <li><a class="dropdown-item" href="/admin/settings.php"><i class="fas fa-cog me-2"></i>Site Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="/admin/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Page Content Starts Here -->
            <main class="page-content">
                <div class="container-fluid">
                    <?php display_flash_messages(); ?>
                    <!-- The specific page content (like dashboard.php) will start from here -->