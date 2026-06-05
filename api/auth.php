<?php
require_once 'config.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':    handleLogin();    break;
    case 'register': handleRegister(); break;
    case 'logout':   handleLogout();   break;
    case 'me':       handleMe();       break;
    default: sendError('অজানা action');
}

function handleLogin() {
    $data = json_decode(file_get_contents('php://input'), true);
    $email    = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if (!$email || !$password) sendError('ইমেইল ও পাসওয়ার্ড দিন');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) sendError('সঠিক ইমেইল দিন');

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        sendError('ইমেইল বা পাসওয়ার্ড ভুল');
    }

    unset($user['password']);
    $_SESSION['user'] = $user;
    session_regenerate_id(true); // Security

    sendSuccess(['user' => $user], 'লগইন সফল');
}

function handleRegister() {
    $data     = json_decode(file_get_contents('php://input'), true);
    $name     = trim($data['name'] ?? '');
    $email    = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $phone    = trim($data['phone'] ?? '');
    $address  = trim($data['address'] ?? '');
    $role     = $data['role'] ?? 'consumer';

    if (!$name || !$email || !$password || !$phone || !$address) sendError('সব তথ্য দিন');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) sendError('সঠিক ইমেইল দিন');
    if (strlen($password) < 6) sendError('পাসওয়ার্ড কমপক্ষে ৬ অক্ষর হতে হবে');
    if (!preg_match('/^01[3-9][0-9]{8}$/', $phone)) sendError('সঠিক বাংলাদেশি মোবাইল নম্বর দিন');
    if (!in_array($role, ['consumer', 'farmer'])) $role = 'consumer';

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) sendError('এই ইমেইল ইতিমধ্যে ব্যবহৃত হয়েছে');

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, phone, address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $email, $hash, $role, $phone, $address]);

    $userId = $pdo->lastInsertId();
    $user = ['id' => (int)$userId, 'name' => $name, 'email' => $email, 'role' => $role, 'phone' => $phone, 'address' => $address];
    $_SESSION['user'] = $user;

    sendSuccess(['user' => $user], 'রেজিস্ট্রেশন সফল! স্বাগতম!');
}

function handleLogout() {
    $_SESSION = [];
    session_destroy();
    sendSuccess([], 'লগআউট সফল');
}

function handleMe() {
    $user = getCurrentUser();
    if (!$user) sendError('লগইন করুন', 401);

    // Refresh user data from database to keep session information up to date
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, name, email, role, phone, address FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $fresh = $stmt->fetch();
    if (!$fresh) {
        session_destroy();
        sendError('লগইন করুন', 401);
    }
    $_SESSION['user'] = $fresh;
    sendSuccess(['user' => $fresh]);
}
?>