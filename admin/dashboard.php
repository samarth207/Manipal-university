<?php
require_once 'auth.php';
requireLogin();

$conn = getDBConnection();
$csrf = generateCSRF();

// Handle change password
$pw_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    verifyCSRF($_POST['csrf_token'] ?? '');
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (empty($current) || empty($new) || empty($confirm)) {
        $pw_msg = 'error:All fields are required.';
    } elseif ($new !== $confirm) {
        $pw_msg = 'error:New passwords do not match.';
    } elseif (strlen($new) < 8) {
        $pw_msg = 'error:Password must be at least 8 characters.';
    } else {
        $stmt = $conn->prepare("SELECT password_hash FROM blog_admins WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['admin_id']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (password_verify($current, $row['password_hash'])) {
            $new_hash = password_hash($new, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE blog_admins SET password_hash = ? WHERE id = ?");
            $stmt->bind_param("si", $new_hash, $_SESSION['admin_id']);
            $stmt->execute();
            $stmt->close();
            $pw_msg = 'success:Password changed successfully.';
        } else {
            $pw_msg = 'error:Current password is incorrect.';
        }
    }
}

// Fetch stats
$stats = [];
$result = $conn->query("SELECT status, COUNT(*) as cnt FROM blog_posts GROUP BY status");
while ($row = $result->fetch_assoc()) {
    $stats[$row['status']] = $row['cnt'];
}
$total_posts = array_sum($stats);

$cat_count = $conn->query("SELECT COUNT(*) as cnt FROM blog_categories")->fetch_assoc()['cnt'];
$tag_count = $conn->query("SELECT COUNT(*) as cnt FROM blog_tags")->fetch_assoc()['cnt'];

// Fetch posts with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($page - 1) * $per_page;
$status_filter = $_GET['status'] ?? '';

$where = "";
$params = [];
$types = "";
if ($status_filter && in_array($status_filter, ['draft','pending','published','scheduled'])) {
    $where = "WHERE p.status = ?";
    $params[] = $status_filter;
    $types = "s";
}

$count_sql = "SELECT COUNT(*) as cnt FROM blog_posts p $where";
if ($types) {
    $stmt = $conn->prepare($count_sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();
} else {
    $total = $conn->query($count_sql)->fetch_assoc()['cnt'];
}
$total_pages = max(1, ceil($total / $per_page));

$sql = "SELECT p.*, a.name as author_name 
        FROM blog_posts p 
        LEFT JOIN blog_authors a ON p.author_id = a.id 
        $where 
        ORDER BY p.updated_at DESC 
        LIMIT $per_page OFFSET $offset";

if ($types) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $posts = $stmt->get_result();
    $stmt->close();
} else {
    $posts = $conn->query($sql);
}

// Success/error messages from redirects
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Dashboard - Blog CMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Blog <span>CMS</span></h2>
            <p>manipalonlineadmission.in</p>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="active">
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
            <a href="manage_authors.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Authors
            </a>
            <div class="nav-divider"></div>
            <a href="../blog.php" target="_blank">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                View Blog
            </a>
            <a href="../index.html" target="_blank">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                View Website
            </a>
        </nav>
    </aside>

    <!-- Main -->
    <main class="main-content">
        <header class="top-bar">
            <div style="display:flex;align-items:center;gap:12px;">
                <button class="mobile-menu-btn" onclick="document.getElementById('sidebar').classList.toggle('open')">&#9776;</button>
                <h1>Dashboard</h1>
            </div>
            <div class="top-bar-actions">
                <span class="admin-name">Hi, <?php echo sanitize(getAdminName()); ?></span>
                <button class="btn btn-sm btn-outline" onclick="document.getElementById('pwModal').classList.add('active')">Change Password</button>
                <a href="logout.php" class="btn btn-sm btn-secondary">Logout</a>
            </div>
        </header>

        <div class="content-area">
            <?php if ($msg === 'deleted'): ?>
                <div class="alert alert-success">Blog post deleted successfully.</div>
            <?php elseif ($msg === 'saved'): ?>
                <div class="alert alert-success">Blog post saved successfully.</div>
            <?php endif; ?>

            <?php if ($pw_msg): 
                $pw_parts = explode(':', $pw_msg, 2);
            ?>
                <div class="alert alert-<?php echo $pw_parts[0] === 'success' ? 'success' : 'error'; ?>">
                    <?php echo sanitize($pw_parts[1]); ?>
                </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_posts; ?></div>
                    <div class="stat-label">Total Posts</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['published'] ?? 0; ?></div>
                    <div class="stat-label">Published</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo ($stats['draft'] ?? 0) + ($stats['pending'] ?? 0); ?></div>
                    <div class="stat-label">Drafts / Pending</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $cat_count; ?></div>
                    <div class="stat-label">Categories</div>
                </div>
            </div>

            <!-- Posts Table -->
            <div class="card">
                <div class="card-header">
                    <h3>All Blog Posts</h3>
                    <div class="actions-row">
                        <select onchange="window.location='dashboard.php?status='+this.value" style="padding:7px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;font-family:inherit;">
                            <option value="">All Status</option>
                            <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>Published</option>
                            <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="scheduled" <?php echo $status_filter === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                        </select>
                        <a href="editor.php" class="btn btn-primary">+ New Post</a>
                    </div>
                </div>

                <?php if ($posts->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($post = $posts->fetch_assoc()): ?>
                        <tr>
                            <td class="post-title">
                                <a href="editor.php?id=<?php echo $post['id']; ?>"><?php echo sanitize($post['title']); ?></a>
                                <div style="font-size:11px;color:#94a3b8;margin-top:2px;">/blog/<?php echo sanitize($post['url_slug']); ?></div>
                            </td>
                            <td><?php echo sanitize($post['author_name'] ?? '—'); ?></td>
                            <td><span class="badge badge-<?php echo $post['status']; ?>"><?php echo $post['status']; ?></span></td>
                            <td style="font-size:12px;color:#64748b;">
                                <?php 
                                    if ($post['publish_date']) echo date('M j, Y', strtotime($post['publish_date']));
                                    else echo date('M j, Y', strtotime($post['created_at']));
                                ?>
                            </td>
                            <td>
                                <div class="actions-row">
                                    <a href="editor.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                                    <?php if ($post['status'] === 'published'): ?>
                                        <a href="../blog/<?php echo sanitize($post['url_slug']); ?>" target="_blank" class="btn btn-sm btn-outline">View</a>
                                    <?php endif; ?>
                                    <form method="POST" action="delete_blog.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>

                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php $qs = $status_filter ? "&status=$status_filter" : ""; ?>
                        <a href="dashboard.php?page=<?php echo $i . $qs; ?>" class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <div class="empty-state">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    <h3>No blog posts yet</h3>
                    <p>Create your first blog post to get started.</p>
                    <a href="editor.php" class="btn btn-primary">+ Create Blog Post</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- Change Password Modal -->
<div class="modal-overlay" id="pwModal">
    <div class="modal">
        <h3>Change Password</h3>
        <form method="POST" action="dashboard.php">
            <input type="hidden" name="action" value="change_password">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" required>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" required minlength="8">
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" required minlength="8">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('pwModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Password</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('pwModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('active');
});
</script>
</body>
</html>
<?php closeDBConnection($conn); ?>
