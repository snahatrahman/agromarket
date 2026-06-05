<?php
require_once 'config.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        getCart();
        break;
    case 'add':
        addToCart();
        break;
    case 'update':
        updateCartItem();
        break;
    case 'remove':
        removeFromCart();
        break;
    case 'clear':
        clearCart();
        break;
    default:
        sendError('অজানা action');
}

function getCart() {
    $user = requireAuth();
    $pdo = getDB();

    $stmt = $pdo->prepare("
        SELECT c.id, c.quantity, p.id as product_id, p.name, p.base_price, p.unit, 
               p.stock, p.image, p.harvest_date, p.location, u.name as farmer_name
        FROM cart c
        JOIN products p ON c.product_id = p.id
        JOIN users u ON p.farmer_id = u.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user['id']]);
    $cartItems = $stmt->fetchAll();

    $result = [];
    $grandTotal = 0;

    foreach ($cartItems as $item) {
        $pricing = calcCurrentPrice($item['base_price'], $item['harvest_date']);

        if ($pricing['is_expired']) {
            // Auto-remove expired items from cart
            $delStmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $delStmt->execute([$user['id'], $item['product_id']]);
            continue;
        }

        $itemTotal = $pricing['current_price'] * $item['quantity'];
        $grandTotal += $itemTotal;

        $result[] = [
            'cart_id'       => $item['id'],
            'product_id'    => $item['product_id'],
            'product_name'  => $item['name'],
            'farmer'        => $item['farmer_name'],
            'location'      => $item['location'],
            'image'         => $item['image'],
            'unit'          => $item['unit'],
            'quantity'      => (int)$item['quantity'],
            'base_price'    => (float)$item['base_price'],
            'current_price' => $pricing['current_price'],
            'discount'      => $pricing['discount'],
            'days_old'      => $pricing['days_old'],
            'stock'         => (int)$item['stock'],
            'item_total'    => $itemTotal
        ];
    }

    sendSuccess(['items' => $result, 'grand_total' => $grandTotal, 'count' => count($result)]);
}

function addToCart() {
    $user = requireAuth();

    $data = json_decode(file_get_contents('php://input'), true);
    $productId = (int)($data['product_id'] ?? 0);
    $quantity  = (int)($data['quantity'] ?? 1);

    if (!$productId || $quantity < 1) sendError('সঠিক তথ্য দিন');

    $pdo = getDB();

    // Check product exists and has stock
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) sendError('পণ্য পাওয়া যায়নি', 404);

    $pricing = calcCurrentPrice($product['base_price'], $product['harvest_date']);
    if ($pricing['is_expired']) sendError('পণ্যের মেয়াদ শেষ');
    if ($product['stock'] < $quantity) sendError('পর্যাপ্ত স্টক নেই');

    // Insert or update cart
    $stmt = $pdo->prepare("
        INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE quantity = quantity + ?
    ");
    $stmt->execute([$user['id'], $productId, $quantity, $quantity]);

    sendSuccess([], 'পণ্যটি কার্টে যোগ হয়েছে');
}

function updateCartItem() {
    $user = requireAuth();

    $data = json_decode(file_get_contents('php://input'), true);
    $productId = (int)($data['product_id'] ?? 0);
    $quantity  = (int)($data['quantity'] ?? 0);

    if (!$productId) sendError('Product ID দিন');

    $pdo = getDB();

    if ($quantity <= 0) {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user['id'], $productId]);
    } else {
        // Check stock
        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        if (!$product || $product['stock'] < $quantity) {
            sendError('পর্যাপ্ত স্টক নেই');
        }

        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$quantity, $user['id'], $productId]);
    }

    sendSuccess([], 'কার্ট আপডেট হয়েছে');
}

function removeFromCart() {
    $user = requireAuth();

    $data = json_decode(file_get_contents('php://input'), true);
    $productId = (int)($data['product_id'] ?? 0);
    if (!$productId) sendError('Product ID দিন');

    $pdo = getDB();
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user['id'], $productId]);

    sendSuccess([], 'পণ্য কার্ট থেকে সরানো হয়েছে');
}

function clearCart() {
    $user = requireAuth();

    $pdo = getDB();
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$user['id']]);

    sendSuccess([], 'কার্ট পরিষ্কার হয়েছে');
}
?>