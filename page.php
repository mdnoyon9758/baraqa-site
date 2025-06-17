<?php
// We need the full application environment, including functions and database connection.
// session_start() should be called here or in app.php to access session variables.
require_once __DIR__ . '/includes/app.php';

// Initialize variables to safe defaults to prevent errors.
$page = null;
$page_title = "Page";

// Get the page slug from the URL. Redirect to the homepage if the slug is not provided.
$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    header("Location: /bs/");
    exit;
}

try {
    // Fetch all columns (*) for the requested page from the database.
    $stmt = $pdo->prepare(
        "SELECT * FROM site_pages WHERE slug = :slug AND is_published = 1"
    );
    
    $stmt->execute(['slug' => $slug]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // On a database error, log it and show a generic server error page.
    error_log('Static page loading error: ' . $e->getMessage());
    http_response_code(503); // Service Unavailable
    $page_title = "Service Temporarily Unavailable";
    require_once __DIR__ . '/includes/header.php'; // Load header to maintain site structure
    echo '<div class="container text-center my-5 py-5"><h1 class="display-1">Error</h1><h2>Service Unavailable</h2><p class="lead">We are currently experiencing technical difficulties. Please try again later.</p></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit; // Stop the script immediately.
}

// If no page was found for the given slug, it's a 404 Not Found error.
if (!$page) {
    http_response_code(404);
    $page_title = "Page Not Found";
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container text-center my-5 py-5"><h1 class="display-1">404</h1><h2>Page Not Found</h2><p class="lead">The page you are looking for does not exist or has been moved.</p><a href="/bs/" class="btn btn-primary mt-3">Go to Homepage</a></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit; // Stop the script immediately.
}

// If the page is found, set the page title for the header.
$page_title = $page['title'];

// All data is ready, now include the main header.
require_once __DIR__ . '/includes/header.php';
?>

<!-- Custom Styled Static Page Layout -->
<div class="page-header bg-light py-5">
    <div class="container text-center">
        <h1 class="display-4"><?php echo e($page['title']); ?></h1>
    </div>
</div>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <article class="page-content">
                <?php
                    // Check if content is empty. Use trim() to handle whitespace.
                    if (empty(trim($page['content']))) {
                        // Display the default message for regular users
                        echo '<p class="text-center text-muted">No content has been added to this page yet.</p>';
                        
                        // --- IMPROVEMENT ---
                        // If an admin is logged in, show a helpful link to edit the page.
                        // Assumes your auth.php sets $_SESSION['user_id']
                        if (isset($_SESSION['user_id'])) {
                             // The path to your admin folder might be different.
                            $admin_path = '/bs/admin/'; 
                            echo '<div class="text-center mt-4"><a href="' . $admin_path . 'manage_pages.php?action=edit&id=' . e($page['id']) . '" class="btn btn-info">Add Content Now (Admin)</a></div>';
                        }

                    } else {
                        // If content exists, display it
                        echo $page['content'];
                    }
                ?>
            </article>
        </div>
    </div>
</div>

<?php
// Include the standard footer to close the page.
require_once __DIR__ . '/includes/footer.php';
?>