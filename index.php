<?php
// Core application environment
require_once __DIR__ . '/includes/app.php';

// Parse the request URI to determine the route
$request_path = strtok($_SERVER['REQUEST_URI'], '?');
$request_path = trim($request_path, '/');
$segments = $request_path ? explode('/', $request_path) : [];

// Determine the primary route, default to 'home'
$main_route = $segments[0] ?? 'home';
$slug = $segments[1] ?? null;

// Define a simple routing map
$routes = [
    '' => 'views/home.php',
    'home' => 'views/home.php',
    'product' => 'product.php',
    'category' => 'category.php',
    'brand' => 'brand.php',
    'page' => 'page.php',
    'wishlist' => 'wishlist.php',
    'search' => 'search.php',
    'auth' => 'auth/',
    'user' => 'user/'
];

// Route the request
if (isset($routes[$main_route])) {
    $target_file = $routes[$main_route];

    // Handle nested routes like /auth/login or /user/dashboard
    if (is_dir(__DIR__ . '/' . $target_file)) {
        $sub_route = $slug ?? 'index'; // Default to index or a specific sub-page
        
        // Security: Whitelist allowed pages to prevent file inclusion vulnerabilities
        $allowed_pages = [];
        if ($main_route === 'auth') {
            $allowed_pages = ['login', 'register', 'logout'];
            if (!$slug) { // Redirect /auth to /auth/login
                 header('Location: /auth/login');
                 exit;
            }
        } elseif ($main_route === 'user') {
            $allowed_pages = ['dashboard', 'orders', 'security'];
             if (!$slug) { // Redirect /user to /user/dashboard
                 header('Location: /user/dashboard');
                 exit;
            }
        }

        if (in_array($sub_route, $allowed_pages)) {
            $page_path = __DIR__ . '/' . $target_file . $sub_route . '.php';
            if (file_exists($page_path)) {
                require $page_path;
            } else {
                show_404();
            }
        } else {
            show_404();
        }
    } 
    // Handle routes that require a slug, like /product/my-product
    elseif (in_array($main_route, ['product', 'category', 'brand', 'page'])) {
        if ($slug) {
            $_GET['slug'] = $slug; // Make slug available for the required file
            require __DIR__ . '/' . $target_file;
        } else {
            // If route requires a slug but none is provided, it's a 404
            show_404();
        }
    } 
    // Handle simple routes like /wishlist or /home
    else {
        require __DIR__ . '/' . $target_file;
    }
} else {
    // If no route matches, it's a 404
    show_404();
}

/**
 * Helper function to display a 404 error page.
 * This avoids code repetition.
 */
function show_404() {
    http_response_code(404);
    require __DIR__ . '/views/404.php';
    exit;
}