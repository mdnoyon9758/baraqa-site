<?php
// =================================================================
// 1. PAGE CONFIGURATION AND CORE SETUP
// =================================================================
$page_key = 'media';
$page_title = 'Media Library';

require_once __DIR__ . '/../includes/app.php';
require_login();

// NOTE: The POST handling for file uploads will now be handled by ajax_upload.php
// This keeps the main media.php file clean and focused on displaying the library.

// =================================================================
// 3. DATA FETCHING AND VIEW RENDERING (GET REQUEST)
// =================================================================
require_once 'includes/header.php';

try {
    // Fetch media files with pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 18;
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
                <p>Drag & drop files here or click to select files</p>
                <input type="file" id="media-file-input" name="media_files[]" multiple class="d-none">
                <button type="button" id="browse-btn" class="btn btn-secondary">Browse Files</button>
            </div>
            <div class="progress mt-3 d-none">
                <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
            </div>
        </form>
    </div>
</div>

<!-- Media Gallery -->
<div id="media-gallery" class="row g-3">
    <?php if (empty($media_files)): ?>
        <div id="no-media-message" class="col-12">
            <div class="alert alert-info text-center">No media files found. Upload one to get started!</div>
        </div>
    <?php else: ?>
        <?php foreach ($media_files as $file): ?>
            <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                <div class="card h-100 shadow-sm media-card" data-id="<?php echo e($file['id']); ?>">
                    <div class="media-card-img-wrapper">
                        <img src="<?php echo e($file['file_path']); ?>" class="card-img-top" loading="lazy" alt="<?php echo e($file['alt_text'] ?? $file['file_name']); ?>">
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <nav class="mt-4 d-flex justify-content-center">
        <ul class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<!-- Media Details Modal -->
<div class="modal fade" id="mediaDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Media Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
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
#drop-zone { border-color: #ccc; border-radius: 5px; cursor: pointer; }
#drop-zone.dragover { border-color: #0d6efd; background-color: #f0f8ff; }
.media-card { cursor: pointer; position: relative; overflow: hidden; }
.media-card-img-wrapper { height: 150px; background-color: #f1f1f1; display: flex; align-items-center; justify-content: center; }
.media-card .card-img-top { width: 100%; height: 100%; object-fit: cover; }
</style>
<script src=\"/admin/assets/js/media-library.js\"></script>
";

require_once 'includes/footer.php';
?>