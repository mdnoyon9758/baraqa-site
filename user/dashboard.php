<?php
session_start();
require_once '../includes/app.php';
// আপনার প্রজেক্টের পাথ অনুযায়ী সঠিক ফাইল include করুন
require_once '../includes/db_connect.php'; 
require_once '../includes/header.php'; 

// ধাপ ১: ব্যবহারকারী লগইন করা আছে কিনা তা পরীক্ষা করা
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// ধাপ ২: ডাটাবেস থেকে বর্তমান ব্যবহারকারীর নাম আনা
$query = "SELECT name FROM users WHERE id = $1";
$result = pg_query_params($db_conn, $query, array($user_id));
$user = pg_fetch_assoc($result);

if (!$user) {
    session_destroy();
    header('Location: /auth/login.php');
    exit();
}

$user_name = htmlspecialchars($user['name']);
?>

<!-- ড্যাশবোর্ডের জন্য কাস্টম স্টাইল (Bootstrap এর সাথে সামঞ্জস্যপূর্ণ) -->
<style>
    .account-hub-card {
        border: 1px solid #dee2e6; /* Bootstrap-এর ডিফল্ট বর্ডার কালার */
        border-radius: 0.375rem; /* Bootstrap-এর ডিফল্ট বর্ডার রেডিয়াস */
        padding: 1.5rem;
        height: 100%;
        transition: box-shadow 0.2s ease-in-out, transform 0.2s ease;
        background-color: #fff;
    }
    .account-hub-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); /* Bootstrap-এর box-shadow */
    }
    .account-hub a {
        text-decoration: none;
        color: inherit;
    }
    .account-hub a.disabled-link {
        pointer-events: none; /* নিষ্ক্রিয় লিঙ্কে ক্লিক করা যাবে না */
        opacity: 0.65;
    }
    .account-hub-card .icon {
        font-size: 2.5rem;
        color: #495057; /* Bootstrap-এর সেকেন্ডারি টেক্সট কালার */
    }
    .account-hub-card h5 {
        font-size: 1.1rem;
        font-weight: 600; /* সেমি-বোল্ড */
        margin-bottom: 0.25rem;
    }
    .account-hub-card p {
        font-size: 0.9rem;
        color: #6c757d; /* Bootstrap-এর সেকেন্ডারি টেক্সট কালার */
        margin-bottom: 0;
    }
    /* ছোট স্ক্রিনের জন্য সাইডবারে মার্জিন */
    @media (max-width: 767.98px) {
        .account-sidebar {
            margin-bottom: 2rem;
        }
    }
</style>

<main class="container my-5">
    <div class="row">
        <!-- বাম কলাম: সাইডবার মেনু -->
        <aside class="col-md-3 account-sidebar">
            <div class="list-group">
                <a href="/user/dashboard" class="list-group-item list-group-item-action active" aria-current="true">
                    <i class="fas fa-user-circle fa-fw me-2"></i>My Account
                </a>
                <a href="/user/orders" class="list-group-item list-group-item-action">
                    <i class="fas fa-box fa-fw me-2"></i>My Orders
                </a>
                <a href="/user/security" class="list-group-item list-group-item-action">
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

        <!-- ডান কলাম: প্রধান কন্টেন্ট (অ্যাকাউন্ট হাব) -->
        <section class="col-md-9">
            <h1 class="h3 mb-4">Hello, <?php echo $user_name; ?>!</h1>
            
            <div class="row row-cols-1 row-cols-lg-2 g-4 account-hub">
                <!-- কার্ড ১: Your Orders -->
                <div class="col">
                    <a href="/user/orders">
                        <div class="account-hub-card">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-box icon me-4"></i>
                                <div>
                                    <h5>Your Orders</h5>
                                    <p>Track, return, or see details of your orders.</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- কার্ড ২: Login & Security -->
                <div class="col">
                    <a href="/user/security">
                        <div class="account-hub-card">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-shield-alt icon me-4"></i>
                                <div>
                                    <h5>Login & Security</h5>
                                    <p>Edit login, name, and password.</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- কার্ড ৩: Your Addresses (ভবিষ্যতের জন্য) -->
                <div class="col">
                    <a href="#" class="disabled-link">
                        <div class="account-hub-card">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-map-marker-alt icon me-4"></i>
                                <div>
                                    <h5>Your Addresses</h5>
                                    <p>Manage and edit your shipping addresses.</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- কার্ড ৪: Payment Options (ভবিষ্যতের জন্য) -->
                <div class="col">
                     <a href="#" class="disabled-link">
                        <div class="account-hub-card">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-credit-card icon me-4"></i>
                                <div>
                                    <h5>Payment Options</h5>
                                    <p>Add or edit your payment methods.</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </section>
    </div>
</main>

<?php
// সাইটের সাধারণ ফুটার include করা হচ্ছে
require_once '../includes/footer.php'; 
?>