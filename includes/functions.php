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

function is_logged_in() { // This is for admin
    return isset($_SESSION['admin_id']);
}

function require_login() { // This is for admin
    if (!is_logged_in()) {
        set_flash_message("Please log in to access this page.", "warning");
        header('Location: /admin/login.php');
        exit();
    }
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifies a CSRF token.
 *
 * @param string $token The token from the request.
 * @param bool $consume If true, the token will be unset after verification (for one-time use).
 * @return bool
 */
function verify_csrf_token($token, $consume = true) {
    if (isset($_SESSION['csrf_token']) && !empty($token) && hash_equals($_SESSION['csrf_token'], $token)) {
        if ($consume) {
            unset($_SESSION['csrf_token']);
        }
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

// --- NEW AND IMPROVED FLASH MESSAGE FUNCTIONS ---

/**
 * Sets a flash message in the session to be displayed on the next page load.
 *
 * @param string $message The message to display.
 * @param string $type The type of message (e.g., 'success', 'danger', 'warning', 'info').
 */
function set_flash_message($message, $type = 'success') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_message'] = ['message' => $message, 'type' => $type];
}

/**
 * Displays and then clears the flash message from the session.
 */
function display_flash_messages() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        $message = e($flash['message']);
        $type = e($flash['type']);
        $alert_details = [
            'success' => ['icon' => 'fa-check-circle', 'title' => 'Success!'],
            'danger'  => ['icon' => 'fa-times-circle', 'title' => 'Error!'],
            'warning' => ['icon' => 'fa-exclamation-triangle', 'title' => 'Warning!'],
            'info'    => ['icon' => 'fa-info-circle', 'title' => 'Notice!']
        ];
        $icon = $alert_details[$type]['icon'] ?? 'fa-info-circle';
        $title = $alert_details[$type]['title'] ?? 'Message';
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
        echo '<strong><i class="fas ' . $icon . ' me-2"></i>' . $title . '</strong> ' . $message;
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['flash_message']);
    }
}


// --- AI & Data Analysis Functions ---

function calculateTrendScore($rating, $reviews, $discount) {
    // ... Function remains unchanged ...
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
    // ... Function remains unchanged ...
    if (!defined('ENCRYPTION_KEY') || !defined('ENCRYPTION_IV') || empty($data)) return $data;
    $cipher = "aes-256-cbc";
    return openssl_encrypt($data, $cipher, ENCRYPTION_KEY, 0, ENCRYPTION_IV);
}

function decrypt_data($encrypted_data) {
    // ... Function remains unchanged ...
    if (!defined('ENCRYPTION_KEY') || !defined('ENCRYPTION_IV') || empty($encrypted_data)) return $encrypted_data;
    $cipher = "aes-256-cbc";
    return openssl_decrypt($encrypted_data, $cipher, ENCRYPTION_KEY, 0, ENCRYPTION_IV);
}

// --- Activity Logging ---

function log_admin_activity($action, $target_type = null, $target_id = null) {
    // ... Function remains unchanged ...
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

// This function is for frontend user authentication
function is_user_logged_in() {
    return isset($_SESSION['user_id']);
}
?>