<?php
// Set response header to JSON
header('Content-Type: application/json');

// Bootstrap the application and perform security checks
require_once __DIR__ . '/../includes/app.php';
require_login();

// Verify request method and CSRF token
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request or security token.']);
    exit;
}

$action = $_POST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Unknown action specified.'];

try {
    // Use a switch statement to handle different actions
    switch ($action) {
        
        case 'add_items':
            $menu_id = (int)($_POST['menu_id'] ?? 0);
            $items = $_POST['items'] ?? [];
            if ($menu_id > 0 && !empty($items)) {
                $stmt = $pdo->prepare("INSERT INTO menu_items (menu_id, title, url) VALUES (?, ?, ?)");
                foreach ($items as $item) {
                    if (!empty($item['title']) && !empty($item['url'])) {
                        $stmt->execute([$menu_id, trim($item['title']), trim($item['url'])]);
                    }
                }
                $response = ['status' => 'success', 'message' => 'Menu items added successfully.'];
            } else {
                $response['message'] = 'Missing menu ID or items to add.';
            }
            break;

        case 'save_structure':
            $menu_id = (int)($_POST['menu_id'] ?? 0);
            $structure = json_decode($_POST['structure'] ?? '[]', true);
            if ($menu_id > 0) {
                $pdo->beginTransaction();
                
                // First, reset all parent_id for this menu to handle items being moved to top level
                $pdo->prepare("UPDATE menu_items SET parent_id = 0 WHERE menu_id = ?")->execute([$menu_id]);
                
                // Recursive function to update order and parentage
                function update_order_and_parentage($items, $parent_id = 0) {
                    global $pdo;
                    foreach ($items as $order => $item) {
                        $stmt = $pdo->prepare("UPDATE menu_items SET item_order = ?, parent_id = ? WHERE id = ?");
                        $stmt->execute([$order, $parent_id, (int)$item['id']]);
                        if (isset($item['children']) && is_array($item['children'])) {
                            update_order_and_parentage($item['children'], (int)$item['id']);
                        }
                    }
                }
                if (!empty($structure)) {
                    update_order_and_parentage($structure);
                }
                $pdo->commit();
                $response = ['status' => 'success', 'message' => 'Menu structure saved.'];
            } else {
                $response['message'] = 'Invalid menu ID.';
            }
            break;

        case 'update_item':
            $item_id = (int)($_POST['item_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $url = trim($_POST['url'] ?? '');
            if ($item_id > 0 && !empty($title) && !empty($url)) {
                $stmt = $pdo->prepare("UPDATE menu_items SET title = ?, url = ? WHERE id = ?");
                $stmt->execute([$title, $url, $item_id]);
                $response = ['status' => 'success', 'message' => 'Menu item updated.'];
            } else {
                $response['message'] = 'Missing data for item update.';
            }
            break;

        case 'delete_item':
            $item_id = (int)($_POST['item_id'] ?? 0);
            if ($item_id > 0) {
                // To delete children recursively, we need to find all descendants
                $pdo->beginTransaction();
                $pdo->prepare("DELETE FROM menu_items WHERE parent_id = ?")->execute([$item_id]); // Delete direct children first
                $pdo->prepare("DELETE FROM menu_items WHERE id = ?")->execute([$item_id]); // Then delete the item itself
                $pdo->commit();
                $response = ['status' => 'success', 'message' => 'Item and its sub-items deleted.'];
            } else {
                $response['message'] = 'Invalid item ID.';
            }
            break;
            
        default:
            // The response is already set to 'Unknown action'
            break;
    }
} catch (Exception $e) {
    // If a transaction was started, roll it back
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = 'An error occurred: ' . $e->getMessage();
    error_log("Menu Action Error: " . $e->getMessage());
}

// Send the JSON response back to the client
echo json_encode($response);