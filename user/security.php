<?php
require_once __DIR__ . '/../includes/app.php';

if (!is_user_logged_in()) {
    header('Location: /auth/login');
    exit();
}

$page_title = 'Login & Security';
require_once __DIR__ . '/../includes/header.php';

$user_id = $_SESSION['user_id'];
$update_message = '';
$password_message = '';

try {
    $stmt = $pdo->prepare("SELECT name, email, password_hash FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header('Location: /auth/login');
        exit();
    }
} catch (PDOException $e) {
    die("Error fetching user data: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);

        if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $update_message = '<div class="alert alert-danger">Please provide a valid name and email.</div>';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $update_message = '<div class="alert alert-danger">This email address is already in use.</div>';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                if ($stmt->execute([$name, $email, $user_id])) {
                    set_flash_message('Profile updated successfully!', 'success');
                    header('Location: /user/security'); // Redirect to show flash message
                    exit();
                } else {
                    $update_message = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
                }
            }
        }
    }

    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (!password_verify($current_password, $user['password_hash'])) {
            $password_message = '<div class="alert alert-danger">Your current password is incorrect.</div>';
        } elseif (strlen($new_password) < 8) {
            $password_message = '<div class="alert alert-danger">New password must be at least 8 characters long.</div>';
        } elseif ($new_password !== $confirm_password) {
            $password_message = '<div class="alert alert-danger">New password and confirmation do not match.</div>';
        } else {
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            if ($stmt->execute([$new_password_hash, $user_id])) {
                set_flash_message('Password changed successfully!', 'success');
                header('Location: /user/security');
                exit();
            } else {
                $password_message = '<div class="alert alert-danger">An error occurred while changing the password.</div>';
            }
        }
    }
}
?>

<main class="container my-5">
    <div class="row">
        <aside class="col-md-3 account-sidebar">
            <div class="list-group">
                <a href="/user/dashboard" class="list-group-item list-group-item-action"><i class="fas fa-user-circle fa-fw me-2"></i>My Account</a>
                <a href="/user/orders" class="list-group-item list-group-item-action"><i class="fas fa-box fa-fw me-2"></i>My Orders</a>
                <a href="/user/security" class="list-group-item list-group-item-action active" aria-current="true"><i class="fas fa-shield-alt fa-fw me-2"></i>Login & Security</a>
                <a href="#" class="list-group-item list-group-item-action disabled" tabindex="-1" aria-disabled="true"><i class="fas fa-map-marker-alt fa-fw me-2"></i>Your Addresses</a>
                <a href="/auth/logout.php" class="list-group-item list-group-item-action text-danger"><i class="fas fa-sign-out-alt fa-fw me-2"></i>Logout</a>
            </div>
        </aside>

        <section class="col-md-9">
            <h1 class="h3 mb-4">Login & Security</h1>
            <?php display_flash_messages(); // This will display messages after redirect ?>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Edit Your Profile Information</h5></div>
                <div class="card-body">
                    <?php echo $update_message; ?>
                    <form action="/user/security" method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo e($user['name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo e($user['email']); ?>" required>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Change Your Password</h5></div>
                <div class="card-body">
                    <?php echo $password_message; ?>
                    <form action="/user/security" method="POST">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </section>
    </div>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php'; 
?>