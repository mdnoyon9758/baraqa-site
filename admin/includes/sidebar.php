<?php
// We define a single source of truth for our menu structure.
function get_admin_menu() {
    $admin_base_url = '/admin/';
    return [
        ['name' => 'Dashboard', 'url' => $admin_base_url . 'dashboard.php', 'icon' => 'fa-tachometer-alt', 'key' => 'dashboard'],
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
            ]
        ],
        ['name' => 'Pages', 'url' => $admin_base_url . 'pages.php', 'icon' => 'fa-file-alt', 'key' => 'pages'],
        [
            'name' => 'Settings',
            'icon' => 'fa-cogs',
            'key' => 'settings',
            'submenu' => [
                ['name' => 'General', 'url' => $admin_base_url . 'settings.php', 'key' => 'general_settings'],
                ['name' => 'API Config', 'url' => $admin_base_url . 'api_config.php', 'key' => 'api_config'],
            ]
        ],
    ];
}
$menu_items = get_admin_menu();
$current_page_key = $page_key ?? ''; // This variable MUST be set on each page (e.g., $page_key = 'dashboard';)
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="/admin/dashboard.php" class="sidebar-logo-link">
            <!-- Replace with your actual logo if available -->
            <i class="fas fa-rocket sidebar-logo-icon"></i>
            <span class="sidebar-logo-text"><?php echo e(get_setting('site_name') ?? 'BARAQA'); ?></span>
        </a>
    </div>

    <nav class="sidebar-nav">
        <ul class="sidebar-menu">
            <?php foreach ($menu_items as $item): ?>
                <?php
                $has_submenu = isset($item['submenu']) && !empty($item['submenu']);
                $is_parent_active = $has_submenu && in_array($current_page_key, array_column($item['submenu'], 'key'));
                $is_active = $current_page_key === $item['key'] || $is_parent_active;
                ?>
                <li class="sidebar-item <?php echo $is_active ? 'active' : ''; ?> <?php echo $has_submenu ? 'has-submenu' : ''; ?>">
                    <a href="<?php echo $has_submenu ? '#' : e($item['url']); ?>" class="sidebar-link" <?php if ($has_submenu) echo 'data-bs-toggle="collapse" data-bs-target="#' . e($item['key']) . '-submenu"'; ?>>
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