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
    
    // --- Route for USER AUTHENTICATION ---
    case 'auth':
        // Handles routes like /auth/login, /auth/register
        if (isset($segments[1])) {
            $auth_page_name = $segments[1];
            // Security: Prevent directory traversal and only allow specific filenames
            $allowed_auth_pages = ['login.php', 'register.php', 'logout.php'];
            $auth_page_file = $auth_page_name . '.php';

            if (in_array($auth_page_file, $allowed_auth_pages) && file_exists(__DIR__ . '/auth/' . $auth_page_file)) {
                require __DIR__ . '/auth/' . $auth_page_file;
            } else {
                http_response_code(404);
                require __DIR__ . '/views/404.php';
            }
        } else {
            // If someone just goes to /auth, redirect to login
            header('Location: /auth/login');
            exit;
        }
        break;

    // --- NEWLY ADDED FOR USER ACCOUNT ---
    case 'user':
        // Handles routes like /user/dashboard, /user/orders, /user/security
        if (isset($segments[1])) {
            $user_page_name = $segments[1];
            // Whitelist allowed pages for security
            $allowed_user_pages = ['dashboard.php', 'orders.php', 'security.php'];
            $user_page_file = $user_page_name . '.php';

            if (in_array($user_page_file, $allowed_user_pages) && file_exists(__DIR__ . '/user/' . $user_page_file)) {
                require __DIR__ . '/user/' . $user_page_file;
            } else {
                // If a user page does not exist (e.g., /user/invalid-page)
                http_response_code(404);
                require __DIR__ . '/views/404.php';
            }
        } else {
            // If someone just goes to /user, redirect them to the dashboard
            header('Location: /user/dashboard');
            exit;
        }
        break;

    default:
        // If the path looks like a filename (e.g., admin.css), it's likely a static asset. Let the web server handle it.
        // This check prevents the 404 page from showing for CSS/JS files if .htaccess isn't configured perfectly.
        if (preg_match('/\.(?:css|js|jpg|jpeg|png|gif|ico|svg)$/', $request_path)) {
            return false; // Let the server handle the request
        }

        // If no route matches, show a 404 error
        http_response_code(404);
        require __DIR__ . '/views/404.php';
        break;
}