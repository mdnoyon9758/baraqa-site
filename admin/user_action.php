<?php
// Core application file is loaded first
require_once __DIR__ . '/../includes/app.php';

// --- Step 1: Security Checks ---

// Ensure an admin is logged in
require_login();

// --- Step 2: Get and Validate Request Parameters ---

$action = $_GET['action'] ?? null;
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$action || $user_id <= 0) {
    set_flash_message('Invalid request. Please try again.', 'danger');
    header('Location: users.php');
    exit();
}

// Prevent admin from taking action on their own account
if ($user_id === $_SESSION['admin_id']) {
    set_flash_message('You cannot perform this action on your own account.', 'warning');
    header('Location: users.php');
    exit();
}

// --- Step 3: Perform Action based on Request ---

try {
    $sql = '';
    $params = [$user_id];

    switch ($action) {
        case 'suspend':
            $sql = "UPDATE users SET status = 'suspended' WHERE id = ?";
            set_flash_message('User has been suspended successfully.', 'success');
            break;

        case 'activate':
            $sql = "UPDATE users SET status = 'active' WHERE id = ?";
            set_flash_message('User has been activated successfully.', 'success');
            break;

        case 'delete':
            $sql = "DELETE FROM users WHERE id = ?";
            set_flash_message('User has been permanently deleted.', 'success');
            break;

        default:
            set_flash_message('Invalid action specified.', 'danger');
            header('Location: users.php');
            exit();
    }
    
    // Execute the query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

} catch (PDOException $e) {
    set_flash_message('An error occurred: ' . $e->getMessage(), 'danger');
}

// --- Step 4: Redirect back to the users list ---
header('Location: users.php');
exit();
?>