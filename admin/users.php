<?php
session_start();
// আপনার প্রজেক্টের পাথ অনুযায়ী সঠিক ফাইল include করুন
require_once '../includes/db_connect.php'; 

// অ্যাডমিন লগইন করা আছে কিনা এবং তার পারমিশন আছে কিনা, সেই চেক এখানে যোগ করতে হবে
// উদাহরণস্বরূপ:
/*
if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit();
}
*/

// অ্যাডমিন প্যানেলের হেডার include করা হচ্ছে
require_once 'includes/admin_header.php';

// ডাটাবেস থেকে সকল ব্যবহারকারীর তথ্য আনা হচ্ছে
// created_at কলামটি users টেবিলে আছে বলে ধরে নেওয়া হচ্ছে
$query = "SELECT id, name, email, created_at, status FROM users ORDER BY created_at DESC";
$result = pg_query($db_conn, $query);
$users = pg_fetch_all($result);

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
            <!-- ডেটাটেবিল (DataTables) ব্যবহার করলে সার্চ, সর্টিং এবং প্যাজিনেশন স্বয়ংক্রিয়ভাবে যোগ হবে -->
            <!-- ডেটাটেবিল চালু করার জন্য টেবিলকে একটি আইডি দিন, যেমন id="usersTable" -->
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
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
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
// অ্যাডমিন প্যানেলের ফুটার include করা হচ্ছে
require_once 'includes/admin_footer.php'; 
?>

<!-- DataTables JS (যদি আপনার টেমপ্লেটে না থাকে) -->
<!-- আপনার admin_footer.php-তে এই স্ক্রিপ্টগুলো যোগ করতে পারেন -->
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" crossorigin="anonymous"></script>
<script>
    window.addEventListener('DOMContentLoaded', event => {
        const usersTable = document.getElementById('usersTable');
        if (usersTable) {
            new simpleDatatables.DataTable(usersTable);
        }
    });
</script>