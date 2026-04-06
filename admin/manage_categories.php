<?php
require_once 'auth.php';
requireLogin();

$conn = getDBConnection();
$csrf = generateCSRF();
$msg = '';

// Handle add/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRF($_POST['csrf_token'] ?? '');
    
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $name = trim($_POST['name'] ?? '');
            if ($name) {
                $slug = preg_replace('/[^a-z0-9-]/', '-', strtolower($name));
                $slug = preg_replace('/-+/', '-', trim($slug, '-'));
                $stmt = $conn->prepare("INSERT IGNORE INTO blog_categories (name, slug) VALUES (?, ?)");
                $stmt->bind_param("ss", $name, $slug);
                $stmt->execute();
                $msg = $stmt->affected_rows > 0 ? 'success:Category added.' : 'error:Category already exists.';
                $stmt->close();
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $conn->prepare("DELETE FROM blog_categories WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $msg = 'success:Category deleted.';
                $stmt->close();
            }
        }
    }
}

$categories = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM blog_post_categories pc WHERE pc.category_id = c.id) as post_count FROM blog_categories c ORDER BY c.name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Manage Categories - Blog CMS</title>
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
            <a href="manage_categories.php" class="active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
                Categories
            </a>
            <a href="manage_authors.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Authors
            </a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="top-bar">
            <div style="display:flex;align-items:center;gap:12px;">
                <button class="mobile-menu-btn" onclick="document.getElementById('sidebar').classList.toggle('open')">&#9776;</button>
                <h1>Manage Categories</h1>
            </div>
            <div class="top-bar-actions">
                <a href="dashboard.php" class="btn btn-sm btn-secondary">&larr; Back</a>
            </div>
        </header>

        <div class="content-area">
            <?php if ($msg): $parts = explode(':', $msg, 2); ?>
                <div class="alert alert-<?php echo $parts[0]; ?>"><?php echo sanitize($parts[1]); ?></div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
                <!-- Add Category -->
                <div class="card">
                    <div class="card-header"><h3>Add New Category</h3></div>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                        <input type="hidden" name="action" value="add">
                        <div class="form-group">
                            <label>Category Name <span class="required">*</span></label>
                            <input type="text" name="name" required placeholder="e.g., Technology">
                        </div>
                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </form>
                </div>

                <!-- Categories List -->
                <div class="card">
                    <div class="card-header"><h3>All Categories (<?php echo count($categories); ?>)</h3></div>
                    <?php if (empty($categories)): ?>
                        <p style="color:#94a3b8;font-size:13px;">No categories yet.</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead><tr><th>Name</th><th>Slug</th><th>Posts</th><th>Action</th></tr></thead>
                            <tbody>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td style="font-weight:600;"><?php echo sanitize($cat['name']); ?></td>
                                    <td style="font-size:12px;color:#64748b;"><?php echo sanitize($cat['slug']); ?></td>
                                    <td><?php echo $cat['post_count']; ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this category?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
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
