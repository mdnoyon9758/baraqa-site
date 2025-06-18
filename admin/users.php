<?php
// Core application file is loaded first to handle sessions, db connection, and functions
require_once __DIR__ . '/../includes/app.php';

// Check if the admin is logged in. Use the function from your functions.php
require_login(); // This function should handle redirecting if not logged in.

// The admin header should be included after the core app and authentication check.
require_once 'includes/admin_header.php';

// Fetch all users from the database
// Note: We use $pdo from db_connect.php which is loaded by app.php
try {
    $stmt = $pdo->query("SELECT id, name, email, created_at, status FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle database errors gracefully
    $users = [];
    set_flash_message('Error fetching users: ' . $e->getMessage(), 'danger');
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">User Management</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Users</li>
    </ol>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            All Registered Users
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover" id="usersTable">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Registration Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users && count($users) > 0): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo e($user['name']); ?></td>
                                    <td><?php echo e($user['email']); ?></td>
                                    <td><?php echo date('d M, Y H:i', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <?php if (isset($user['status']) && $user['status'] === 'suspended'): ?>
                                            <span class="badge bg-warning text-dark">Suspended</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="view_user.php?id=<?php echo $user['id']; ?>" class="btn btn-info btn-sm" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (isset($user['status']) && $user['status'] === 'suspended'): ?>
                                            <a href="user_action.php?action=activate&id=<?php echo $user['id']; ?>" class="btn btn-success btn-sm" title="Activate User">
                                                <i class="fas fa-check-circle"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="user_action.php?action=suspend&id=<?php echo $user['id']; ?>" class="btn btn-warning btn-sm" title="Suspend User">
                                                <i class="fas fa-user-slash"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="user_action.php?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');" title="Delete User">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No users found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Admin footer
require_once 'includes/admin_footer.php'; 
?>