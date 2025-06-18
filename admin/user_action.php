<?php
session_start();
// আপনার প্রজেক্টের পাথ অনুযায়ী সঠিক ফাইল include করুন
require_once '../includes/db_connect.php'; 
require_once '../includes/functions.php'; // display_flash_messages() ফাংশনের জন্য

// --- ধাপ ১: নিরাপত্তা যাচাই ---

// ১.১: অ্যাডমিন লগইন করা আছে কিনা তা পরীক্ষা করা
// আপনার auth.php বা সমতুল্য ফাইলে যদি অ্যাডমিন সেশন যাচাইয়ের ফাংশন থাকে, সেটি ব্যবহার করুন।
// উদাহরণ: if (!is_admin_logged_in()) { ... }
if (!isset($_SESSION['admin_id'])) { 
    // যদি অ্যাডমিন লগইন করা না থাকে, তাহলে কোনো অ্যাকশন না নিয়ে লগইন পেজে পাঠান
    header('Location: /admin/login.php');
    exit();
}

// ১.২: CSRF (Cross-Site Request Forgery) টোকেন যাচাই করা (ঐচ্ছিক কিন্তু অত্যন্ত প্রস্তাবিত)
// নিরাপত্তার জন্য, ফর্ম বা লিঙ্কে একটি টোকেন যোগ করা উচিত এবং এখানে তা যাচাই করা উচিত।
// আপাতত আমরা এই ধাপটি সরলতার জন্য বাদ দিচ্ছি, কিন্তু প্রোডাকশন সাইটের জন্য এটি আবশ্যক।

// --- ধাপ ২: রিকোয়েস্ট প্যারামিটার গ্রহণ ও যাচাই ---

// ২.১: অ্যাকশন এবং ইউজার আইডি গ্রহণ
$action = $_GET['action'] ?? null;
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ২.২: প্যারামিটারগুলো বৈধ কিনা তা পরীক্ষা করা
if (!$action || $user_id <= 0) {
    // যদি অ্যাকশন বা আইডি না থাকে, তাহলে একটি এরর মেসেজ সেট করে ইউজার পেজে ফেরত পাঠান
    set_flash_message('Invalid request. Please try again.', 'danger');
    header('Location: users.php');
    exit();
}

// অ্যাডমিন নিজেকে সাসপেন্ড বা ডিলিট করতে পারবে না
if ($user_id === $_SESSION['admin_id']) {
    set_flash_message('You cannot perform this action on your own account.', 'warning');
    header('Location: users.php');
    exit();
}

// --- ধাপ ৩: অ্যাকশন অনুযায়ী কাজ করা ---

try {
    switch ($action) {
        case 'suspend':
            // ব্যবহারকারীর স্ট্যাটাস 'suspended' করা
            $query = "UPDATE users SET status = 'suspended' WHERE id = $1";
            $result = pg_query_params($db_conn, $query, array($user_id));
            
            if ($result) {
                set_flash_message('User has been suspended successfully.', 'success');
            } else {
                throw new Exception('Failed to suspend user.');
            }
            break;

        case 'activate':
            // ব্যবহারকারীর স্ট্যাটাস 'active' করা
            // স্ট্যাটাস কলাম না থাকলে NULL বা অন্য কোনো ডিফল্ট মান সেট করতে পারেন
            $query = "UPDATE users SET status = 'active' WHERE id = $1";
            $result = pg_query_params($db_conn, $query, array($user_id));
            
            if ($result) {
                set_flash_message('User has been activated successfully.', 'success');
            } else {
                throw new Exception('Failed to activate user.');
            }
            break;

        case 'delete':
            // ব্যবহারকারীকে ডাটাবেস থেকে স্থায়ীভাবে ডিলিট করা
            // সতর্কতা: এটি একটি অপরিবর্তনীয় কাজ।
            $query = "DELETE FROM users WHERE id = $1";
            $result = pg_query_params($db_conn, $query, array($user_id));

            if ($result) {
                set_flash_message('User has been permanently deleted.', 'success');
            } else {
                // যদি ব্যবহারকারীর সাথে সম্পর্কিত ডেটা (যেমন অর্ডার) থাকে, তাহলে ডিলিট হতে সমস্যা হতে পারে (foreign key constraint)।
                throw new Exception('Failed to delete user. The user might have associated orders.');
            }
            break;

        default:
            // যদি কোনো অজানা অ্যাকশন আসে
            set_flash_message('Invalid action specified.', 'danger');
            break;
    }
} catch (Exception $e) {
    // যেকোনো ডাটাবেস বা অন্য কোনো এররের জন্য
    set_flash_message('An error occurred: ' . $e->getMessage(), 'danger');
}

// --- ধাপ ৪: ব্যবহারকারীকে আগের পেজে ফেরত পাঠানো ---
header('Location: users.php');
exit();

?>