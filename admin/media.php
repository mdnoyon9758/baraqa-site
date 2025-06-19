<?php
// =================================================================
// 1. PAGE CONFIGURATION AND CORE SETUP
// =================================================================
$page_key = 'media';
$page_title = 'Media Library';

require_once __DIR__ . '/../includes/app.php';
require_login();

// =================================================================
// 3. DATA FETCHING AND VIEW RENDERING (GET REQUEST)
// =================================================================
require_once 'includes/header.php';

try {
    // Fetch media files with pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 18; // প্রতি পৃষ্ঠায় আইটেম সংখ্যা
    $offset = ($page - 1) * $limit;

    $total_media = $pdo->query("SELECT COUNT(id) FROM media")->fetchColumn();
    $total_pages = ceil($total_media / $limit);
    
    $media_stmt = $pdo->prepare("SELECT * FROM media ORDER BY uploaded_at DESC LIMIT :limit OFFSET :offset");
    $media_stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $media_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $media_stmt->execute();
    $media_files = $media_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $media_files = [];
    $total_pages = 0;
    set_flash_message('Error fetching media files: ' . $e->getMessage(), 'danger');
}
?>

<!-- Page Header -->
<div class="page-header mb-4">
    <div class="row align-items-center">
        <div class="col">
            <h1 class="page-title">Media Library</h1>
        </div>
    </div>
</div>

<!-- AJAX Upload Zone -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="/admin/ajax_upload.php" method="POST" enctype="multipart/form-data" id="upload-form">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <div id="drop-zone" class="text-center p-5 border-2 border-dashed">
                <p>ফাইল এখানে টেনে আনুন অথবা ফাইল বাছাই করতে ক্লিক করুন</p>
                <input type="file" id="media-file-input" name="media_files[]" multiple class="d-none">
                <button type="button" id="browse-btn" class="btn btn-secondary">ফাইল ব্রাউজ করুন</button>
            </div>
            <div id="upload-progress-container" class="progress mt-3 d-none" style="height: 25px;">
                <div id="upload-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
            </div>
        </form>
    </div>
</div>

<!-- Media Controls: Search and Bulk Actions -->
<div class="card shadow-sm mb-4">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div class="media-actions-left d-flex align-items-center gap-2">
            <button id="select-mode-btn" class="btn btn-outline-primary">
                <i class="bi bi-check2-square"></i> Select
            </button>
            <button id="delete-selected-btn" class="btn btn-danger d-none">
                <i class="bi bi-trash"></i> Delete Selected (<span id="selection-count">0</span>)
            </button>
        </div>
        <div class="media-actions-right" style="width: 300px;">
            <input type="text" id="media-search-input" class="form-control" placeholder="Search by filename...">
        </div>
    </div>
</div>


<!-- Media Gallery -->
<div id="media-gallery" class="row g-3">
    <?php if (empty($media_files)): ?>
        <div id="no-media-message" class="col-12">
            <div class="alert alert-info text-center">কোনো মিডিয়া ফাইল পাওয়া যায়নি। শুরু করার জন্য একটি আপলোড করুন!</div>
        </div>
    <?php else: ?>
        <?php foreach ($media_files as $file): ?>
            <div class="col-lg-2 col-md-3 col-sm-4 col-6 media-item-container" data-id="<?php echo e($file['id']); ?>">
                <div class="card h-100 shadow-sm media-card" data-id="<?php echo e($file['id']); ?>">
                    <div class="media-card-img-wrapper">
                        <img src="/<?php echo e($file['file_path']); ?>" class="card-img-top" loading="lazy" alt="<?php echo e($file['alt_text'] ?? $file['file_name']); ?>">
                        <div class="selection-overlay"><i class="bi bi-check-circle-fill"></i></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<div id="search-loading" class="text-center my-4 d-none">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>


<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <nav class="mt-4 d-flex justify-content-center" id="pagination-nav">
        <ul class="pagination">
            <?php if ($page > 1): ?>
                <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a></li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                 <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a></li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>

<!-- Media Details Modal -->
<div class="modal fade" id="mediaDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Media Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="media-details-content">
                <!-- Content will be loaded here via AJAX -->
                <div class="text-center"><div class="spinner-border"></div></div>
            </div>
        </div>
    </div>
</div>

<?php
// Page-specific scripts
$page_scripts = "
<style>
/* ... (আপনার আগের স্টাইলগুলো এখানে থাকবে) ... */
#drop-zone { border-color: #ccc; border-radius: 5px; cursor: pointer; transition: all 0.2s ease-in-out; }
#drop-zone.dragover { border-color: #0d6efd; background-color: #f0f8ff; }

.media-card { cursor: pointer; position: relative; overflow: hidden; transition: transform 0.2s; }
.media-card:hover { transform: scale(1.05); }

.media-card-img-wrapper { height: 150px; background-color: #f1f1f1; display: flex; align-items-center; justify-content: center; position: relative; }
.media-card .card-img-top { width: 100%; height: 100%; object-fit: cover; }

/* Selection Styles */
.selection-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 123, 255, 0.5);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    opacity: 0;
    transition: opacity 0.2s ease;
    pointer-events: none;
}
.media-card.selected .selection-overlay { opacity: 1; }
body.selection-mode .media-card:not(.selected):hover .selection-overlay {
    opacity: 0.5;
    background-color: rgba(0, 0, 0, 0.2);
}
</style>
<script>
    // Pass CSRF token to JavaScript
    const CSRF_TOKEN = '<?php echo generate_csrf_token(); ?>';
</script>
<script src=\"/admin/assets/js/media-library.js\"></script>
";

require_once 'includes/footer.php';
?>