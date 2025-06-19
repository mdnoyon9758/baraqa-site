<?php
require_once __DIR__ . '/../includes/app.php';
require_login();

// Set header to return JSON
header('Content-Type: application/json');

// Get the POST data
$input = json_decode(file_get_contents('php://input'), true);

// Basic security checks
if (!$input || !isset($input['action']) || !verify_csrf_token($input['csrf_token'] ?? '', false)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request or CSRF token mismatch.']);
    exit;
}

$action = $input['action'];
$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

try {
    $pdo->beginTransaction();

    switch ($action) {
        
        case 'get_menu_items':
            $menu_id = (int)$input['menu_id'];
            if ($menu_id > 0) {
                $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE menu_id = ? ORDER BY item_order ASC");
                $stmt->execute([$menu_id]);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $response = ['status' => 'success', 'items' => $items];
            } else {
                $response['message'] = 'Invalid Menu ID.';
            }
            break;

        case 'add_items':
            $menu_id = (int)$input['menu_id'];
            $items_to_add = (array)$input['items'];
            
            foreach ($items_to_add as $item) {
                $stmt = $pdo->prepare("INSERT INTO menu_items (menu_id, title, url, type, original_id) VALUES (?, ?, ?, ?, ?)");
                
                $url = '#';
                if ($item['type'] === 'custom') {
                    $url = $item['url'];
                } elseif ($item['type'] === 'page') {
                    $url = '/page/' . e($item['slug']); // Assuming this URL structure
                } elseif ($item['type'] === 'category') {
                    $url = '/category/' . e($item['slug']); // Assuming this URL structure
                }
                
                $stmt->execute([$menu_id, $item['title'], $url, $item['type'], $item['id'] ?? null]);
            }
            $response = ['status' => 'success', 'message' => count($items_to_add) . ' item(s) added successfully.'];
            break;
        
        case 'update_menu_structure':
            $menu_id = (int)$input['menu_id'];
            $structure = (array)$input['structure'];

            function update_order($items, $parent_id = 0) {
                global $pdo;
                foreach ($items as $order => $item) {
                    $stmt = $pdo->prepare("UPDATE menu_items SET item_order = ?, parent_id = ? WHERE id = ?");
                    $stmt->execute([$order, $parent_id, $item['id']]);
                    if (isset($item['children'])) {
                        update_order($item['children'], $item['id']);
                    }
                }
            }
            update_order($structure);
            $response = ['status' => 'success', 'message' => 'Menu structure saved successfully.'];
            break;

        case 'update_item_details':
            $details = $input['details'];
            $id = (int)$details['id'];

            $stmt = $pdo->prepare("
                UPDATE menu_items SET 
                title = ?, url = ?, target_blank = ?, icon_class = ?, 
                css_class = ?, description = ?, is_mega_menu = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $details['title'],
                $details['url'],
                (bool)($details['target_blank'] ?? false),
                $details['icon_class'],
                $details['css_class'],
                $details['description'],
                (bool)($details['is_mega_menu'] ?? false),
                $id
            ]);
            $response = ['status' => 'success', 'message' => 'Menu item updated.'];
            break;
            
        case 'delete_item':
            $id = (int)$input['id'];
            // Also delete children to avoid orphaned items
            function delete_children($parent_id) {
                global $pdo;
                $stmt = $pdo->prepare("SELECT id FROM menu_items WHERE parent_id = ?");
                $stmt->execute([$parent_id]);
                $children = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($children as $child) {
                    delete_children($child['id']);
                }
                $stmt = $pdo->prepare("DELETE FROM menu_items WHERE parent_id = ?");
                $stmt->execute([$parent_id]);
            }
            delete_children($id);

            $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
            $stmt->execute([$id]);
            $response = ['status' => 'success', 'message' => 'Menu item deleted.'];
            break;

        case 'create_menu':
            $name = trim($input['name']);
            if (empty($name)) {
                $response['message'] = 'Menu name cannot be empty.';
                break;
            }
            $stmt = $pdo->prepare("INSERT INTO menus (name) VALUES (?)");
            $stmt->execute([$name]);
            $new_id = $pdo->lastInsertId();
            $response = ['status' => 'success', 'message' => 'Menu created.', 'new_id' => $new_id];
            break;

        case 'delete_menu':
            $menu_id = (int)$input['menu_id'];
            // Delete menu items first
            $stmt = $pdo->prepare("DELETE FROM menu_items WHERE menu_id = ?");
            $stmt->execute([$menu_id]);
            // Delete menu
            $stmt = $pdo->prepare("DELETE FROM menus WHERE id = ?");
            $stmt->execute([$menu_id]);
            $response = ['status' => 'success', 'message' => 'Menu deleted successfully.'];
            break;

        case 'save_locations':
            $locations = $input['locations'];
            foreach ($locations as $key => $menu_id) {
                $stmt = $pdo->prepare("
                    INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
                    ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value
                ");
                $stmt->execute([$key, $menu_id]);
            }
            $response = ['status' => 'success', 'message' => 'Menu locations saved.'];
            break;
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    $response['message'] = 'Error: ' . $e->getMessage();
    // For debugging, you might want to log the error
    error_log("Menu Action Error: " . $e->getMessage());
}

echo json_encode($response);