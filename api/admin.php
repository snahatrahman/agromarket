<?php
require_once 'config.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'stats':
        getStats();
        break;
    case 'users':
        getUsers();
        break;
    case 'delete_user':
        deleteUser();
        break;
    case 'delete_product':
        deleteProduct();
        break;
    default:
        sendError('অজানা action');
}

function getStats() {
    requireRole('admin');
    $pdo = getDB();

    $users    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $farmers  = $pdo->query("SELECT COUNT(*) FROM users WHERE role='farmer'")->fetchColumn();
    $consumers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='consumer'")->fetchColumn();
    $products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $orders   = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $revenue  = $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
    $pending  = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();

    sendSuccess([
        'total_users'    => (int)$users,
        'farmer_count'   => (int)$farmers,
        'consumer_count' => (int)$consumers,
        'total_products' => (int)$products,
        'total_orders'   => (int)$orders,
        'total_revenue'  => (float)$revenue,
        'pending_orders' => (int)$pending
    ]);
}

function getUsers() {
    requireRole('admin');
    $pdo = getDB();

    $stmt = $pdo->query("SELECT id, name, email, role, phone, address, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();

    sendSuccess(['users' => $users]);
}

function deleteUser() {
    requireRole('admin');

    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    if (!$id) sendError('User ID দিন');

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) sendError('User পাওয়া যায়নি');
    if ($user['role'] === 'admin') sendError('Admin মুছে ফেলা যাবে না');

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);

    sendSuccess([], 'User মুছে ফেলা হয়েছে');
}

function deleteProduct() {
    requireRole('admin');

    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    if (!$id) sendError('Product ID দিন');

    $pdo = getDB();
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);

    sendSuccess([], 'পণ্য মুছে ফেলা হয়েছে');
}
?>