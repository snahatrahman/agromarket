<?php
require_once 'config.php';

$user = requireRole('farmer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError('POST method ব্যবহার করুন');
if (!isset($_FILES['image'])) sendError('Image file দিন');

$file = $_FILES['image'];
$allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

if ($file['error'] !== UPLOAD_ERR_OK) sendError('Upload ব্যর্থ হয়েছে');
if (!in_array($file['type'], $allowed)) sendError('শুধুমাত্র JPG, PNG, WEBP ছবি দেওয়া যাবে');
if ($file['size'] > 3 * 1024 * 1024) sendError('ছবির সাইজ ৩MB এর বেশি হওয়া যাবে না');

// Verify it's actually an image
$imageInfo = getimagesize($file['tmp_name']);
if (!$imageInfo) sendError('সঠিক ছবি ফাইল দিন');

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'product_' . time() . '_' . $user['id'] . '.' . strtolower($ext);
$destination = UPLOAD_DIR . $filename;

if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

if (!move_uploaded_file($file['tmp_name'], $destination)) sendError('ছবি সংরক্ষণ ব্যর্থ হয়েছে');

$url = UPLOAD_URL . $filename;
sendSuccess(['url' => $url], 'ছবি সফলভাবে আপলোড হয়েছে');
?>