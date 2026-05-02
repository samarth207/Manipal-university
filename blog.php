<?php
require_once 'config.php';

$conn = getDBConnection();
$conn->set_charset("utf8mb4");

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Category filter
$category_slug = $_GET['category'] ?? '';
$search = trim($_GET['q'] ?? '');

$where = "WHERE p.status = 'published' AND (p.publish_date <= CURDATE() OR p.publish_date IS NULL)";
$params = [];
$types = "";

if ($category_slug) {
    $where .= " AND EXISTS (SELECT 1 FROM blog_post_categories pc JOIN blog_categories c ON pc.category_id = c.id WHERE pc.post_id = p.id AND c.slug = ?)";
    $params[] = $category_slug;
    $types .= "s";
}

if ($search) {
    $where .= " AND (p.title LIKE ? OR p.excerpt LIKE ? OR p.content LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

// Count total posts
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

// Fetch posts
$sql = "SELECT p.*, a.name as author_name, a.image as author_image
        FROM blog_posts p 
        LEFT JOIN blog_authors a ON p.author_id = a.id 
        $where 
        ORDER BY p.publish_date DESC, p.created_at DESC 
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

// Fetch all categories for filter
$all_categories = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM blog_post_categories pc JOIN blog_posts bp ON pc.post_id = bp.id WHERE pc.category_id = c.id AND bp.status = 'published') as post_count FROM blog_categories c HAVING post_count > 0 ORDER BY c.name")->fetch_all(MYSQLI_ASSOC);

// Get categories for each post
function getPostCategories($conn, $post_id) {
    $stmt = $conn->prepare("SELECT c.name, c.slug FROM blog_post_categories pc JOIN blog_categories c ON pc.category_id = c.id WHERE pc.post_id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $result;
}

// Page meta
$page_title = 'Blog - Online Manipal';
$page_desc = 'Read the latest blogs about online education, career guidance, MBA, MCA, BBA, BCA and more from Online Manipal.';
if ($category_slug) {
    foreach ($all_categories as $c) {
        if ($c['slug'] === $category_slug) {
            $page_title = $c['name'] . ' - Blog - Online Manipal';
            $page_desc = 'Read blogs about ' . $c['name'] . ' from Online Manipal.';
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_desc); ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://manipalonlineadmission.in/blog<?php echo $category_slug ? '/category/' . htmlspecialchars($category_slug) : ''; ?>">
    
    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($page_desc); ?>">
    <meta property="og:url" content="https://manipalonlineadmission.in/blog">
    <meta property="og:site_name" content="Online Manipal">
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($page_desc); ?>">

    <!-- Schema.org -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Blog",
        "name": "Online Manipal Blog",
        "description": "<?php echo htmlspecialchars($page_desc); ?>",
        "url": "https://manipalonlineadmission.in/blog",
        "publisher": {
            "@type": "Organization",
            "name": "Online Manipal",
            "logo": { "@type": "ImageObject", "url": "https://manipalonlineadmission.in/images/OM_Logo.svg" }
        }
    }
    </script>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            { "@type": "ListItem", "position": 1, "name": "Home", "item": "https://manipalonlineadmission.in" },
            { "@type": "ListItem", "position": 2, "name": "Blog", "item": "https://manipalonlineadmission.in/blog" }
            <?php if ($category_slug): ?>
            ,{ "@type": "ListItem", "position": 3, "name": "<?php echo htmlspecialchars($category_slug); ?>", "item": "https://manipalonlineadmission.in/blog/category/<?php echo htmlspecialchars($category_slug); ?>" }
            <?php endif; ?>
        ]
    }
    </script>

    <link rel="icon" href="images/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="blog-styles.css">
</head>
<body>
    <!-- Header (same as main site) -->
    <header>
        <div class="topbar">
            <div class="brand">
                <a href="index.html"><img src="images/OM_Logo.svg" alt="Online Manipal Logo" class="main-logo"></a>
            </div>
            <div class="contact">
                <a href="index.html" class="blog-nav-link">Home</a>
                <a href="tel:+918920785477" class="phone-number">+91-8920785477</a>
                <a href="index.html#hero-form" class="apply-btn">Apply Now</a>
            </div>
        </div>
    </header>

    <!-- Blog Listing -->
    <section class="blog-listing-hero">
        <div class="container">
            <h1>Our Blog</h1>
            <p>Insights, guides, and expert advice on online education and career growth.</p>
        </div>
    </section>

    <div class="blog-listing-container container">
        <!-- Category Filter -->
        <div class="blog-filters">
            <a href="blog.php" class="filter-chip <?php echo !$category_slug ? 'active' : ''; ?>">All</a>
            <?php foreach ($all_categories as $cat): ?>
                <a href="blog.php?category=<?php echo htmlspecialchars($cat['slug']); ?>" 
                   class="filter-chip <?php echo $category_slug === $cat['slug'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['name']); ?> (<?php echo $cat['post_count']; ?>)
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Search -->
        <form class="blog-search" action="blog.php" method="GET">
            <?php if ($category_slug): ?>
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_slug); ?>">
            <?php endif; ?>
            <input type="text" name="q" placeholder="Search blogs..." value="<?php echo htmlspecialchars($search); ?>" class="blog-search-input">
            <button type="submit" class="blog-search-btn">Search</button>
        </form>

        <?php if ($posts->num_rows > 0): ?>
        <!-- Blog Grid -->
        <div class="blog-grid">
            <?php while ($post = $posts->fetch_assoc()): 
                $cats = getPostCategories($conn, $post['id']);
                $read_time = max(1, ceil(str_word_count(strip_tags($post['content'])) / 200));
                $pub_date = $post['publish_date'] ? date('M j, Y', strtotime($post['publish_date'])) : date('M j, Y', strtotime($post['created_at']));
            ?>
            <article class="blog-card">
                <a href="blog/<?php echo htmlspecialchars($post['url_slug']); ?>" class="blog-card-link">
                    <div class="blog-card-image">
                        <?php if (!empty($post['feature_image'])): ?>
                            <img src="<?php echo htmlspecialchars($post['feature_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($post['feature_image_alt'] ?? $post['title']); ?>"
                                 loading="lazy" width="400" height="210">
                        <?php else: ?>
                            <div class="blog-card-placeholder">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                            </div>
                        <?php endif; ?>
                        <div class="blog-card-cats">
                            <?php foreach (array_slice($cats, 0, 2) as $cat): ?>
                                <span class="blog-cat-badge"><?php echo htmlspecialchars($cat['name']); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="blog-card-body">
                        <div class="blog-card-meta">
                            <span><?php echo $pub_date; ?></span>
                            <span class="meta-dot"></span>
                            <span><?php echo $read_time; ?> min read</span>
                        </div>
                        <h2 class="blog-card-title"><?php echo htmlspecialchars($post['title']); ?></h2>
                        <p class="blog-card-excerpt"><?php echo htmlspecialchars(substr($post['excerpt'], 0, 120)); ?>...</p>
                    </div>
                </a>
            </article>
            <?php endwhile; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav class="blog-pagination" aria-label="Blog pagination">
            <?php if ($page > 1): ?>
                <a href="blog.php?page=<?php echo $page - 1; ?><?php echo $category_slug ? '&category=' . htmlspecialchars($category_slug) : ''; ?>" class="page-btn">&laquo; Prev</a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="blog.php?page=<?php echo $i; ?><?php echo $category_slug ? '&category=' . htmlspecialchars($category_slug) : ''; ?>" 
                   class="page-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="blog.php?page=<?php echo $page + 1; ?><?php echo $category_slug ? '&category=' . htmlspecialchars($category_slug) : ''; ?>" class="page-btn">Next &raquo;</a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>

        <?php else: ?>
        <div class="blog-empty">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#ddd" stroke-width="1.5"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <h3>No posts found</h3>
            <p><?php echo $search ? 'No results for "' . htmlspecialchars($search) . '". Try a different search.' : 'Blog posts will appear here once published.'; ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="blog-footer">
        <div class="container">
            <div class="blog-footer-inner">
                <div class="blog-footer-brand">
                    <img src="images/OM_Logo.svg" alt="Online Manipal" style="height:32px;">
                    <p>&copy; <?php echo date('Y'); ?> Online Manipal. All Rights Reserved.</p>
                </div>
                <div class="blog-footer-links">
                    <a href="index.html">Home</a>
                    <a href="blog.php">Blog</a>
                    <a href="index.html#help">Help Center</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Google Analytics (same as main site) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-0VM4D9DFER"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-0VM4D9DFER');
    </script>
</body>
</html>
<?php closeDBConnection($conn); ?>
