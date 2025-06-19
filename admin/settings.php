<?php
// =================================================================
// 1. PAGE CONFIGURATION AND CORE SETUP
// =================================================================
$page_key = 'general_settings'; // Corresponds to the key in the sidebar menu
$page_title = 'Site Settings';

require_once __DIR__ . '/../includes/app.php';
require_login();

// =================================================================
// 2. HANDLE POST REQUESTS (SETTINGS UPDATE)
// =================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_flash_message('CSRF token mismatch. Action aborted.', 'danger');
        header('Location: /admin/settings.php');
        exit;
    }

    try {
        $pdo->beginTransaction();
        
        // This array holds all checkbox keys. If a key is in this array but not in the POST data, its value is 0.
        $all_checkboxes = ['allow_user_registration', 'show_featured_section']; 

        $settings_to_update = $_POST['settings'] ?? [];

        foreach ($all_checkboxes as $checkbox_key) {
            if (!isset($settings_to_update[$checkbox_key])) {
                $settings_to_update[$checkbox_key] = 0; // Set to 0 if unchecked
            }
        }

        foreach ($settings_to_update as $key => $value) {
            $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = :value WHERE setting_key = :key");
            $stmt->execute(['value' => trim($value), 'key' => $key]);
        }
        
        $pdo->commit();
        set_flash_message('Settings updated successfully!', 'success');

    } catch (PDOException $e) {
        $pdo->rollBack();
        set_flash_message('Database error: ' . $e->getMessage(), 'danger');
    }

    header('Location: /admin/settings.php');
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
function get_s($key, $default = '') {
    global $settings;
    return isset($settings[$key]) ? e($settings[$key]) : $default;
}
?>

<!-- Page Header -->
<div class="page-header mb-4">
    <h1 class="page-title">Site Settings</h1>
</div>

<!-- Tab-based Navigation for Settings -->
<ul class="nav nav-tabs" id="settingsTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general-tab-pane" type="button" role="tab">General</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="appearance-tab" data-bs-toggle="tab" data-bs-target="#appearance-tab-pane" type="button" role="tab">Appearance</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="api-tab" data-bs-toggle="tab" data-bs-target="#api-tab-pane" type="button" role="tab">API Keys</button>
    </li>
</ul>

<form action="/admin/settings.php" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="tab-content" id="settingsTabsContent">
                <!-- General Settings Tab -->
                <div class="tab-pane fade show active" id="general-tab-pane" role="tabpanel">
                    <h5 class="mb-4">General Information</h5>
                    <div class="row mb-3">
                        <label for="site_name" class="col-sm-3 col-form-label">Site Name</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="site_name" name="settings[site_name]" value="<?php echo get_s('site_name'); ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="site_tagline" class="col-sm-3 col-form-label">Tagline</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="site_tagline" name="settings[site_tagline]" value="<?php echo get_s('site_tagline'); ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="admin_email" class="col-sm-3 col-form-label">Admin Email</label>
                        <div class="col-sm-9">
                            <input type="email" class="form-control" id="admin_email" name="settings[admin_email]" value="<?php echo get_s('admin_email'); ?>">
                        </div>
                    </div>
                    <hr class="my-4">
                    <h5 class="mb-4">User Settings</h5>
                    <div class="row mb-3">
                        <label for="allow_user_registration" class="col-sm-3 col-form-label">User Registration</label>
                        <div class="col-sm-9">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="allow_user_registration" name="settings[allow_user_registration]" value="1" <?php echo get_s('allow_user_registration') == 1 ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="allow_user_registration">Allow new users to register</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Appearance Settings Tab -->
                <div class="tab-pane fade" id="appearance-tab-pane" role="tabpanel">
                    <h5 class="mb-4">Logos and Branding</h5>
                    <div class="row mb-3">
                        <label for="site_logo_url" class="col-sm-3 col-form-label">Site Logo URL</label>
                        <div class="col-sm-9">
                            <input type="url" class="form-control" id="site_logo_url" name="settings[site_logo_url]" value="<?php echo get_s('site_logo_url'); ?>" placeholder="https://example.com/logo.png">
                        </div>
                    </div>
                     <div class="row mb-3">
                        <label for="site_favicon_url" class="col-sm-3 col-form-label">Favicon URL</label>
                        <div class="col-sm-9">
                            <input type="url" class="form-control" id="site_favicon_url" name="settings[site_favicon_url]" value="<?php echo get_s('site_favicon_url'); ?>" placeholder="https://example.com/favicon.ico">
                        </div>
                    </div>
                    <hr class="my-4">
                    <h5 class="mb-4">Homepage Settings</h5>
                     <div class="row mb-3">
                        <label for="show_featured_section" class="col-sm-3 col-form-label">Featured Section</label>
                        <div class="col-sm-9">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="show_featured_section" name="settings[show_featured_section]" value="1" <?php echo get_s('show_featured_section') == 1 ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="show_featured_section">Show featured brands/products section on homepage</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- API Settings Tab -->
                <div class="tab-pane fade" id="api-tab-pane" role="tabpanel">
                    <h5 class="mb-4">Affiliate API Credentials</h5>
                    <div class="alert alert-warning">Please be careful with these keys. Do not share them.</div>
                    <div class="row mb-3">
                        <label for="amazon_affiliate_id" class="col-sm-3 col-form-label">Amazon Associate Tag</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="amazon_affiliate_id" name="settings[amazon_affiliate_id]" value="<?php echo get_s('amazon_affiliate_id'); ?>">
                        </div>
                    </div>
                     <div class="row mb-3">
                        <label for="amazon_access_key" class="col-sm-3 col-form-label">Amazon Access Key</label>
                        <div class="col-sm-9">
                            <input type="password" class="form-control" id="amazon_access_key" name="settings[amazon_access_key]" value="<?php echo get_s('amazon_access_key'); ?>">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="amazon_secret_key" class="col-sm-3 col-form-label">Amazon Secret Key</label>
                        <div class="col-sm-9">
                            <input type="password" class="form-control" id="amazon_secret_key" name="settings[amazon_secret_key]" value="<?php echo get_s('amazon_secret_key'); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary">Save All Settings</button>
        </div>
    </div>
</form>

<?php
require_once 'includes/footer.php';
?>