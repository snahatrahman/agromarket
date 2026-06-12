<?php
require_once 'config.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':            getProductList();    break;
    case 'detail':          getProductDetail();  break;
    case 'add':             addProduct();        break;
    case 'delete':          deleteProduct();     break;
    case 'farmer_products': getFarmerProducts(); break;
    case 'update':          updateProduct();     break;
    default: sendError('অজানা action');
}

function getProductList() {
    $pdo      = getDB();
    $category = $_GET['category'] ?? '';
    $search   = $_GET['search'] ?? '';
    $sort     = $_GET['sort'] ?? 'newest';

    $where  = ["p.stock > 0"];
    $params = [];

    if ($category) { $where[] = "p.category = ?"; $params[] = $category; }
    if ($search)   { $where[] = "(p.name LIKE ? OR p.description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

    $whereSQL = "WHERE " . implode(" AND ", $where);
    $orderSQL = match($sort) {
        'price-low'  => "ORDER BY p.base_price ASC",
        'price-high' => "ORDER BY p.base_price DESC",
        'fresh'      => "ORDER BY p.harvest_date DESC",
        default      => "ORDER BY p.created_at DESC"
    };

    $sql  = "SELECT p.*, u.name as farmer_name FROM products p JOIN users u ON p.farmer_id = u.id $whereSQL $orderSQL";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    $result = [];
    foreach ($products as $product) {
        $p = addPricingToProduct($product);
        if (!$p['is_expired']) $result[] = $p;
    }
    sendSuccess(['products' => $result]);
}

function getProductDetail() {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) sendError('Product ID দিন');

    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT p.*, u.name as farmer_name FROM products p JOIN users u ON p.farmer_id = u.id WHERE p.id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) sendError('পণ্য পাওয়া যায়নি', 404);

    $product = addPricingToProduct($product);

    $stmt = $pdo->prepare("SELECT r.*, u.name as reviewer_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
    $stmt->execute([$id]);
    $reviews = $stmt->fetchAll();

    $avgRating = $reviews ? round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1) : 0;

    sendSuccess(['product' => $product, 'reviews' => $reviews, 'avg_rating' => $avgRating]);
}

function addProduct() {
    $user = requireRole('farmer');
    $data = json_decode(file_get_contents('php://input'), true);

    $name        = trim($data['name'] ?? '');
    $category    = trim($data['category'] ?? '');
    $base_price  = (float)($data['price'] ?? 0);
    $unit        = trim($data['unit'] ?? '');
    $stock       = (int)($data['stock'] ?? 0);
    $location    = trim($data['location'] ?? '');
    $description = trim($data['description'] ?? '');
    $image       = trim($data['image'] ?? '');
    $harvestDate = $data['harvest_date'] ?? date('Y-m-d H:i:s');

    if (!$name || !$category || !$base_price || !$unit || !$stock || !$location || !$description) sendError('সব তথ্য পূরণ করুন');
    if ($base_price <= 0) sendError('সঠিক দাম দিন');
    if ($stock <= 0) sendError('সঠিক স্টক দিন');

    if (!$image) $image = 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=500';

    $pdo  = getDB();
    $stmt = $pdo->prepare("INSERT INTO products (name, category, base_price, unit, stock, location, description, image, harvest_date, farmer_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $category, $base_price, $unit, $stock, $location, $description, $image, $harvestDate, $user['id']]);

    sendSuccess(['product_id' => (int)$pdo->lastInsertId()], 'পণ্য সফলভাবে যোগ হয়েছে');
}

function deleteProduct() {
    $user = requireRole('farmer');
    $data = json_decode(file_get_contents('php://input'), true);
    $id   = (int)($data['id'] ?? 0);
    if (!$id) sendError('Product ID দিন');

    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT farmer_id FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) sendError('পণ্য পাওয়া যায়নি', 404);
    if ($product['farmer_id'] != $user['id'] && $user['role'] !== 'admin') sendError('আপনার অনুমতি নেই', 403);

    // Delete associated image if local
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row && str_starts_with($row['image'], 'uploads/')) {
        $path = __DIR__ . '/../' . $row['image'];
        if (file_exists($path)) unlink($path);
    }

    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    sendSuccess([], 'পণ্য মুছে ফেলা হয়েছে');
}

function getFarmerProducts() {
    $user = requireRole('farmer');
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM products WHERE farmer_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user['id']]);
    $products = $stmt->fetchAll();
    sendSuccess(['products' => array_map('addPricingToProduct', $products)]);
}

function updateProduct() {
    $user = requireRole('farmer');
    $data = json_decode(file_get_contents('php://input'), true);
    $id   = (int)($data['id'] ?? 0);
    if (!$id) sendError('Product ID দিন');

    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT farmer_id FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) sendError('পণ্য পাওয়া যায়নি', 404);
    if ($product['farmer_id'] != $user['id'] && $user['role'] !== 'admin') sendError('আপনার অনুমতি নেই', 403);

    $name        = trim($data['name'] ?? '');
    $base_price  = (float)($data['price'] ?? 0);
    $stock       = (int)($data['stock'] ?? 0);
    $description = trim($data['description'] ?? '');
    if (!$name || !$base_price || !$stock) sendError('সব তথ্য পূরণ করুন');

    $stmt = $pdo->prepare("UPDATE products SET name=?, base_price=?, stock=?, description=? WHERE id=?");
    $stmt->execute([$name, $base_price, $stock, $description, $id]);
    sendSuccess([], 'পণ্য আপডেট হয়েছে');
}
?>
