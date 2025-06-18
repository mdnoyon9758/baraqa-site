<?php
// ফাইল: index.php (নতুন রাউটার)

// ১. কোর অ্যাপ্লিকেশন লোড করুন
require_once __DIR__ . '/includes/app.php';

// ২. URL পার্স করুন
$request_uri = strtok($_SERVER['REQUEST_URI'], '?'); // URL থেকে ? অংশ বাদ দিন
$request_path = trim($request_uri, '/');
$segments = explode('/', $request_path);
$main_route = $segments[0] ?? 'home';

// ৩. সঠিক পেজ দেখান
switch ($main_route) {
    case 'home':
    case '': // যদি URL শুধু "/" হয়
        require __DIR__ . '/views/home.php';
        break;

    case 'product':
        if (isset($segments[1])) {
            $_GET['slug'] = $segments[1]; // product.php-এর জন্য slug সেট করুন
            require __DIR__ . '/product.php';
        }
        break;

    case 'category':
        if (isset($segments[1])) {
            $_GET['slug'] = $segments[1];
            require __DIR__ . '/category.php';
        }
        break;

    case 'brand':
        if (isset($segments[1])) {
            $_GET['slug'] = $segments[1];
            require __DIR__ . '/brand.php';
        }
        break;
        
    case 'page':
        if (isset($segments[1])) {
            $_GET['slug'] = $segments[1];
            require __DIR__ . '/page.php';
        }
        break;

    case 'wishlist':
        require __DIR__ . '/wishlist.php';
        break;

    case 'search':
        require __DIR__ . '/search.php';
        break;

    default: // যদি কোনো রুট না মেলে
        http_response_code(404);
        require __DIR__ . '/views/404.php'; // 404 পেজ দেখান
        break;
}