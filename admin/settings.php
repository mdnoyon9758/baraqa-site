<?php
$page_title = "Site Settings";
require_once 'includes/auth.php'; // Handles session, auth, and CSRF token
require_once 'includes/admin_header.php';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'CSRF token mismatch. Action aborted.';
        header('Location: settings.php');
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Loop through all posted data
        foreach ($_POST['settings'] as $key => $value) {
            // Handle checkbox/toggle switch values
            // If a checkbox setting is not in POST, it means it was unchecked (value 0)
            if (isset($_POST['is_checkbox']) && in_array($key, $_POST['is_checkbox'])) {
                $value = isset($_POST['settings'][$key]) ? 1 : 0;
            }
            
            // For checkboxes that were not submitted (unchecked)
            if (isset($_POST['all_checkboxes']) && !isset($_POST['settings'][$key])) {
                 if (in_array($key, $_POST['all_checkboxes'])){
                    $value = 0;
                 }
            }

            $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = :value WHERE setting_key = :key");
            $stmt->execute(['value' => trim($value), 'key' => $key]);
        }
        
        $pdo->commit();
        $_SESSION['success_message'] = "Settings updated successfully!";

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
    }

    // Redirect to prevent form resubmission
    header('Location: settings.php');
    exit;
}

// Fetch all settings from the database to display in the form
$all_settings = $pdo->query("SELECT * FROM site_settings ORDER BY setting_key ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?php echo e($page_title); ?></h1>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">General Settings</h6>
    </div>
    <div class="card-body">
        <form action="settings.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
            
            <?php 
            $checkboxes = [];
            foreach ($all_settings as $setting): 
                $key = e($setting['setting_key']);
                $value = e($setting['setting_value']);
                $label = ucwords(str_replace('_', ' ', $key));
            ?>
                <div class="row mb-3">
                    <label for="<?php echo $key; ?>" class="col-sm-4 col-form-label"><?php echo $label; ?></label>
                    <div class="col-sm-8">
                        <?php if ($key === 'allow_admin_registration'): // Example of a toggle switch (checkbox) ?>
                            <div class="form-check form-switch">
                                <input type="hidden" name="all_checkboxes[]" value="<?php echo $key; ?>">
                                <input class="form-check-input" type="checkbox" role="switch" id="<?php echo $key; ?>" name="settings[<?php echo $key; ?>]" value="1" <?php echo ($value == 1) ? 'checked' : ''; ?>>
                            </div>
                            <small class="form-text text-muted">Allow new admins to register from the login page.</small>
                        <?php elseif (strpos($key, 'products_per_') !== false): // Example of a number input ?>
                            <input type="number" class="form-control" id="<?php echo $key; ?>" name="settings[<?php echo $key; ?>]" value="<?php echo $value; ?>">
                        <?php else: // Default to a text input ?>
                            <input type="text" class="form-control" id="<?php echo $key; ?>" name="settings[<?php echo $key; ?>]" value="<?php echo $value; ?>">
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="row">
                <div class="col-sm-8 offset-sm-4">
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </div>
        </form>
    </div>
</div>