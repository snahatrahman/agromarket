<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'agromarketbd');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', 'uploads/');


define('SITE_URL', 'https://if0_41903214.infinityfreeapp.com');
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Database connection failed']));
        }
    }
    return $pdo;
}

// ================================================================
// Session Config
// ================================================================
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ================================================================
// CORS — FIXED for both localhost AND public hosting
// ================================================================
header('Content-Type: application/json; charset=utf-8');

$origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
$host    = $_SERVER['HTTP_HOST']   ?? '';

// Allowed origins: localhost dev + same-origin production
$isLocalhost = (
    str_starts_with($origin, 'http://localhost') ||
    str_starts_with($origin, 'http://127.0.0.1') ||
    str_starts_with($origin, 'https://localhost')
);

// Same-origin check (frontend & backend একই domain এ আছে)
$sameOrigin = !empty($origin) && (
    str_contains($origin, $host) ||
    (SITE_URL && str_starts_with($origin, SITE_URL))
);

if ($isLocalhost || $sameOrigin) {
    header("Access-Control-Allow-Origin: $origin");
} elseif (empty($origin)) {
    // Direct API call (same domain, no Origin header) — allow
    // No CORS header needed
} else {
    // Unknown origin — still allow but without credentials for safety
    header("Access-Control-Allow-Origin: *");
}

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// ================================================================
// Helper Functions
// ================================================================
function sendSuccess($data = [], $message = 'সফল') {
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function sendError($message = 'কিছু ভুল হয়েছে', $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

function requireAuth() {
    $user = getCurrentUser();
    if (!$user) {
        sendError('লগইন করুন', 401);
    }
    return $user;
}

function requireRole($role) {
    $user = requireAuth();
    if ($user['role'] !== $role && $user['role'] !== 'admin') {
        sendError('আপনার অনুমতি নেই', 403);
    }
    return $user;
}

function calcCurrentPrice($basePrice, $harvestDate) {
    $harvest  = new DateTime($harvestDate);
    $now      = new DateTime();
    $daysOld  = (int)$now->diff($harvest)->days;

    if ($daysOld <= 2) {
        return ['days_old' => $daysOld, 'current_price' => (float)$basePrice, 'discount' => 0,   'is_expired' => false];
    } elseif ($daysOld <= 4) {
        return ['days_old' => $daysOld, 'current_price' => round($basePrice * 0.8, 2), 'discount' => 20,  'is_expired' => false];
    } elseif ($daysOld <= 6) {
        return ['days_old' => $daysOld, 'current_price' => round($basePrice * 0.7, 2), 'discount' => 30,  'is_expired' => false];
    } else {
        return ['days_old' => $daysOld, 'current_price' => 0, 'discount' => 100, 'is_expired' => true];
    }
}

function addPricingToProduct($product) {
    $pricing = calcCurrentPrice($product['base_price'], $product['harvest_date']);
    return array_merge($product, $pricing);
}

function sanitize($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}
?>
