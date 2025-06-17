<?php
// We only need functions.php which also includes db_connect.php
// functions.php will also start the session.
require_once __DIR__ . '/../includes/functions.php';

// Define admin base URL for consistent redirects and links
$admin_base_url = '/admin/';

// If user is already logged in, redirect them to the dashboard
if (is_logged_in()) {
    // CHANGED: Using absolute path for redirect
    header('Location: ' . $admin_base_url . 'dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Email and password are required.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = :email");
                $stmt->execute(['email' => $email]);
                $admin = $stmt->fetch();

                if ($admin && password_verify($password, $admin['password'])) {
                    session_regenerate_id(true);

                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_name'] = $admin['name'];
                    
                    log_admin_activity('Admin logged in successfully.');
                    
                    // CHANGED: Using absolute path for redirect
                    header('Location: ' . $admin_base_url . 'dashboard.php');
                    exit();
                } else {
                    log_admin_activity("Failed login attempt for email: {$email}");
                    $error = 'Invalid email or password.';
                }
            } catch (PDOException $e) {
                $error = 'A database error occurred. Please try again later.';
                error_log("Login error: " . $e->getMessage());
            }
        }
    }
}

$csrf_token = generate_csrf_token();
$site_title = get_setting('site_name') ?? 'BARAQA Admin';
$allow_registration = get_setting('allow_admin_registration');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo e($site_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #f8f9fc;
        }
        .login-card {
            max-width: 450px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="card login-card shadow-lg border-0 my-5">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <h1 class="h4 text-gray-900">Welcome Back!</h1>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger text-center"><?php echo e($error); ?></div>
            <?php endif; ?>
            
            <?php display_flash_messages(); ?>

            <form action="<?php echo $admin_base_url; ?>login.php" method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">

                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
                    <label for="email"><i class="fas fa-envelope me-2"></i>Email Address</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">Login</button>
                </div>
            </form>
            <hr>
            <div class="text-center">
                <?php if ($allow_registration === '1'): ?>
                    <!-- CHANGED: Using absolute path for link -->
                    <a class="small" href="<?php echo $admin_base_url; ?>register.php">Create an Account!</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>