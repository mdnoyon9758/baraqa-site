<?php
// File: auth/register.php
require_once __DIR__ . '/../includes/app.php';

if (is_user_logged_in()) {
    header('Location: /');
    exit;
}

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name)) { $errors[] = "Full Name is required."; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "A valid email is required."; }
    if (strlen($password) < 8) { $errors[] = "Password must be at least 8 characters long."; }
    if ($password !== $confirm_password) { $errors[] = "Passwords do not match."; }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                $errors[] = "An account with this email already exists.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_insert = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
                $stmt_insert->execute(['name' => $name, 'email' => $email, 'password' => $hashed_password]);
                
                $_SESSION['success_message'] = "Registration successful! You can now log in.";
                header('Location: /auth/login.php');
                exit();
            }
        } catch (PDOException $e) {
            $errors[] = "Database error. Please try again.";
            error_log("User registration error: " . $e->getMessage());
        }
    }
}

$page_title = "Create an Account";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container my-5" style="max-width: 500px;">
    <div class="card shadow-sm">
        <div class="card-body p-5">
            <h1 class="text-center h3 mb-4">Create Your Account</h1>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): echo "<p class='mb-0'>" . e($error) . "</p>"; endforeach; ?>
                </div>
            <?php endif; ?>
            <form action="/auth/register.php" method="POST">
                <div class="mb-3">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo e($name); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo e($email); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Create Account</button>
                </div>
            </form>
            <hr>
            <div class="text-center">
                <p class="mb-0">Already have an account? <a href="/auth/login.php">Log In</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>