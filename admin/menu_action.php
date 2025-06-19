<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/app.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request or CSRF token.']);
    exit;
}

$action = $_POST['action'] ?? '';
$response = ['status' => 'error', 'message' => 'Unknown action.'];

try {
    switch ($action) {
        case 'add_items':
            $menu_id = (int)$_POST['menu_id'];
            $items = $_POST['items'] ?? [];
            if ($menu_id > 0 && !empty($items)) {
                $stmt = $pdo->prepare("INSERT INTO menu_items (menu_id, title, url) VALUES (?, ?, ?)");
                foreach ($items as $item) {
                    $stmt->execute([$menu_id, $item['title'], $item['url']]);
                }
                $response = ['status' => 'success'];
            }
            break;

        case 'save_structure':
            $menu_id = (int)$_POST['menu_id'];
            $structure = json_decode($_POST['structure'], true);
            if ($menu_id > 0 && !empty($structure)) {
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
                $response = ['status' => 'success'];
            }
            break;

        case 'delete_item':
            $item_id = (int)$_POST['item_id'];
            if ($item_id > 0) {
                $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
                $stmt->execute([$item_id]);
                $response = ['status' => 'success'];
            }
            break;
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);