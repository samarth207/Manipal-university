<?php
require_once 'auth.php';
requireLogin();

$conn = getDBConnection();
$csrf = generateCSRF();
ensureUploadDirs();

// Load post if editing
$post = null;
$post_categories = [];
$post_tags = [];
$editing = false;

if (isset($_GET['id'])) {
    $editing = true;
    $stmt = $conn->prepare("SELECT * FROM blog_posts WHERE id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$post) {
        header('Location: dashboard.php');
        exit;
    }
    
    // Get post categories
    $stmt = $conn->prepare("SELECT category_id FROM blog_post_categories WHERE post_id = ?");
    $stmt->bind_param("i", $post['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $post_categories[] = $row['category_id'];
    }
    $stmt->close();
    
    // Get post tags
    $stmt = $conn->prepare("SELECT t.name FROM blog_post_tags pt JOIN blog_tags t ON pt.tag_id = t.id WHERE pt.post_id = ?");
    $stmt->bind_param("i", $post['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $post_tags[] = $row['name'];
    }
    $stmt->close();
}

// Fetch categories and authors
$categories = $conn->query("SELECT * FROM blog_categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$authors = $conn->query("SELECT * FROM blog_authors ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo $editing ? 'Edit' : 'New'; ?> Blog Post - Blog CMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>
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
            <a href="dashboard.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <a href="editor.php" class="active">
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
        </nav>
    </aside>

    <!-- Main -->
    <main class="main-content">
        <header class="top-bar">
            <div style="display:flex;align-items:center;gap:12px;">
                <button class="mobile-menu-btn" onclick="document.getElementById('sidebar').classList.toggle('open')">&#9776;</button>
                <h1><?php echo $editing ? 'Edit Blog Post' : 'Create New Blog Post'; ?></h1>
            </div>
            <div class="top-bar-actions">
                <a href="dashboard.php" class="btn btn-sm btn-secondary">&larr; Back</a>
            </div>
        </header>

        <div class="content-area">
            <?php if ($msg === 'saved'): ?>
                <div class="alert alert-success">Blog post saved successfully.</div>
            <?php endif; ?>

            <form id="blogForm" method="POST" action="save_blog.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="post_id" value="<?php echo $post['id'] ?? ''; ?>">
                
                <div class="editor-layout">
                    <!-- LEFT: Main Content -->
                    <div class="editor-main">
                        <!-- Blog Title (H1) -->
                        <div class="card">
                            <div class="form-group">
                                <label>Blog Title (H1) <span class="required">*</span> <span class="char-count" id="titleCount">0/100</span></label>
                                <input type="text" name="title" id="title" maxlength="100" required 
                                    value="<?php echo sanitize($post['title'] ?? ''); ?>"
                                    placeholder="Enter your blog title..."
                                    oninput="updateCharCount(this, 'titleCount', 100); updateSlug();">
                                <div class="help-text">This will appear as H1 on the blog page. Keep it under 70 characters for best SEO.</div>
                            </div>

                            <!-- URL Slug -->
                            <div class="form-group">
                                <label>URL Slug <span class="required">*</span></label>
                                <div style="display:flex;gap:6px;align-items:center;">
                                    <span style="font-size:13px;color:#94a3b8;white-space:nowrap;">/blog/</span>
                                    <input type="text" name="url_slug" id="url_slug" required
                                        value="<?php echo sanitize($post['url_slug'] ?? ''); ?>"
                                        placeholder="auto-generated-from-title"
                                        pattern="[a-z0-9-]+"
                                        oninput="this.value = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '');">
                                </div>
                                <div class="slug-preview" id="slugPreview"></div>
                            </div>
                        </div>

                        <!-- Short Description / Excerpt -->
                        <div class="card">
                            <div class="form-group">
                                <label>Short Description / Excerpt <span class="required">*</span> <span class="char-count" id="excerptCount">0/250</span></label>
                                <textarea name="excerpt" id="excerpt" maxlength="250" required rows="3"
                                    placeholder="Brief description for blog listing and SEO..."
                                    oninput="updateCharCount(this, 'excerptCount', 250);"><?php echo sanitize($post['excerpt'] ?? ''); ?></textarea>
                                <div class="help-text">Appears in blog listing cards and used as default meta description.</div>
                            </div>
                        </div>

                        <!-- Blog Content Editor -->
                        <div class="card">
                            <div class="form-group">
                                <label>Blog Content <span class="required">*</span></label>
                                <textarea name="content" id="blog-content"><?php echo htmlspecialchars($post['content'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Sidebar -->
                    <div class="editor-sidebar">
                        <!-- Publish Settings -->
                        <div class="card">
                            <div class="card-header">
                                <h3>Publish</h3>
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" id="status">
                                    <option value="draft" <?php echo ($post['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="pending" <?php echo ($post['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending Review</option>
                                    <option value="published" <?php echo ($post['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                                    <option value="scheduled" <?php echo ($post['status'] ?? '') === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Publish Date</label>
                                <input type="date" name="publish_date" value="<?php echo sanitize($post['publish_date'] ?? date('Y-m-d')); ?>">
                            </div>
                            <div class="form-group">
                                <label>Publish Time</label>
                                <input type="time" name="publish_time" value="<?php echo sanitize($post['publish_time'] ?? date('H:i')); ?>">
                            </div>
                            <?php if ($editing && $post['updated_at']): ?>
                                <div class="help-text">Last updated: <?php echo date('M j, Y g:i A', strtotime($post['updated_at'])); ?></div>
                            <?php endif; ?>
                            <div style="margin-top:14px;display:flex;gap:8px;">
                                <button type="submit" name="save_action" value="draft" class="btn btn-secondary" style="flex:1">Save Draft</button>
                                <button type="submit" name="save_action" value="publish" class="btn btn-primary" style="flex:1">Publish</button>
                            </div>
                        </div>

                        <!-- SEO / Meta Data -->
                        <div class="card">
                            <div class="card-header">
                                <h3>SEO Meta Data</h3>
                            </div>
                            <div class="form-group">
                                <label>Meta Title <span class="char-count" id="metaTitleCount">0/60</span></label>
                                <input type="text" name="meta_title" id="meta_title" maxlength="60"
                                    value="<?php echo sanitize($post['meta_title'] ?? ''); ?>"
                                    placeholder="SEO title (auto-fills from blog title)"
                                    oninput="updateCharCount(this, 'metaTitleCount', 60); updateSEOPreview();">
                            </div>
                            <div class="form-group">
                                <label>Meta Description <span class="char-count" id="metaDescCount">0/250</span></label>
                                <textarea name="meta_description" id="meta_description" maxlength="250" rows="3"
                                    placeholder="SEO description (auto-fills from excerpt)"
                                    oninput="updateCharCount(this, 'metaDescCount', 250); updateSEOPreview();"><?php echo sanitize($post['meta_description'] ?? ''); ?></textarea>
                                <div class="help-text">Recommended: 150-160 characters for Google.</div>
                            </div>
                            <div class="form-group">
                                <label>Focus Keyword <span class="required">*</span></label>
                                <input type="text" name="focus_keyword" required
                                    value="<?php echo sanitize($post['focus_keyword'] ?? ''); ?>"
                                    placeholder="Main keyword for this post">
                            </div>
                            <div class="form-group">
                                <label>Primary Keyword</label>
                                <input type="text" name="primary_keyword"
                                    value="<?php echo sanitize($post['primary_keyword'] ?? ''); ?>"
                                    placeholder="Primary keyword for analytics">
                            </div>
                            <!-- SEO Preview -->
                            <div style="background:#f8fafc;border-radius:6px;padding:12px;margin-top:8px;">
                                <div style="font-size:10px;color:#94a3b8;text-transform:uppercase;margin-bottom:6px;">Google Preview</div>
                                <div id="seoPreviewTitle" style="color:#1a0dab;font-size:16px;font-weight:500;line-height:1.3;word-break:break-word;margin-bottom:2px;">Blog Title</div>
                                <div id="seoPreviewUrl" style="color:#006621;font-size:13px;margin-bottom:2px;">manipalonlineadmission.in/blog/url-slug</div>
                                <div id="seoPreviewDesc" style="color:#545454;font-size:13px;line-height:1.4;">Meta description will appear here...</div>
                            </div>
                        </div>

                        <!-- Feature Image -->
                        <div class="card">
                            <div class="card-header">
                                <h3>Feature Image</h3>
                            </div>
                            <div class="form-group">
                                <div class="image-upload-area" id="featureImageArea" onclick="document.getElementById('feature_image').click();">
                                    <?php if (!empty($post['feature_image'])): ?>
                                        <img src="../<?php echo sanitize($post['feature_image']); ?>" alt="Feature Image" id="featurePreview">
                                    <?php else: ?>
                                        <div class="upload-icon">&#128247;</div>
                                        <p>Click to upload feature image<br><small>JPG / WebP, 1200x628px recommended</small></p>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="feature_image" id="feature_image" accept="image/jpeg,image/webp,image/png" style="display:none;" onchange="previewFeatureImage(this);">
                                <?php if (!empty($post['feature_image'])): ?>
                                    <input type="hidden" name="existing_feature_image" value="<?php echo sanitize($post['feature_image']); ?>">
                                    <button type="button" class="btn btn-sm btn-outline" style="margin-top:8px;width:100%;" onclick="removeFeatureImage();">Remove Image</button>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label>Image Alt Text <span class="required">*</span></label>
                                <input type="text" name="feature_image_alt" id="feature_image_alt" 
                                    value="<?php echo sanitize($post['feature_image_alt'] ?? ''); ?>"
                                    placeholder="Descriptive alt text for the image">
                            </div>
                            <div class="form-group">
                                <label>Image Title <small>(Optional)</small></label>
                                <input type="text" name="feature_image_title"
                                    value="<?php echo sanitize($post['feature_image_title'] ?? ''); ?>"
                                    placeholder="Image title attribute">
                            </div>
                        </div>

                        <!-- Author -->
                        <div class="card">
                            <div class="card-header">
                                <h3>Author</h3>
                            </div>
                            <div class="form-group">
                                <label>Author Name</label>
                                <select name="author_id">
                                    <option value="">— Select Author —</option>
                                    <?php foreach ($authors as $author): ?>
                                        <option value="<?php echo $author['id']; ?>" <?php echo ($post['author_id'] ?? '') == $author['id'] ? 'selected' : ''; ?>>
                                            <?php echo sanitize($author['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="help-text"><a href="manage_authors.php" style="color:var(--admin-primary);text-decoration:none;">+ Add new author</a></div>
                            </div>
                        </div>

                        <!-- Categories -->
                        <div class="card">
                            <div class="card-header">
                                <h3>Categories <span class="required">*</span></h3>
                            </div>
                            <div class="category-list">
                                <?php foreach ($categories as $cat): ?>
                                    <label>
                                        <input type="checkbox" name="categories[]" value="<?php echo $cat['id']; ?>"
                                            <?php echo in_array($cat['id'], $post_categories) ? 'checked' : ''; ?>>
                                        <?php echo sanitize($cat['name']); ?>
                                    </label>
                                <?php endforeach; ?>
                                <?php if (empty($categories)): ?>
                                    <p style="font-size:12px;color:#94a3b8;">No categories yet.</p>
                                <?php endif; ?>
                            </div>
                            <div class="add-category-inline">
                                <input type="text" id="newCatName" placeholder="New category...">
                                <button type="button" class="btn btn-sm btn-primary" onclick="addCategory();">Add</button>
                            </div>
                        </div>

                        <!-- Tags -->
                        <div class="card">
                            <div class="card-header">
                                <h3>Tags</h3>
                            </div>
                            <div class="tags-container" onclick="this.querySelector('input').focus();">
                                <?php foreach ($post_tags as $tag): ?>
                                    <span class="tag"><?php echo sanitize($tag); ?><span class="remove-tag" onclick="this.parentElement.remove();updateTagsInput();">&times;</span></span>
                                <?php endforeach; ?>
                                <input type="text" id="tagInput" placeholder="Type and press Enter..." onkeydown="handleTagInput(event);">
                            </div>
                            <input type="hidden" name="tags" id="tagsHidden" value="<?php echo sanitize(implode(',', $post_tags)); ?>">
                            <div class="help-text">Press Enter or comma to add a tag.</div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
// Character counters
function updateCharCount(input, countId, max) {
    const count = input.value.length;
    const el = document.getElementById(countId);
    el.textContent = count + '/' + max;
    el.style.color = count > max * 0.9 ? '#ef4444' : '#94a3b8';
}

// Auto slug generation
function updateSlug() {
    const title = document.getElementById('title').value;
    const slugField = document.getElementById('url_slug');
    if (!slugField.dataset.manual) {
        const slug = title.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/[\s]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
        slugField.value = slug;
    }
    updateSEOPreview();
}
document.getElementById('url_slug').addEventListener('input', function() {
    this.dataset.manual = '1';
});

// SEO Preview
function updateSEOPreview() {
    const title = document.getElementById('meta_title').value || document.getElementById('title').value || 'Blog Title';
    const slug = document.getElementById('url_slug').value || 'url-slug';
    const desc = document.getElementById('meta_description').value || document.getElementById('excerpt').value || 'Meta description will appear here...';
    
    document.getElementById('seoPreviewTitle').textContent = title;
    document.getElementById('seoPreviewUrl').textContent = 'manipalonlineadmission.in/blog/' + slug;
    document.getElementById('seoPreviewDesc').textContent = desc;
}

// Feature Image Preview
function previewFeatureImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const area = document.getElementById('featureImageArea');
            area.innerHTML = '<img src="' + e.target.result + '" alt="Feature Image Preview" id="featurePreview">';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function removeFeatureImage() {
    const area = document.getElementById('featureImageArea');
    area.innerHTML = '<div class="upload-icon">&#128247;</div><p>Click to upload feature image<br><small>JPG / WebP, 1200x628px recommended</small></p>';
    document.getElementById('feature_image').value = '';
    const existing = document.querySelector('[name="existing_feature_image"]');
    if (existing) existing.remove();
    // Add a flag to indicate removal
    let removeFlag = document.createElement('input');
    removeFlag.type = 'hidden';
    removeFlag.name = 'remove_feature_image';
    removeFlag.value = '1';
    document.getElementById('blogForm').appendChild(removeFlag);
}

// Tags
function handleTagInput(e) {
    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        const input = e.target;
        const tag = input.value.trim().replace(/,/g, '');
        if (tag) {
            addTag(tag);
            input.value = '';
        }
    }
}

function addTag(name) {
    const container = document.querySelector('.tags-container');
    const input = container.querySelector('input');
    const span = document.createElement('span');
    span.className = 'tag';
    span.innerHTML = escapeHtml(name) + '<span class="remove-tag" onclick="this.parentElement.remove();updateTagsInput();">&times;</span>';
    container.insertBefore(span, input);
    updateTagsInput();
}

function updateTagsInput() {
    const tags = [...document.querySelectorAll('.tags-container .tag')].map(t => t.textContent.replace('×', '').trim());
    document.getElementById('tagsHidden').value = tags.join(',');
}

function escapeHtml(text) {
    const d = document.createElement('div');
    d.textContent = text;
    return d.innerHTML;
}

// Add Category
function addCategory() {
    const name = document.getElementById('newCatName').value.trim();
    if (!name) return;
    
    fetch('save_category_ajax.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({name: name, csrf_token: '<?php echo $csrf; ?>'})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const list = document.querySelector('.category-list');
            const label = document.createElement('label');
            label.innerHTML = '<input type="checkbox" name="categories[]" value="' + data.id + '" checked> ' + escapeHtml(data.name);
            list.appendChild(label);
            document.getElementById('newCatName').value = '';
        } else {
            alert(data.message || 'Error adding category');
        }
    })
    .catch(err => alert('Error: ' + err.message));
}

// TinyMCE Init
tinymce.init({
    selector: '#blog-content',
    height: 600,
    menubar: 'file edit view insert format table',
    plugins: 'lists link image table code fullscreen preview searchreplace wordcount help',
    toolbar: 'undo redo | blocks | bold italic underline strikethrough | bullist numlist | alignleft aligncenter alignright | link image table | faqblock | code fullscreen preview',
    block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4',
    image_advtab: true,
    image_caption: true,
    image_title: true,
    link_target_list: [
        {title: 'Same window', value: ''},
        {title: 'New window', value: '_blank'}
    ],
    link_default_target: '_blank',
    link_rel_list: [
        {title: 'None', value: ''},
        {title: 'No Follow', value: 'nofollow'},
        {title: 'No Opener', value: 'noopener'},
        {title: 'No Follow No Opener', value: 'nofollow noopener'}
    ],
    images_upload_handler: function(blobInfo, progress) {
        return new Promise(function(resolve, reject) {
            var formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());
            formData.append('csrf_token', '<?php echo $csrf; ?>');
            formData.append('type', 'content');
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'upload_image.php');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var json = JSON.parse(xhr.responseText);
                    if (json.success) {
                        resolve(json.url);
                    } else {
                        reject('Upload failed: ' + json.message);
                    }
                } else {
                    reject('Upload failed with status: ' + xhr.status);
                }
            };
            xhr.onerror = function() { reject('Upload failed due to network error.'); };
            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) progress(e.loaded / e.total * 100);
            };
            xhr.send(formData);
        });
    },
    // Force alt text on images
    image_description: true,
    setup: function(editor) {
        // FAQ Block Button
        editor.ui.registry.addButton('faqblock', {
            text: 'FAQ',
            tooltip: 'Insert FAQ Section',
            onAction: function() {
                editor.insertContent(
                    '<div class="faq-section">' +
                    '<h2>Frequently Asked Questions</h2>' +
                    '<div class="faq-item" itemscope itemtype="https://schema.org/Question">' +
                    '<h3 itemprop="name">Your question here?</h3>' +
                    '<div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">' +
                    '<p itemprop="text">Your answer here.</p>' +
                    '</div></div>' +
                    '<div class="faq-item" itemscope itemtype="https://schema.org/Question">' +
                    '<h3 itemprop="name">Another question?</h3>' +
                    '<div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">' +
                    '<p itemprop="text">Another answer.</p>' +
                    '</div></div>' +
                    '</div>'
                );
            }
        });

        // Validate image alt text
        editor.on('NodeChange', function(e) {
            if (e.element.tagName === 'IMG' && !e.element.alt) {
                e.element.style.outline = '3px solid red';
                e.element.title = 'WARNING: Alt text is missing!';
            }
        });
    },
    content_style: `
        body { font-family: 'Inter', system-ui, sans-serif; font-size: 15px; line-height: 1.7; color: #333; max-width: 800px; margin: 0 auto; padding: 16px; }
        h2 { font-size: 24px; margin-top: 32px; margin-bottom: 12px; color: #1a1a2e; }
        h3 { font-size: 20px; margin-top: 24px; margin-bottom: 10px; color: #1a1a2e; }
        h4 { font-size: 17px; margin-top: 20px; margin-bottom: 8px; color: #1a1a2e; }
        img { max-width: 100%; height: auto; border-radius: 8px; }
        table { border-collapse: collapse; width: 100%; margin: 16px 0; }
        th, td { border: 1px solid #e2e8f0; padding: 10px 14px; text-align: left; }
        th { background: #f8fafc; font-weight: 600; }
        .faq-section { background: #f8f9fa; padding: 24px; border-radius: 8px; margin: 24px 0; }
        .faq-item { margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0; }
        .faq-item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        a { color: #ff6a00; }
        blockquote { border-left: 4px solid #ff6a00; padding: 12px 20px; margin: 16px 0; background: #fff5eb; }
    `
});

// Init char counts on load
window.addEventListener('DOMContentLoaded', function() {
    ['title', 'excerpt', 'meta_title', 'meta_description'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el && el.value) {
            var countMap = {title: 'titleCount', excerpt: 'excerptCount', meta_title: 'metaTitleCount', meta_description: 'metaDescCount'};
            var maxMap = {title: 100, excerpt: 250, meta_title: 60, meta_description: 250};
            updateCharCount(el, countMap[id], maxMap[id]);
        }
    });
    updateSEOPreview();
});

// Form validation
document.getElementById('blogForm').addEventListener('submit', function(e) {
    var content = tinymce.get('blog-content').getContent();
    if (!content.trim()) {
        e.preventDefault();
        alert('Blog content is required.');
        return;
    }
    
    // Check categories
    var cats = document.querySelectorAll('input[name="categories[]"]:checked');
    if (cats.length === 0) {
        e.preventDefault();
        alert('Please select at least one category.');
        return;
    }

    // Sync TinyMCE
    tinymce.triggerSave();
});
</script>
</body>
</html>
<?php closeDBConnection($conn); ?>
