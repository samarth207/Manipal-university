<?php
require_once 'auth.php';
requireLogin();

$conn = getDBConnection();
$csrf = generateCSRF();
ensureUploadDirs();
$msg = '';

// Handle add/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF($_POST['csrf_token'] ?? '');
    
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $name = trim($_POST['name'] ?? '');
            $bio = trim($_POST['bio'] ?? '');
            $author_page = trim($_POST['author_page'] ?? '');
            $image = '';
            
            if (empty($name)) {
                $msg = 'error:Author name is required.';
            } else {
                // Handle author image upload
                if (!empty($_FILES['author_image']['name'])) {
                    $file = $_FILES['author_image'];
                    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                    if (in_array($file['type'], $allowed) && $file['size'] <= 2 * 1024 * 1024) {
                        $image_info = getimagesize($file['tmp_name']);
                        if ($image_info !== false) {
                            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) $ext = 'jpg';
                            $filename = 'author-' . time() . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
                            $path = __DIR__ . '/../uploads/blog/authors/' . $filename;
                            if (move_uploaded_file($file['tmp_name'], $path)) {
                                $image = 'uploads/blog/authors/' . $filename;
                            }
                        }
                    }
                }
                
                $stmt = $conn->prepare("INSERT INTO blog_authors (name, bio, image, author_page) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $name, $bio, $image, $author_page);
                if ($stmt->execute()) {
                    $msg = 'success:Author added.';
                } else {
                    $msg = 'error:Error adding author.';
                }
                $stmt->close();
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                // Get image to delete
                $stmt = $conn->prepare("SELECT image FROM blog_authors WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $author = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                $stmt = $conn->prepare("DELETE FROM blog_authors WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
                
                if ($author && !empty($author['image'])) {
                    $file_path = __DIR__ . '/../' . $author['image'];
                    if (file_exists($file_path)) unlink($file_path);
                }
                $msg = 'success:Author deleted.';
            }
        }
    }
}

$authors = $conn->query("SELECT a.*, (SELECT COUNT(*) FROM blog_posts p WHERE p.author_id = a.id) as post_count FROM blog_authors a ORDER BY a.name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Manage Authors - Blog CMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="admin-layout">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Blog <span>CMS</span></h2>
            <p>manipalonlineadmission.in</p>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <a href="editor.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                New Blog Post
            </a>
            <div class="nav-divider"></div>
            <div class="nav-label">Manage</div>
            <a href="manage_categories.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
                Categories
            </a>
            <a href="manage_authors.php" class="active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Authors
            </a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="top-bar">
            <div style="display:flex;align-items:center;gap:12px;">
                <button class="mobile-menu-btn" onclick="document.getElementById('sidebar').classList.toggle('open')">&#9776;</button>
                <h1>Manage Authors</h1>
            </div>
            <div class="top-bar-actions">
                <a href="dashboard.php" class="btn btn-sm btn-secondary">&larr; Back</a>
            </div>
        </header>

        <div class="content-area">
            <?php if ($msg): $parts = explode(':', $msg, 2); ?>
                <div class="alert alert-<?php echo $parts[0]; ?>"><?php echo sanitize($parts[1]); ?></div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 1.5fr;gap:24px;">
                <!-- Add Author -->
                <div class="card">
                    <div class="card-header"><h3>Add New Author</h3></div>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                        <input type="hidden" name="action" value="add">
                        <div class="form-group">
                            <label>Author Name <span class="required">*</span></label>
                            <input type="text" name="name" required placeholder="Full name">
                        </div>
                        <div class="form-group">
                            <label>Bio <small>(Optional)</small></label>
                            <textarea name="bio" rows="3" placeholder="Short author bio..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Author Image <small>(Optional)</small></label>
                            <input type="file" name="author_image" accept="image/jpeg,image/png,image/webp" style="font-size:13px;">
                        </div>
                        <div class="form-group">
                            <label>Author Page URL <small>(Optional)</small></label>
                            <input type="url" name="author_page" placeholder="https://...">
                        </div>
                        <button type="submit" class="btn btn-primary">Add Author</button>
                    </form>
                </div>

                <!-- Authors List -->
                <div class="card">
                    <div class="card-header"><h3>All Authors (<?php echo count($authors); ?>)</h3></div>
                    <?php if (empty($authors)): ?>
                        <p style="color:#94a3b8;font-size:13px;">No authors yet.</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead><tr><th></th><th>Name</th><th>Bio</th><th>Posts</th><th>Action</th></tr></thead>
                            <tbody>
                            <?php foreach ($authors as $author): ?>
                                <tr>
                                    <td style="width:40px;">
                                        <?php if (!empty($author['image'])): ?>
                                            <img src="../<?php echo sanitize($author['image']); ?>" alt="<?php echo sanitize($author['name']); ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover;">
                                        <?php else: ?>
                                            <div style="width:36px;height:36px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:600;color:#64748b;">
                                                <?php echo strtoupper(substr($author['name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-weight:600;">
                                        <?php echo sanitize($author['name']); ?>
                                        <?php if (!empty($author['author_page'])): ?>
                                            <br><a href="<?php echo sanitize($author['author_page']); ?>" target="_blank" style="font-size:11px;color:#3b82f6;font-weight:400;">Profile &rarr;</a>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size:12px;color:#64748b;max-width:200px;">
                                        <?php echo sanitize(substr($author['bio'] ?? '', 0, 80)); ?><?php echo strlen($author['bio'] ?? '') > 80 ? '...' : ''; ?>
                                    </td>
                                    <td><?php echo $author['post_count']; ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this author? Posts will keep but show no author.');">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $author['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
<?php closeDBConnection($conn); ?>
