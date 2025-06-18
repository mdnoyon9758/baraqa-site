<?php
// File: index.php (The Front Controller)

// 1. Load the core application environment
require_once __DIR__ . '/includes/app.php';

// 2. Basic Routing Logic
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = ''; // Since we are at the root

// Remove query string from URI (e.g., ?page=2)
$request_path = strtok($request_uri, '?');

// Remove base path from the request path if it exists
if (strlen($base_path) > 0 && substr($request_path, 0, strlen($base_path)) == $base_path) {
    $request_path = substr($request_path, strlen($base_path));
}

// Trim leading/trailing slashes
$request_path = trim($request_path, '/');

// Parse the path into segments
$segments = explode('/', $request_path);
$main_route = $segments[0] ?? 'home';

// 3. Route the request to the correct page
switch ($main_route) {
    case 'home':
    case '':
        // This is the homepage
        require __DIR__ . '/views/home.php';
        break;

    case 'product':
        // Handle /product/some-slug
        if (isset($segments[1])) {
            $_GET['slug'] = $segments[1]; // Make the slug available for product.php
            require __DIR__ . '/product.php';
        } else {
            http_response_code(404);
            require __DIR__ . '/views/404.php';
        }
        break;

    case 'category':
        // Handle /category/some-slug
        if (isset($segments[1])) {
            $_GET['slug'] = $segments[1];
            require __DIR__ . '/category.php';
        } else {
            http_response_code(404);
            require __DIR__ . '/views/404.php';
        }
        break;

    case 'brand':
        // Handle /brand/some-slug
        if (isset($segments[1])) {
            $_GET['slug'] = $segments[1];
            require __DIR__ . '/brand.php';
        } else {
            http_response_code(404);
            require __DIR__ . '/views/404.php';
        }
        break;
        
    case 'page':
        // Handle /page/about-us
        if (isset($segments[1])) {
            $_GET['slug'] = $segments[1];
            require __DIR__ . '/page.php';
        } else {
            http_response_code(404);
            require __DIR__ . '/views/404.php';
        }
        break;

    case 'wishlist':
        require __DIR__ . '/wishlist.php';
        break;

    case 'search':
        require __DIR__ . '/search.php';
        break;
    
    // --- NEWLY ADDED FOR USER AUTHENTICATION ---
    case 'auth':
        // Handles routes like /auth/login.php, /auth/register.php
        if (isset($segments[1])) {
            $auth_page = $segments[1];
            // Security: Sanitize filename to prevent directory traversal
            $auth_page = basename($auth_page); 
            
            if (file_exists(__DIR__ . '/auth/' . $auth_page)) {
                require __DIR__ . '/auth/' . $auth_page;
            } else {
                http_response_code(404);
                require __DIR__ . '/views/404.php';
            }
        } else {
            // If someone just goes to /auth, redirect to login
            header('Location: /auth/login.php');
            exit;
        }
        break;

    default:
        // If no route matches, show a 404 error
        http_response_code(404);
        require __DIR__ . '/views/404.php';
        break;
}