<?php
session_start();
require_once '../includes/app.php';
// আপনার প্রজেক্টের পাথ অনুযায়ী সঠিক ফাইল include করুন
require_once '../includes/db_connect.php'; 
require_once '../includes/functions.php'; // e() এবং অন্যান্য ফাংশনের জন্য
require_once '../includes/header.php'; 

// ব্যবহারকারী লগইন করা আছে কিনা তা পরীক্ষা করা
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$update_message = '';
$password_message = '';

// ডাটাবেস থেকে বর্তমান ব্যবহারকারীর তথ্য আনা
$query = "SELECT name, email, password_hash FROM users WHERE id = $1";
$result = pg_query_params($db_conn, $query, array($user_id));
$user = pg_fetch_assoc($result);

if (!$user) {
    session_destroy();
    header('Location: /auth/login.php');
    exit();
}

// ---- প্রোফাইল তথ্য (নাম ও ইমেইল) আপডেট করার লজিক ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);

    if (empty($name) || empty($email)) {
        $update_message = '<div class="alert alert-danger">Name and Email cannot be empty.</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $update_message = '<div class="alert alert-danger">Invalid email format.</div>';
    } else {
        // ইমেইলটি অন্য কোনো ব্যবহারকারী ব্যবহার করছে কিনা তা পরীক্ষা করা
        $email_check_query = "SELECT id FROM users WHERE email = $1 AND id != $2";
        $email_check_result = pg_query_params($db_conn, $email_check_query, array($email, $user_id));

        if (pg_num_rows($email_check_result) > 0) {
            $update_message = '<div class="alert alert-danger">This email address is already in use by another account.</div>';
        } else {
            // ডাটাবেস আপডেট করা
            $update_query = "UPDATE users SET name = $1, email = $2 WHERE id = $3";
            $update_result = pg_query_params($db_conn, $update_query, array($name, $email, $user_id));

            if ($update_result) {
                $update_message = '<div class="alert alert-success">Profile updated successfully!</div>';
                // নতুন ডেটা দেখানোর জন্য user ভ্যারিয়েবল আপডেট করা
                $user['name'] = $name;
                $user['email'] = $email;
            } else {
                $update_message = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
            }
        }
    }
}

// ---- পাসওয়ার্ড পরিবর্তন করার লজিক ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // বর্তমান পাসওয়ার্ড সঠিক কিনা তা যাচাই করা
    if (!password_verify($current_password, $user['password_hash'])) {
        $password_message = '<div class="alert alert-danger">Your current password is incorrect.</div>';
    } elseif (strlen($new_password) < 8) {
        $password_message = '<div class="alert alert-danger">New password must be at least 8 characters long.</div>';
    } elseif ($new_password !== $confirm_password) {
        $password_message = '<div class="alert alert-danger">New password and confirmation do not match.</div>';
    } else {
        // নতুন পাসওয়ার্ড হ্যাশ করা
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        // ডাটাবেসে নতুন পাসওয়ার্ড আপডেট করা
        $password_update_query = "UPDATE users SET password_hash = $1 WHERE id = $2";
        $password_update_result = pg_query_params($db_conn, $password_update_query, array($new_password_hash, $user_id));

        if ($password_update_result) {
            $password_message = '<div class="alert alert-success">Password changed successfully!</div>';
        } else {
            $password_message = '<div class="alert alert-danger">An error occurred while changing the password.</div>';
        }
    }
}
?>

<main class="container my-5">
    <div class="row">
        <!-- বাম কলাম: সাইডবার মেনু -->
        <aside class="col-md-3 account-sidebar">
             <div class="list-group">
                <a href="/user/dashboard" class="list-group-item list-group-item-action">
                    <i class="fas fa-user-circle fa-fw me-2"></i>My Account
                </a>
                <a href="/user/orders" class="list-group-item list-group-item-action">
                    <i class="fas fa-box fa-fw me-2"></i>My Orders
                </a>
                <a href="/user/security" class="list-group-item list-group-item-action active" aria-current="true">
                    <i class="fas fa-shield-alt fa-fw me-2"></i>Login & Security
                </a>
                <a href="#" class="list-group-item list-group-item-action disabled" tabindex="-1" aria-disabled="true">
                    <i class="fas fa-map-marker-alt fa-fw me-2"></i>Your Addresses
                </a>
                <a href="/auth/logout.php" class="list-group-item list-group-item-action text-danger">
                    <i class="fas fa-sign-out-alt fa-fw me-2"></i>Logout
                </a>
            </div>
        </aside>

        <!-- ডান কলাম: ফর্ম কন্টেন্ট -->
        <section class="col-md-9">
            <h1 class="h3 mb-4">Login & Security</h1>

            <!-- প্রোফাইল তথ্য পরিবর্তনের কার্ড -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Edit Your Profile Information</h5>
                </div>
                <div class="card-body">
                    <?php echo $update_message; // সফল বা ব্যর্থ বার্তা দেখানোর জন্য ?>
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

            <!-- পাসওয়ার্ড পরিবর্তনের কার্ড -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Change Your Password</h5>
                </div>
                <div class="card-body">
                    <?php echo $password_message; // সফল বা ব্যর্থ বার্তা দেখানোর জন্য ?>
                    <form action="/user/security" method="POST">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                            <div class="form-text">Password must be at least 8 characters long.</div>
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
// সাইটের সাধারণ ফুটার include করা হচ্ছে
require_once '../includes/footer.php'; 
?>