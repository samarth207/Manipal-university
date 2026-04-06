<?php
require_once 'auth.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$csrf = $data['csrf_token'] ?? '';

if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$name = trim($data['name'] ?? '');
if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Category name is required']);
    exit;
}

$slug = preg_replace('/[^a-z0-9-]/', '-', strtolower($name));
$slug = preg_replace('/-+/', '-', trim($slug, '-'));

$conn = getDBConnection();
$stmt = $conn->prepare("INSERT IGNORE INTO blog_categories (name, slug) VALUES (?, ?)");
$stmt->bind_param("ss", $name, $slug);

if ($stmt->execute()) {
    $id = $stmt->affected_rows > 0 ? $conn->insert_id : 0;
    if ($id) {
        echo json_encode(['success' => true, 'id' => $id, 'name' => $name, 'slug' => $slug]);
    } else {
        // Category already exists, get its ID
        $stmt2 = $conn->prepare("SELECT id FROM blog_categories WHERE slug = ?");
        $stmt2->bind_param("s", $slug);
        $stmt2->execute();
        $row = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();
        echo json_encode(['success' => true, 'id' => $row['id'], 'name' => $name, 'slug' => $slug]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error creating category']);
}

$stmt->close();
closeDBConnection($conn);
?>
