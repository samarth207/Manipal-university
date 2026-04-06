<?php
require_once 'auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

verifyCSRF($_POST['csrf_token'] ?? '');
ensureUploadDirs();

$conn = getDBConnection();
$conn->set_charset("utf8mb4");

$post_id = intval($_POST['post_id'] ?? 0);
$editing = $post_id > 0;

// Collect and sanitize inputs
$title = trim($_POST['title'] ?? '');
$url_slug = trim($_POST['url_slug'] ?? '');
$excerpt = trim($_POST['excerpt'] ?? '');
$content = $_POST['content'] ?? '';
$meta_title = trim($_POST['meta_title'] ?? '');
$meta_description = trim($_POST['meta_description'] ?? '');
$focus_keyword = trim($_POST['focus_keyword'] ?? '');
$primary_keyword = trim($_POST['primary_keyword'] ?? '');
$feature_image_alt = trim($_POST['feature_image_alt'] ?? '');
$feature_image_title = trim($_POST['feature_image_title'] ?? '');
$author_id = intval($_POST['author_id'] ?? 0) ?: null;
$status = $_POST['status'] ?? 'draft';
$publish_date = $_POST['publish_date'] ?? date('Y-m-d');
$publish_time = $_POST['publish_time'] ?? date('H:i');
$categories = $_POST['categories'] ?? [];
$tags_str = trim($_POST['tags'] ?? '');
$save_action = $_POST['save_action'] ?? 'draft';

// Override status based on save action
if ($save_action === 'publish') {
    $status = 'published';
} elseif ($save_action === 'draft' && $status !== 'scheduled') {
    $status = 'draft';
}

// Validate required fields
if (empty($title) || empty($url_slug) || empty($excerpt) || empty($content) || empty($focus_keyword)) {
    header('Location: editor.php' . ($editing ? "?id=$post_id" : '') . '&msg=error&detail=missing_fields');
    exit;
}

// Validate status
if (!in_array($status, ['draft', 'pending', 'published', 'scheduled'])) {
    $status = 'draft';
}

// Sanitize slug
$url_slug = preg_replace('/[^a-z0-9-]/', '', strtolower($url_slug));
$url_slug = preg_replace('/-+/', '-', $url_slug);
$url_slug = trim($url_slug, '-');

// Auto-fill meta title from title if empty
if (empty($meta_title)) {
    $meta_title = substr($title, 0, 60);
}
if (empty($meta_description)) {
    $meta_description = substr($excerpt, 0, 250);
}

// Generate table of contents from content headings
$toc = [];
if (preg_match_all('/<h([23])[^>]*>(.*?)<\/h[23]>/is', $content, $matches, PREG_SET_ORDER)) {
    foreach ($matches as $i => $match) {
        $level = intval($match[1]);
        $text = strip_tags($match[2]);
        $id = 'section-' . ($i + 1) . '-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($text));
        $id = substr($id, 0, 80);
        $toc[] = ['level' => $level, 'text' => $text, 'id' => $id];
        
        // Add id to heading in content
        $old_tag = $match[0];
        $new_tag = preg_replace('/<h([23])/', '<h$1 id="' . htmlspecialchars($id, ENT_QUOTES) . '"', $old_tag, 1);
        $content = str_replace($old_tag, $new_tag, $content);
    }
}
$toc_json = json_encode($toc);

// Handle feature image upload
$feature_image = $_POST['existing_feature_image'] ?? '';

if (isset($_POST['remove_feature_image'])) {
    $feature_image = '';
}

if (!empty($_FILES['feature_image']['name'])) {
    $file = $_FILES['feature_image'];
    $allowed_types = ['image/jpeg', 'image/webp', 'image/png'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowed_types)) {
        header('Location: editor.php' . ($editing ? "?id=$post_id" : '') . '&msg=error&detail=invalid_image');
        exit;
    }
    if ($file['size'] > $max_size) {
        header('Location: editor.php' . ($editing ? "?id=$post_id" : '') . '&msg=error&detail=image_too_large');
        exit;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        header('Location: editor.php' . ($editing ? "?id=$post_id" : '') . '&msg=error&detail=upload_error');
        exit;
    }
    
    // Validate it's actually an image
    $image_info = getimagesize($file['tmp_name']);
    if ($image_info === false) {
        header('Location: editor.php' . ($editing ? "?id=$post_id" : '') . '&msg=error&detail=invalid_image');
        exit;
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $ext = strtolower($ext);
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
        $ext = 'jpg';
    }
    $filename = $url_slug . '-feature-' . time() . '.' . $ext;
    $upload_path = __DIR__ . '/../uploads/blog/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        $feature_image = 'uploads/blog/' . $filename;
    }
}

// Check slug uniqueness
$slug_check_sql = "SELECT id FROM blog_posts WHERE url_slug = ?" . ($editing ? " AND id != ?" : "");
$stmt = $conn->prepare($slug_check_sql);
if ($editing) {
    $stmt->bind_param("si", $url_slug, $post_id);
} else {
    $stmt->bind_param("s", $url_slug);
}
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $url_slug = $url_slug . '-' . time();
}
$stmt->close();

// Purify content - allow safe HTML tags
$content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);

// Save or update post
if ($editing) {
    $stmt = $conn->prepare("UPDATE blog_posts SET 
        title=?, url_slug=?, excerpt=?, content=?, meta_title=?, meta_description=?, 
        focus_keyword=?, primary_keyword=?, feature_image=?, feature_image_alt=?, 
        feature_image_title=?, author_id=?, status=?, publish_date=?, publish_time=?, 
        table_of_contents=?
        WHERE id=?");
    $stmt->bind_param("sssssssssssissssi",
        $title, $url_slug, $excerpt, $content, $meta_title, $meta_description,
        $focus_keyword, $primary_keyword, $feature_image, $feature_image_alt,
        $feature_image_title, $author_id, $status, $publish_date, $publish_time,
        $toc_json, $post_id
    );
} else {
    $stmt = $conn->prepare("INSERT INTO blog_posts 
        (title, url_slug, excerpt, content, meta_title, meta_description, 
        focus_keyword, primary_keyword, feature_image, feature_image_alt, 
        feature_image_title, author_id, status, publish_date, publish_time, table_of_contents) 
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("sssssssssssissss",
        $title, $url_slug, $excerpt, $content, $meta_title, $meta_description,
        $focus_keyword, $primary_keyword, $feature_image, $feature_image_alt,
        $feature_image_title, $author_id, $status, $publish_date, $publish_time,
        $toc_json
    );
}

if (!$stmt->execute()) {
    error_log("Blog save error: " . $stmt->error);
    $stmt->close();
    closeDBConnection($conn);
    header('Location: editor.php' . ($editing ? "?id=$post_id" : '') . '&msg=error');
    exit;
}

if (!$editing) {
    $post_id = $conn->insert_id;
}
$stmt->close();

// Save categories
$conn->query("DELETE FROM blog_post_categories WHERE post_id = $post_id");
if (!empty($categories)) {
    $cat_stmt = $conn->prepare("INSERT IGNORE INTO blog_post_categories (post_id, category_id) VALUES (?, ?)");
    foreach ($categories as $cat_id) {
        $cat_id = intval($cat_id);
        $cat_stmt->bind_param("ii", $post_id, $cat_id);
        $cat_stmt->execute();
    }
    $cat_stmt->close();
}

// Save tags
$conn->query("DELETE FROM blog_post_tags WHERE post_id = $post_id");
if (!empty($tags_str)) {
    $tags = array_filter(array_map('trim', explode(',', $tags_str)));
    foreach ($tags as $tag_name) {
        if (empty($tag_name)) continue;
        $tag_slug = preg_replace('/[^a-z0-9-]/', '-', strtolower($tag_name));
        $tag_slug = preg_replace('/-+/', '-', $tag_slug);
        
        // Insert tag if doesn't exist
        $stmt = $conn->prepare("INSERT IGNORE INTO blog_tags (name, slug) VALUES (?, ?)");
        $stmt->bind_param("ss", $tag_name, $tag_slug);
        $stmt->execute();
        $stmt->close();
        
        // Get tag id
        $stmt = $conn->prepare("SELECT id FROM blog_tags WHERE slug = ?");
        $stmt->bind_param("s", $tag_slug);
        $stmt->execute();
        $tag_row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($tag_row) {
            $stmt = $conn->prepare("INSERT IGNORE INTO blog_post_tags (post_id, tag_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $post_id, $tag_row['id']);
            $stmt->execute();
            $stmt->close();
        }
    }
}

closeDBConnection($conn);
header("Location: editor.php?id=$post_id&msg=saved");
exit;
?>
