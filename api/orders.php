<?php
require_once 'config.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        createOrder();
        break;
    case 'my_orders':
        getMyOrders();
        break;
    case 'all_orders':
        getAllOrders();
        break;
    case 'update_status':
        updateOrderStatus();
        break;
    case 'farmer_orders':
        getFarmerOrders();
        break;
    default:
        sendError('অজানা action');
}

function createOrder() {
    $user = requireAuth();

    $data = json_decode(file_get_contents('php://input'), true);

    $shippingAddress = trim($data['shipping_address'] ?? '');
    $phone           = trim($data['phone'] ?? '');
    $paymentMethod   = trim($data['payment_method'] ?? '');
    $notes           = trim($data['notes'] ?? '');

    if (!$shippingAddress || !$phone || !$paymentMethod) {
        sendError('ডেলিভারি তথ্য সম্পূর্ণ করুন');
    }

    // Frontend validation can be bypassed, so backend check is necessary
if (!preg_match('/^01[0-9]\d{8}$/', $phone)) {
    sendError('সঠিক বাংলাদেশি ফোন নম্বর দিন (যেমন: 01XXXXXXXXX)');
}

// Prevents users from sending extremely long text to the server
if (strlen($notes) > 500) {
    sendError('বিশেষ নির্দেশনা ৫০০ অক্ষরের বেশি হতে পারবে না');
}


if (strlen($shippingAddress) < 10) {
    sendError('সম্পূর্ণ ঠিকানা লিখুন (কমপক্ষে ১০ অক্ষর)');
}

    $pdo = getDB();

    // Get cart
    $stmt = $pdo->prepare("
        SELECT c.quantity, p.id as product_id, p.name, p.base_price, p.stock, p.harvest_date, p.farmer_id
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user['id']]);
    $cartItems = $stmt->fetchAll();

    if (!$cartItems) sendError('কার্ট খালি');

    $total = 0;
    $orderItems = [];

    foreach ($cartItems as $item) {
        $pricing = calcCurrentPrice($item['base_price'], $item['harvest_date']);
        if ($pricing['is_expired']) {
            sendError("{$item['name']} পণ্যের মেয়াদ শেষ হয়ে গেছে");
        }
        if ($item['stock'] < $item['quantity']) {
            sendError("{$item['name']} পণ্যে পর্যাপ্ত স্টক নেই");
        }

        $itemTotal = $pricing['current_price'] * $item['quantity'];
        $total += $itemTotal;

        $orderItems[] = [
            'product_id'   => $item['product_id'],
            'product_name' => $item['name'],
            'quantity'     => $item['quantity'],
            'price'        => $pricing['current_price'],
            'total'        => $itemTotal
        ];
    }

    // Transaction: create order, order_items, update stock, clear cart
    $pdo->beginTransaction();

    try {
        // Create order
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total, payment_method, shipping_address, phone, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user['id'], $total, $paymentMethod, $shippingAddress, $phone, $notes]);
        $orderId = $pdo->lastInsertId();

        // Insert order items & update stock
        foreach ($orderItems as $oi) {
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price, total) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$orderId, $oi['product_id'], $oi['product_name'], $oi['quantity'], $oi['price'], $oi['total']]);

            $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$oi['quantity'], $oi['product_id']]);
        }

        // Clear cart
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$user['id']]);

        $pdo->commit();

        sendSuccess(['order_id' => $orderId, 'total' => $total], 'অর্ডার সফলভাবে সম্পন্ন হয়েছে');

    } catch (Exception $e) {
        $pdo->rollBack();
        sendError('অর্ডার তৈরিতে সমস্যা হয়েছে');
    }
}

function getMyOrders() {
    $user = requireAuth();
    $pdo = getDB();

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user['id']]);
    $orders = $stmt->fetchAll();

    foreach ($orders as &$order) {
        $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$order['id']]);
        $order['items'] = $stmt->fetchAll();
    }

    sendSuccess(['orders' => $orders]);
}

function getAllOrders() {
    requireRole('admin');
    $pdo = getDB();

    $status = $_GET['status'] ?? '';
    $params = [];
    $where = '';

    if ($status) {
        $where = "WHERE o.status = ?";
        $params[] = $status;
    }

    $stmt = $pdo->prepare("
        SELECT o.*, u.name as user_name, u.email as user_email
        FROM orders o
        JOIN users u ON o.user_id = u.id
        $where
        ORDER BY o.created_at DESC
    ");
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    foreach ($orders as &$order) {
        $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$order['id']]);
        $order['items'] = $stmt->fetchAll();
    }

    sendSuccess(['orders' => $orders]);
}

function updateOrderStatus() {
    requireRole('admin');

    $data = json_decode(file_get_contents('php://input'), true);
    $orderId = (int)($data['order_id'] ?? 0);
    $status  = $data['status'] ?? '';

    $allowed = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (!$orderId || !in_array($status, $allowed)) {
        sendError('সঠিক তথ্য দিন');
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $orderId]);

    sendSuccess([], 'অর্ডার স্ট্যাটাস আপডেট হয়েছে');
}

function getFarmerOrders() {
    $user = requireRole('farmer');
    $pdo = getDB();

    $stmt = $pdo->prepare("
        SELECT DISTINCT o.*, u.name as user_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        JOIN order_items oi ON oi.order_id = o.id
        JOIN products p ON oi.product_id = p.id
        WHERE p.farmer_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $orders = $stmt->fetchAll();

    foreach ($orders as &$order) {
        $stmt = $pdo->prepare("
            SELECT oi.* FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ? AND p.farmer_id = ?
        ");
        $stmt->execute([$order['id'], $user['id']]);
        $order['items'] = $stmt->fetchAll();
    }

    sendSuccess(['orders' => $orders]);
}
?>