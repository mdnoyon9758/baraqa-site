<?php
// We define a single source of truth for our menu structure.
function get_admin_menu() {
    $admin_base_url = '/admin/';
    return [
        // Dashboard
        ['name' => 'Dashboard', 'url' => $admin_base_url . 'dashboard.php', 'icon' => 'fa-tachometer-alt', 'key' => 'dashboard'],
        
        // Ecommerce Section
        [
            'name' => 'Ecommerce',
            'icon' => 'fa-shopping-cart',
            'key' => 'ecommerce',
            'submenu' => [
                ['name' => 'Orders', 'url' => $admin_base_url . 'orders.php', 'key' => 'orders'],
                ['name' => 'Products', 'url' => $admin_base_url . 'products.php', 'key' => 'products'],
                ['name' => 'Categories', 'url' => $admin_base_url . 'categories.php', 'key' => 'categories'],
                ['name' => 'Brands', 'url' => $admin_base_url . 'brands.php', 'key' => 'brands'],
                ['name' => 'Customers', 'url' => $admin_base_url . 'users.php', 'key' => 'users'],
                ['name' => 'Reviews', 'url' => $admin_base_url . 'reviews.php', 'key' => 'reviews'],
            ]
        ],

        // Affiliate Section
        [
            'name' => 'Affiliate',
            'icon' => 'fa-handshake',
            'key' => 'affiliate',
            'submenu' => [
                ['name' => 'All Affiliates', 'url' => $admin_base_url . 'affiliates.php', 'key' => 'affiliates'],
                ['name' => 'Commissions', 'url' => $admin_base_url . 'commissions.php', 'key' => 'commissions'],
            ]
        ],
        
        // Content Management Section
        ['name' => 'Pages', 'url' => $admin_base_url . 'pages.php', 'icon' => 'fa-file-alt', 'key' => 'pages'],
        ['name' => 'Media', 'url' => $admin_base_url . 'media.php', 'icon' => 'fa-photo-video', 'key' => 'media'],

        // Appearance Section
        [
            'name' => 'Appearance',
            'icon' => 'fa-paint-brush',
            'key' => 'appearance',
            'submenu' => [
                ['name' => 'Menus', 'url' => $admin_base_url . 'menus.php', 'key' => 'menus'],
                ['name' => 'Theme Options', 'url' => $admin_base_url . 'theme_options.php', 'key' => 'theme_options'],
            ]
        ],

        // Settings & Tools Section
        [
            'name' => 'System',
            'icon' => 'fa-cogs',
            'key' => 'system',
            'submenu' => [
                ['name' => 'General Settings', 'url' => $admin_base_url . 'settings.php', 'key' => 'general_settings'],
                ['name' => 'API Config', 'url' => $admin_base_url . 'api_config.php', 'key' => 'api_config'],
                ['name' => 'Task Scheduler', 'url' => $admin_base_url . 'scheduler.php', 'key' => 'scheduler'],
                ['name' => 'System Logs', 'url' => $admin_base_url . 'logs.php', 'key' => 'logs'],
            ]
        ],
    ];
}
$menu_items = get_admin_menu();
$current_page_key = $page_key ?? '';
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="/admin/dashboard.php" class="sidebar-logo-link">
            <?php 
            $site_logo_url = get_setting('site_logo_url');
            if ($site_logo_url): ?>
                <img src="<?php echo e($site_logo_url); ?>" alt="<?php echo e(get_setting('site_name')); ?>" style="max-height: 35px;">
            <?php else: ?>
                <i class="fas fa-rocket sidebar-logo-icon"></i>
                <span class="sidebar-logo-text"><?php echo e(get_setting('site_name', 'BARAQA')); ?></span>
            <?php endif; ?>
        </a>
    </div>

    <nav class="sidebar-nav">
        <ul class="sidebar-menu">
            <?php foreach ($menu_items as $item): ?>
                <?php
                $has_submenu = isset($item['submenu']) && !empty($item['submenu']);
                $is_parent_active = $has_submenu && in_array($current_page_key, array_column($item['submenu'], 'key'));
                $is_active = (!$has_submenu && $current_page_key === $item['key']) || $is_parent_active;
                ?>
                <li class="sidebar-item <?php echo $is_active ? 'active' : ''; ?> <?php echo $has_submenu ? 'has-submenu' : ''; ?>">
                    <a href="<?php echo $has_submenu ? '#' : e($item['url']); ?>" class="sidebar-link" <?php if ($has_submenu) echo 'data-bs-toggle="collapse" data-bs-target="#' . e($item['key']) . '-submenu" aria-expanded="' . ($is_parent_active ? 'true' : 'false') . '"'; ?>>
                        <i class="fas <?php echo e($item['icon']); ?> fa-fw"></i>
                        <span><?php echo e($item['name']); ?></span>
                        <?php if ($has_submenu): ?><i class="fas fa-chevron-down submenu-arrow"></i><?php endif; ?>
                    </a>
                    <?php if ($has_submenu): ?>
                        <ul id="<?php echo e($item['key']); ?>-submenu" class="collapse submenu <?php echo $is_parent_active ? 'show' : ''; ?>">
                            <?php foreach ($item['submenu'] as $sub_item): ?>
                                <li class="submenu-item <?php echo ($current_page_key === $sub_item['key']) ? 'active' : ''; ?>">
                                    <a href="<?php echo e($sub_item['url']); ?>"><?php echo e($sub_item['name']); ?></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>