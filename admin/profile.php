<?php
$page_title = "My Profile";
require_once 'includes/auth.php'; // 身份验证、CSRF 令牌等
require_once 'includes/admin_header.php';

$admin_id = $_SESSION['admin_id'];
$errors = [];
$success = '';

// 获取当前管理员信息
try {
    $stmt = $pdo->prepare("SELECT name, email FROM admins WHERE id = :id");
    $stmt->execute(['id' => $admin_id]);
    $admin = $stmt->fetch();

    if (!$admin) {
        // 如果找不到管理员，这通常是一个严重错误，可能是会话被篡改
        session_destroy();
        header('Location: login.php');
        exit;
    }
} catch (PDOException $e) {
    // 数据库错误，显示错误信息
    $_SESSION['error_message'] = "无法加载您的个人资料，请稍后重试。";
    $admin = ['name' => 'Error', 'email' => 'Error'];
}


// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证 CSRF 令牌
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'CSRF 令牌不匹配，操作已中止。';
        header('Location: profile.php');
        exit;
    }

    // --- 更新个人信息 ---
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);

        if (empty($name) || empty($email)) {
            $errors[] = "姓名和邮箱不能为空。";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "邮箱格式无效。";
        } else {
            // 检查邮箱是否已被其他管理员使用
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = :email AND id != :id");
            $stmt->execute(['email' => $email, 'id' => $admin_id]);
            if ($stmt->fetch()) {
                $errors[] = "此邮箱已被其他账户注册。";
            }
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("UPDATE admins SET name = :name, email = :email WHERE id = :id");
                $stmt->execute(['name' => $name, 'email' => $email, 'id' => $admin_id]);
                $_SESSION['admin_name'] = $name; // 更新会话中的姓名
                $_SESSION['success_message'] = "个人资料更新成功！";
                header('Location: profile.php');
                exit;
            } catch (PDOException $e) {
                $errors[] = "数据库错误，无法更新个人资料。";
            }
        }
    }

    // --- 更改密码 ---
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $errors[] = "所有密码字段均为必填项。";
        } elseif (strlen($new_password) < 8) {
            $errors[] = "新密码必须至少为8个字符。";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "新密码与确认密码不匹配。";
        } else {
            // 验证当前密码
            $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = :id");
            $stmt->execute(['id' => $admin_id]);
            $admin_password_hash = $stmt->fetchColumn();

            if (password_verify($current_password, $admin_password_hash)) {
                // 当前密码正确，更新为新密码
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt_update = $pdo->prepare("UPDATE admins SET password = :password WHERE id = :id");
                $stmt_update->execute(['password' => $new_password_hash, 'id' => $admin_id]);
                $_SESSION['success_message'] = "密码已成功更改！";
                header('Location: profile.php');
                exit;
            } else {
                $errors[] = "当前密码不正确。";
            }
        }
    }
    // 如果有错误，将它们存入会话中以便刷新后显示
    if (!empty($errors)) {
        $_SESSION['error_message'] = implode('<br>', $errors);
        header('Location: profile.php');
        exit;
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?php echo e($page_title); ?></h1>
</div>

<div class="row">

    <!-- 更新个人信息卡片 -->
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">编辑个人信息</h6>
            </div>
            <div class="card-body">
                <form action="profile.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">姓名</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo e($admin['name']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">邮箱地址</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo e($admin['email']); ?>" required>
                    </div>

                    <button type="submit" name="update_profile" class="btn btn-primary">保存更改</button>
                </form>
            </div>
        </div>
    </div>

    <!-- 更改密码卡片 -->
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">更改密码</h6>
            </div>
            <div class="card-body">
                <form action="profile.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">当前密码</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">新密码 (至少8个字符)</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">确认新密码</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>

                    <button type="submit" name="change_password" class="btn btn-warning">更新密码</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>