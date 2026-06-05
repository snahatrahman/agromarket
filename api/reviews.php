<?php
require_once 'config.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        addReview();
        break;
    case 'get':
        getReviews();
        break;
    default:
        sendError('অজানা action');
}

function addReview() {
    $user = requireAuth();

    $data      = json_decode(file_get_contents('php://input'), true);
    $productId = (int)($data['product_id'] ?? 0);
    $rating    = (int)($data['rating'] ?? 0);
    $comment   = trim($data['comment'] ?? '');

    if (!$productId || $rating < 1 || $rating > 5) {
        sendError('সঠিক তথ্য দিন (রেটিং ১-৫)');
    }

    $pdo = getDB();

    // Check if user bought this product
    $stmt = $pdo->prepare("
        SELECT o.id FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'delivered'
        LIMIT 1
    ");
    $stmt->execute([$user['id'], $productId]);
    if (!$stmt->fetch()) {
        sendError('শুধুমাত্র পণ্য ক্রয় ও ডেলিভারির পরে রিভিউ দেওয়া যাবে');
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE rating = ?, comment = ?");
        $stmt->execute([$productId, $user['id'], $rating, $comment, $rating, $comment]);
        sendSuccess([], 'রিভিউ যোগ হয়েছে');
    } catch (Exception $e) {
        sendError('রিভিউ যোগ করতে সমস্যা হয়েছে');
    }
}

function getReviews() {
    $productId = (int)($_GET['product_id'] ?? 0);
    if (!$productId) sendError('Product ID দিন');

    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT r.*, u.name as reviewer_name
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.product_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$productId]);
    $reviews = $stmt->fetchAll();

    $avgRating = $reviews ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;

    sendSuccess(['reviews' => $reviews, 'avg_rating' => round($avgRating, 1)]);
}
?>