<?php
// We only need functions.php which also includes db_connect.php
require_once __DIR__ . '/../includes/functions.php';

// Check if admin registration is allowed from site settings
if (get_setting('allow_admin_registration') !== '1') {
    $_SESSION['error_message'] = 'Admin registration is currently disabled by the site administrator.';
    header('Location: login.php');
    exit;
}

// If user is already logged in, redirect to dashboard
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // 2. Sanitize and validate inputs
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($name)) {
            $errors[] = "Full Name is required.";
        }
        if (empty($email)) {
            $errors[] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }
        if (empty($password)) {
            $errors[] = "Password is required.";
        } elseif (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        }
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }

        // 3. Check if email already exists (if no validation errors)
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = :email");
                $stmt->execute(['email' => $email]);
                if ($stmt->fetch()) {
                    $errors[] = "An account with this email already exists.";
                }
            } catch (PDOException $e) {
                $errors[] = "Database check failed.";
                error_log("Registration check error: " . $e->getMessage());
            }
        }
        
        // 4. Insert new admin into database if everything is okay
        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                $stmt = $pdo->prepare("INSERT INTO admins (name, email, password) VALUES (:name, :email, :password)");
                $stmt->execute([
                    'name' => $name,
                    'email' => $email,
                    'password' => $hashed_password
                ]);
                
                $_SESSION['success_message'] = "Registration successful! You can now log in.";
                header('Location: login.php');
                exit();
            } catch (PDOException $e) {
                $errors[] = "Could not create account. Please try again later.";
                error_log("Registration insert error: " . $e->getMessage());
            }
        }
    }
}

// Generate a new CSRF token for the form
$csrf_token = generate_csrf_token();
$site_title = get_setting('site_name') ?? 'BARAQA Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - <?php echo e($site_title); ?></title>
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
        .register-card {
            max-width: 450px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="card register-card shadow-lg border-0 my-5">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <h1 class="h4 text-gray-900">Create an Account!</h1>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">

                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="name" name="name" placeholder="John Doe" value="<?php echo e($name); ?>" required>
                    <label for="name"><i class="fas fa-user me-2"></i>Full Name</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" value="<?php echo e($email); ?>" required>
                    <label for="email"><i class="fas fa-envelope me-2"></i>Email address</label>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-floating">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                            <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                         <div class="form-floating">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                            <label for="confirm_password"><i class="fas fa-lock me-2"></i>Confirm</label>
                        </div>
                    </div>
                </div>

                <div class="d-grid mt-2">
                    <button type="submit" class="btn btn-primary btn-lg">Register Account</button>
                </div>
            </form>
            <hr>
            <div class="text-center">
                <a class="small" href="login.php">Already have an account? Login!</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>