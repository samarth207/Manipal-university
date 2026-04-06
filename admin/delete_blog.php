<?php
require_once 'auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

verifyCSRF($_POST['csrf_token'] ?? '');

$post_id = intval($_POST['post_id'] ?? 0);
if ($post_id <= 0) {
    header('Location: dashboard.php');
    exit;
}

$conn = getDBConnection();

// Get feature image path to delete file
$stmt = $conn->prepare("SELECT feature_image FROM blog_posts WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($post) {
    // Delete the post (cascades to categories and tags relations)
    $stmt = $conn->prepare("DELETE FROM blog_posts WHERE id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $stmt->close();
    
    // Delete feature image file if exists
    if (!empty($post['feature_image'])) {
        $file_path = __DIR__ . '/../' . $post['feature_image'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
}

closeDBConnection($conn);
header('Location: dashboard.php?msg=deleted');
exit;
?>
