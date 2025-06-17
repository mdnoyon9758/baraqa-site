<?php
/**
 * Initializes the application environment for all frontend pages.
 * This file should be included at the top of every user-facing page.
 */

// Start a session if it hasn't been started already.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Load Core Components
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/functions.php';

// 2. Load Global Site Data using Functions
$SITE_SETTINGS = get_site_settings();

// 3. Prepare Navigation Data
try {
    // --- START: MODIFIED NAVIGATION LOGIC ---

    // A. Fetch a LIMITED list of FEATURED categories for the main header navigation.
    // This keeps the header clean. We limit it to 7 for design purposes.
    $featured_nav_stmt = $pdo->query(
        "SELECT id, name, slug 
         FROM categories 
         WHERE parent_id IS NULL AND is_published = 1 AND is_featured = 1 
         ORDER BY menu_order ASC, name ASC 
         LIMIT 7"
    );
    $nav_categories_for_header = $featured_nav_stmt->fetchAll(PDO::FETCH_ASSOC);

    // B. Fetch ALL top-level (parent) categories for the site footer.
    // This ensures users can find any category from the footer.
    $all_parents_stmt = $pdo->query(
        "SELECT id, name, slug 
         FROM categories 
         WHERE parent_id IS NULL AND is_published = 1 
         ORDER BY name ASC"
    );
    $all_parent_categories = $all_parents_stmt->fetchAll(PDO::FETCH_ASSOC);

    // C. Fetch all sub-categories to be nested under their parents (this logic remains the same).
    $stmt_children = $pdo->query(
        "SELECT id, name, slug, parent_id 
         FROM categories 
         WHERE parent_id IS NOT NULL AND is_published = 1 
         ORDER BY name ASC"
    );
    $sub_categories_raw = $stmt_children->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize sub-categories into a structured array, keyed by their parent_id.
    $nav_sub_categories = [];
    foreach ($sub_categories_raw as $sub) {
        $nav_sub_categories[$sub['parent_id']][] = $sub;
    }

    // D. Fetch all published static pages for the menu (remains the same).
    $nav_pages = get_all_pages();
    
    // --- END: MODIFIED NAVIGATION LOGIC ---

} catch (PDOException $e) {
    // On database error, set empty arrays to prevent frontend errors.
    $nav_categories_for_header = [];
    $all_parent_categories = [];
    $nav_sub_categories = [];
    $nav_pages = [];
    error_log("Navigation data fetch failed in app.php: " . $e->getMessage());
}

// 4. Provide Fallback for Critical Settings
if (empty($SITE_SETTINGS['site_name'])) {
    $SITE_SETTINGS['site_name'] = 'AI Affiliate Hub';
}
if (empty($SITE_SETTINGS['products_per_page'])) {
    $SITE_SETTINGS['products_per_page'] = 12;
}
?>