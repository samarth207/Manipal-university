<?php
require_once 'auth.php';
requireLogin();
ensureUploadDirs();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Verify CSRF
$csrf = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

if (empty($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file'];
$type = $_POST['type'] ?? 'content'; // 'content' or 'author'

// Validate file
$allowed_types = ['image/jpeg', 'image/webp', 'image/png', 'image/gif'];
$max_size = 5 * 1024 * 1024; // 5MB

if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, WebP and GIF allowed.']);
    exit;
}

if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum 5MB allowed.']);
    exit;
}

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Upload error code: ' . $file['error']]);
    exit;
}

// Validate it's actually an image
$image_info = getimagesize($file['tmp_name']);
if ($image_info === false) {
    echo json_encode(['success' => false, 'message' => 'File is not a valid image.']);
    exit;
}

// Determine upload directory
$sub_dir = ($type === 'author') ? 'authors/' : 'content/';
$upload_dir = __DIR__ . '/../uploads/blog/' . $sub_dir;

// Generate safe filename
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
    $ext = 'jpg';
}
$filename = 'img-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
$upload_path = $upload_dir . $filename;

if (move_uploaded_file($file['tmp_name'], $upload_path)) {
    $url = '/uploads/blog/' . $sub_dir . $filename;
    echo json_encode([
        'success' => true,
        'url' => $url,
        'location' => $url // TinyMCE uses 'location' key
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file.']);
}
?>
