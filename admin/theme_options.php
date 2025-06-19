<?php
// =================================================================
// 1. PAGE CONFIGURATION AND CORE SETUP
// =================================================================
$page_key = 'theme_options';
$page_title = 'Theme Options';

require_once __DIR__ . '/../includes/app.php';
require_login();

// =================================================================
// 2. HANDLE POST REQUESTS (SETTINGS UPDATE)
// =================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_flash_message('CSRF token mismatch. Action aborted.', 'danger');
        header('Location: /admin/theme_options.php');
        exit;
    }

    try {
        $pdo->beginTransaction();
        
        $settings_to_update = $_POST['settings'] ?? [];

        foreach ($settings_to_update as $key => $value) {
            // Sanitize color codes
            if (strpos($key, '_color') !== false && !preg_match('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $value)) {
                $value = '#000000'; // Default to black if invalid hex code
            }
            
            $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = :value WHERE setting_key = :key");
            $stmt->execute(['value' => trim($value), 'key' => $key]);
        }
        
        $pdo->commit();
        set_flash_message('Theme options updated successfully!', 'success');

    } catch (PDOException $e) {
        $pdo->rollBack();
        set_flash_message('Database error: ' . $e->getMessage(), 'danger');
    }

    header('Location: /admin/theme_options.php');
    exit;
}

// =================================================================
// 3. PREPARE AND RENDER THE VIEW
// =================================================================
require_once 'includes/header.php';

// Fetch all settings and organize them into an associative array for easy access
$settings_query = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$settings = $settings_query->fetchAll(PDO::FETCH_KEY_PAIR);

// Helper function to get a setting value safely
function get_theme_s($key, $default = '') {
    global $settings;
    return isset($settings[$key]) ? e($settings[$key]) : $default;
}
?>

<!-- Page Header -->
<div class="page-header mb-4">
    <h1 class="page-title">Theme Options</h1>
</div>

<form action="/admin/theme_options.php" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
    <div class="row">
        <!-- Left Column: Color Scheme & Fonts -->
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header"><h6 class="m-0 font-weight-bold">Color Scheme</h6></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="primary_color" class="form-label">Primary Color</label>
                            <input type="color" class="form-control form-control-color" id="primary_color" name="settings[primary_color]" value="<?php echo get_theme_s('primary_color', '#0d6efd'); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="secondary_color" class="form-label">Secondary Color</label>
                            <input type="color" class="form-control form-control-color" id="secondary_color" name="settings[secondary_color]" value="<?php echo get_theme_s('secondary_color', '#6c757d'); ?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm">
                <div class="card-header"><h6 class="m-0 font-weight-bold">Custom CSS</h6></div>
                <div class="card-body">
                     <div class="mb-3">
                        <label for="custom_css" class="form-label">Custom CSS</label>
                        <textarea class="form-control" id="custom_css" name="settings[custom_css]" rows="10" placeholder="e.g., body { font-size: 16px; }"><?php echo get_theme_s('custom_css'); ?></textarea>
                        <small class="text-muted">Add your own custom CSS rules here. They will be loaded after the main stylesheet.</small>
                    </div>
                </div>
            </div>
        </div>
        <!-- Right Column: Save Button -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                 <div class="card-header"><h6 class="m-0 font-weight-bold">Actions</h6></div>
                 <div class="card-body">
                    <p class="text-muted">Remember to save your changes after modifying any options.</p>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Save All Theme Options</button>
                    </div>
                 </div>
            </div>
        </div>
    </div>
</form>

<?php
require_once 'includes/footer.php';
?>