<?php
// --- Smart Session Start ---
if (!defined('CRON_JOB_RUNNING')) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Include the database connection handler.
require_once __DIR__ . '/db_connect.php';

// --- Site Settings & Navigation Functions ---

function get_site_settings() {
    global $pdo;
    static $settings = null;
    if ($settings === null) {
        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (PDOException $e) {
            $settings = [];
        }
    }
    return $settings;
}

function get_setting($key, $default = null) {
    $settings = get_site_settings();
    return $settings[$key] ?? $default;
}

function get_all_pages() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT id, title, slug FROM site_pages WHERE is_published = 1 ORDER BY menu_order ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// --- Security & Authentication Functions ---

function is_logged_in() {
    return isset($_SESSION['admin_id']);
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['error_message'] = "Please log in to access this page.";
        header('Location: /bs/admin/login.php');
        exit();
    }
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (isset($_SESSION['csrf_token']) && !empty($token) && hash_equals($_SESSION['csrf_token'], $token)) {
        unset($_SESSION['csrf_token']);
        return true;
    }
    return false;
}

// --- Helper & Utility Functions ---

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    if (empty($text)) {
        return 'n-a-' . substr(md5(time()), 0, 5);
    }
    return $text;
}

function display_flash_messages() {
    if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
        echo '<strong>Success!</strong> ' . e($_SESSION['success_message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        echo '<strong>Error!</strong> ' . e($_SESSION['error_message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['error_message']);
    }
}

// --- AI & Data Analysis Functions ---

function calculateTrendScore($rating, $reviews, $discount) {
    $w_rating = 0.5; $w_reviews = 0.3; $w_discount = 0.2;
    $normalized_rating = ($rating / 5) * 100;
    $normalized_reviews = ($reviews > 0) ? (log10($reviews) / log10(100000)) * 100 : 0;
    $normalized_reviews = min($normalized_reviews, 100);
    $normalized_discount = (float)$discount;
    $score = ($normalized_rating * $w_rating) + ($normalized_reviews * $w_reviews) + ($normalized_discount * $w_discount);
    return round($score, 2);
}

// --- Encryption Functions ---

function encrypt_data($data) {
    if (!defined('ENCRYPTION_KEY') || !defined('ENCRYPTION_IV') || empty($data)) return $data;
    $cipher = "aes-256-cbc";
    return openssl_encrypt($data, $cipher, ENCRYPTION_KEY, 0, ENCRYPTION_IV);
}

function decrypt_data($encrypted_data) {
    if (!defined('ENCRYPTION_KEY') || !defined('ENCRYPTION_IV') || empty($encrypted_data)) return $encrypted_data;
    $cipher = "aes-256-cbc";
    return openssl_decrypt($encrypted_data, $cipher, ENCRYPTION_KEY, 0, ENCRYPTION_IV);
}

// --- Activity Logging ---

function log_admin_activity($action, $target_type = null, $target_id = null) {
    global $pdo;
    if (defined('CRON_JOB_RUNNING')) {
        return;
    }
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO admin_activity_log (admin_id, admin_name, action, target_type, target_id, ip_address) 
             VALUES (:admin_id, :admin_name, :action, :target_type, :target_id, :ip_address)"
        );
        $stmt->execute([
            'admin_id' => $_SESSION['admin_id'] ?? null,
            'admin_name' => $_SESSION['admin_name'] ?? 'System',
            'action' => $action,
            'target_type' => $target_type,
            'target_id' => $target_id,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'N/A'
        ]);
    } catch (PDOException $e) {
        error_log('Failed to log admin activity: ' . $e->getMessage());
    }
}
?>