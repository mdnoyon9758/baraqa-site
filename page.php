<?php
// Our front controller (index.php) handles app initialization.

// The slug is made available by our front controller in index.php
$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    header("Location: /"); // Redirect to homepage if no slug
    exit;
}

try {
    // Fetch the requested page from the database.
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
    require __DIR__ . '/includes/header.php';
    echo '<div class="container text-center my-5 py-5"><h1 class="display-1">Error</h1><h2>Service Unavailable</h2><p class="lead">We are currently experiencing technical difficulties. Please try again later.</p></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// If no page was found for the given slug, show our standard 404 page.
if (!$page) {
    http_response_code(404);
    require __DIR__ . '/views/404.php'; // Use our standard 404 page
    exit;
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
                    if (empty(trim($page['content']))) {
                        echo '<p class="text-center text-muted">No content has been added to this page yet.</p>';
                        
                        // FIX: Check for the correct admin session variable
                        if (isset($_SESSION['admin_id'])) {
                             // PATH FIX: Use a root-relative path for the admin link
                            $admin_edit_url = '/admin/manage_pages.php?action=edit&id=' . e($page['id']);
                            echo '<div class="text-center mt-4"><a href="' . $admin_edit_url . '" class="btn btn-info">Add Content Now (Admin)</a></div>';
                        }
                    } else {
                        // Using 'echo' is fine here since the content is expected to be HTML.
                        // Ensure you trust the source of this HTML (i.e., only admins can edit it).
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