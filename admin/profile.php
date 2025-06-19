<?php
// =================================================================
// 1. PAGE CONFIGURATION AND CORE SETUP
// =================================================================
$page_key = 'profile'; // A unique key for this page
$page_title = 'My Profile';

require_once __DIR__ . '/../includes/app.php';
require_login();

// =================================================================
// 2. DATA FETCHING AND FORM PROCESSING
// =================================================================
$admin_id = $_SESSION['admin_id'];

// Fetch current admin information
try {
    $stmt = $pdo->prepare("SELECT name, email FROM admins WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();
    if (!$admin) {
        set_flash_message('Could not find your profile. Please log in again.', 'danger');
        header('Location: /admin/logout.php'); // Log out if user is not found
        exit;
    }
} catch (PDOException $e) {
    set_flash_message('Database error: Could not load your profile.', 'danger');
    $admin = ['name' => 'Error Loading', 'email' => 'error@example.com'];
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_flash_message('CSRF token mismatch. Action aborted.', 'danger');
    } else {
        // Handle Profile Information Update
        if (isset($_POST['update_profile'])) {
            $name = trim($_POST['name']);
            $email = trim($_POST['email']);
            // ... [Your existing, excellent validation and update logic for profile] ...
            // Using set_flash_message instead of direct session assignment
            $_SESSION['admin_name'] = $name;
            set_flash_message("Profile updated successfully!", "success");
            header('Location: /admin/profile.php');
            exit;
        }

        // Handle Password Change
        if (isset($_POST['change_password'])) {
            // ... [Your existing, secure validation and update logic for password] ...
            // Using set_flash_message instead of direct session assignment
            set_flash_message("Password changed successfully!", "success");
            header('Location: /admin/profile.php');
            exit;
        }
    }
    // Redirect if there were errors to show flash messages
    header('Location: /admin/profile.php');
    exit;
}

// Generate Gravatar URL for the profile picture
$gravatar_hash = md5(strtolower(trim($admin['email'])));
$gravatar_url = "https://www.gravatar.com/avatar/{$gravatar_hash}?s=120&d=mp";

// =================================================================
// 3. RENDER THE VIEW
// =================================================================
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header mb-4">
    <h1 class="page-title"><?php echo e($page_title); ?></h1>
</div>

<div class="row">
    <!-- Edit Profile Card -->
    <div class="col-lg-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold">Edit Profile Information</h6></div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <img src="<?php echo $gravatar_url; ?>" alt="Profile Picture" class="rounded-circle img-thumbnail">
                </div>
                <form action="/admin/profile.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo e($admin['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo e($admin['email']); ?>" required>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password Card -->
    <div class="col-lg-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header"><h6 class="m-0 font-weight-bold">Change Password</h6></div>
            <div class="card-body">
                <form action="/admin/profile.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                        <small class="form-text text-muted">Must be at least 8 characters long.</small>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-warning">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>