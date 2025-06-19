<?php
// We assume the app.php file (which includes functions.php) has already been loaded.

// Fetch settings for the frontend theme
$site_name = get_setting('site_name', 'BARAQA Affiliate Hub');
$favicon_url = get_setting('site_favicon_url', '/favicon.ico'); // Default favicon at root

// Fetch theme options for dynamic styling
$primary_color = get_setting('primary_color', '#0d6efd'); // Bootstrap's default blue
$secondary_color = get_setting('secondary_color', '#6c757d'); // Bootstrap's default gray
$custom_css = get_setting('custom_css', '');

// Helper function to convert hex color to RGB values for Bootstrap variables
function hexToRgb($hex) {
    $hex = str_replace('#', '', $hex);
    if(strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    return "$r, $g, $b";
}

// Prepare dynamic CSS variables
$primary_color_rgb = hexToRgb($primary_color);
$secondary_color_rgb = hexToRgb($secondary_color);

// Dynamic page title (each page should set its own $page_title)
$page_title_full = isset($page_title) ? e($page_title) . ' - ' . e($site_name) : e($site_name);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title_full; ?></title>

    <!-- SEO Meta Tags (should be set dynamically per page) -->
    <meta name="description" content="<?php echo isset($meta_description) ? e($meta_description) : 'Find the best deals and products.'; ?>">
    <link rel="icon" type="image/x-icon" href="<?php echo e($favicon_url); ?>">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Custom CSS for the frontend -->
    <link rel="stylesheet" href="/public/css/style.css">

    <!-- Dynamic Theme Options -->
    <style>
        :root {
            --bs-primary: <?php echo e($primary_color); ?>;
            --bs-primary-rgb: <?php echo e($primary_color_rgb); ?>;
            --bs-secondary: <?php echo e($secondary_color); ?>;
            --bs-secondary-rgb: <?php echo e($secondary_color_rgb); ?>;
            
            /* You can override other Bootstrap colors here if needed */
        }

        <?php 
        // Output custom CSS from theme options.
        // It's assumed the admin is trusted. If not, this needs sanitization.
        echo $custom_css; 
        ?>
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="/">
                    <?php 
                    $site_logo_url = get_setting('site_logo_url');
                    if ($site_logo_url): ?>
                        <img src="<?php echo e($site_logo_url); ?>" alt="<?php echo e($site_name); ?> Logo" style="max-height: 40px;">
                    <?php else: ?>
                        <?php echo e($site_name); ?>
                    <?php endif; ?>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="mainNavbar">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                        <li class="nav-item"><a class="nav-link" href="/">Home</a></li>
                        <!-- Other nav links can be generated dynamically -->
                    </ul>
                    <div class="d-flex align-items-center">
                        <?php if (is_user_logged_in()): ?>
                            <div class="dropdown">
                                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                                    My Account
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="/user/dashboard">Dashboard</a></li>
                                    <li><a class="dropdown-item" href="/user/orders">My Orders</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="/auth/logout.php">Logout</a></li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <a href="/auth/login.php" class="btn btn-outline-primary btn-sm me-2">Login</a>
                            <a href="/auth/register.php" class="btn btn-primary btn-sm">Register</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main class="py-4">
        <div class="container">