<?php
// We assume a session is active and necessary functions are loaded by the core app.php
$site_title = get_setting('site_name') ?? 'BARAQA Admin';
$page_title = isset($page_title) ? e($page_title) : 'Dashboard';
$full_title = $page_title . ' - ' . e($site_title);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $full_title; ?></title>

    <!-- Meta Tags for Admin Panel -->
    <meta name="robots" content="noindex, nofollow">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Custom Admin CSS (New Path) -->
    <link rel="stylesheet" href="/admin/assets/css/admin-style.css">
</head>
<body class="bg-light">
    <!-- This SVG sprite is from your old header, kept for compatibility -->
    <svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
        <symbol id="speedometer2" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5V6a.5.5 0 0 1-1 0V4.5A.5.5 0 0 1 8 4zM3.732 5.732a.5.5 0 0 1 .707 0l.915.914a.5.5 0 1 1-.708.708l-.914-.915a.5.5 0 0 1 0-.707zM2 10a.5.5 0 0 1 .5-.5h1.586a.5.5 0 0 1 0 1H2.5A.5.5 0 0 1 2 10zm9.5 0a.5.5 0 0 1 .5-.5h1.5a.5.5 0 0 1 0 1H12a.5.5 0 0 1-.5-.5zm.754-4.246a.5.5 0 0 1 0 .708l-.914.915a.5.5 0 1 1-.707-.708l.914-.914a.5.5 0 0 1 .707 0zM8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1zM6.5 4.5v-1a1.5 1.5 0 1 1 3 0v1h-3zM8 16a6 6 0 1 1 0-12 6 6 0 0 1 0 12zM10 10.5a2.5 2.5 0 1 0-5 0 2.5 2.5 0 0 0 5 0z"/></symbol>
        <!-- Other symbols... -->
    </svg>

    <div class="admin-layout">
        <!-- Sidebar will be included here -->
        <?php require_once __DIR__ . '/sidebar.php'; ?>

        <div class="main-content-wrapper">
            <!-- Top Navigation Bar -->
            <header class="top-navbar">
                <div class="container-fluid">
                    <!-- Sidebar Toggle Button -->
                    <button id="sidebar-toggle" type="button" class="btn btn-icon">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="ms-auto d-flex align-items-center">
                        <!-- Visit Site Button -->
                        <a href="/" target="_blank" class="btn btn-sm btn-outline-secondary me-3">View Website</a>

                        <!-- User Profile Dropdown -->
                        <div class="dropdown">
                            <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="https://via.placeholder.com/40" class="rounded-circle me-2" width="32" height="32" alt="Avatar">
                                <span class="d-none d-sm-inline"><?php echo e($_SESSION['admin_name'] ?? 'Admin'); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="/admin/profile.php"><i class="fas fa-user-circle me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="/admin/settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="/admin/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content Starts Here -->
            <main class="page-content">
                <div class="container-fluid">
                    <?php display_flash_messages(); ?>