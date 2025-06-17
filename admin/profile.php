<?php
$page_title = "My Profile";
require_once 'includes/auth.php'; // Handles authentication, CSRF token etc.

// Define the absolute URL for this page for consistent redirects and form actions
$profile_page_url = '/admin/profile.php';
$login_page_url = '/admin/login.php';

$admin_id = $_SESSION['admin_id'];
$profile_errors = [];
$password_errors = [];

// --- Fetch current admin information ---
try {
    $stmt = $pdo->prepare("SELECT name, email FROM admins WHERE id = :id");
    $stmt->execute(['id' => $admin_id]);
    $admin = $stmt->fetch();

    if (!$admin) {
        // If admin is not found, this is a serious error, possibly a tampered session
        session_destroy();
        header('Location: ' . $login_page_url);
        exit;
    }
} catch (PDOException $e) {
    // Database error, show a message via session
    $_SESSION['error_message'] = "Could not load your profile. Please try again later.";
    error_log("Profile load error: " . $e->getMessage());
    $admin = ['name' => 'Error Loading', 'email' => 'Error Loading'];
}

// --- Process form submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'CSRF token mismatch. Action aborted.';
        header('Location: ' . $profile_page_url);
        exit;
    }

    // --- Handle Profile Information Update ---
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);

        if (empty($name) || empty($email)) {
            $profile_errors[] = "Name and Email cannot be empty.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $profile_errors[] = "Invalid email format.";
        } else {
            // Check if the email is already used by another admin
            $stmt_check_email = $pdo->prepare("SELECT id FROM admins WHERE email = :email AND id != :id");
            $stmt_check_email->execute(['email' => $email, 'id' => $admin_id]);
            if ($stmt_check_email->fetch()) {
                $profile_errors[] = "This email is already registered to another account.";
            }
        }

        if (empty($profile_errors)) {
            try {
                $stmt_update = $pdo->prepare("UPDATE admins SET name = :name, email = :email WHERE id = :id");
                $stmt_update->execute(['name' => $name, 'email' => $email, 'id' => $admin_id]);
                
                $_SESSION['admin_name'] = $name; // Update the name in the session
                $_SESSION['success_message'] = "Profile updated successfully!";
                log_admin_activity("Updated own profile information.");
                header('Location: ' . $profile_page_url);
                exit;
            } catch (PDOException $e) {
                $profile_errors[] = "Database error: Could not update profile.";
                error_log("Profile update error: " . $e->getMessage());
            }
        }
    }

    // --- Handle Password Change ---
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $password_errors[] = "All password fields are required.";
        } elseif (strlen($new_password) < 8) {
            $password_errors[] = "New password must be at least 8 characters long.";
        } elseif ($new_password !== $confirm_password) {
            $password_errors[] = "The new password and confirmation do not match.";
        } else {
            // Verify the current password
            $stmt_pass = $pdo->prepare("SELECT password FROM admins WHERE id = :id");
            $stmt_pass->execute(['id' => $admin_id]);
            $admin_password_hash = $stmt_pass->fetchColumn();

            if (password_verify($current_password, $admin_password_hash)) {
                // Current password is correct, update to the new one
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt_update_pass = $pdo->prepare("UPDATE admins SET password = :password WHERE id = :id");
                $stmt_update_pass->execute(['password' => $new_password_hash, 'id' => $admin_id]);
                
                $_SESSION['success_message'] = "Password changed successfully!";
                log_admin_activity("Changed own password.");
                header('Location: ' . $profile_page_url);
                exit;
            } else {
                $password_errors[] = "Incorrect current password.";
            }
        }
    }
}

// Generate Gravatar URL for the profile picture
$gravatar_email = md5(strtolower(trim($admin['email'])));
$gravatar_url = "https://www.gravatar.com/avatar/{$gravatar_email}?s=120&d=mp&r=g";

// We can now include the header as all logic/redirects are done
require_once 'includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?php echo e($page_title); ?></h1>
</div>

<div class="row">
    <!-- Edit Profile Card -->
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Edit Profile Information</h6></div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <img src="<?php echo $gravatar_url; ?>" alt="Profile Picture" class="rounded-circle img-thumbnail">
                </div>

                <?php if (!empty($profile_errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($profile_errors as $error): echo e($error) . '<br>'; endforeach; ?>
                    </div>
                <?php endif; ?>

                <form action="<?php echo $profile_page_url; ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
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
        <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Change Password</h6></div>
            <div class="card-body">
                <?php if (!empty($password_errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($password_errors as $error): echo e($error) . '<br>'; endforeach; ?>
                    </div>
                <?php endif; ?>

                <form action="<?php echo $profile_page_url; ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password (min. 8 characters)</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
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

<?php require_once 'includes/admin_footer.php'; ?>