<?php
// File: auth/login.php
require_once __DIR__ . '/../includes/app.php';

if (is_user_logged_in()) {
    header('Location: /');
    exit;
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "Invalid email format."; }
    if (empty($password)) { $errors[] = "Password is required."; }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                header('Location: /'); // Redirect to homepage
                exit();
            } else {
                $errors[] = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error. Please try again.";
            error_log("User login error: " . $e->getMessage());
        }
    }
}

$page_title = "Login to Your Account";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container my-5" style="max-width: 500px;">
    <div class="card shadow-sm">
        <div class="card-body p-5">
            <h1 class="text-center h3 mb-4">Welcome Back!</h1>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): echo "<p class='mb-0'>" . e($error) . "</p>"; endforeach; ?>
                </div>
            <?php endif; ?>
            <?php display_flash_messages(); ?>
            <form action="/auth/login.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo e($email); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>
            <hr>
            <div class="text-center">
                <p class="mb-0">Don't have an account? <a href="/auth/register.php">Create one</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>