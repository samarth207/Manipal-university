<?php
require_once 'config.php';

$conn = getDBConnection();
$conn->set_charset("utf8mb4");

$slug = trim($_GET['slug'] ?? '');
if (empty($slug)) {
    header('Location: blog.php');
    exit;
}

// Sanitize slug
$slug = preg_replace('/[^a-z0-9-]/', '', strtolower($slug));

// Fetch post
$stmt = $conn->prepare("SELECT p.*, a.name as author_name, a.bio as author_bio, a.image as author_image, a.author_page 
    FROM blog_posts p 
    LEFT JOIN blog_authors a ON p.author_id = a.id 
    WHERE p.url_slug = ? AND p.status = 'published' AND (p.publish_date <= CURDATE() OR p.publish_date IS NULL) 
    LIMIT 1");
$stmt->bind_param("s", $slug);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Post Not Found - Online Manipal</title>
        <link rel="stylesheet" href="styles.css">
        <link rel="stylesheet" href="blog-styles.css">
        <link rel="icon" href="images/favicon.png">
    </head>
    <body>
        <header><div class="topbar"><div class="brand"><a href="index.html"><img src="images/OM_Logo.svg" alt="Online Manipal Logo" class="main-logo"></a></div><div class="contact"><a href="blog.php" class="apply-btn">Back to Blog</a></div></div></header>
        <div class="blog-empty" style="min-height:60vh;display:flex;flex-direction:column;align-items:center;justify-content:center;">
            <h1 style="font-size:64px;color:#ddd;margin-bottom:10px;">404</h1>
            <h2>Post Not Found</h2>
            <p>This blog post doesn't exist or has been removed.</p>
            <a href="blog.php" style="color:#ff6a00;margin-top:16px;">Browse all posts &rarr;</a>
        </div>
    </body>
    </html>
    <?php
    closeDBConnection($conn);
    exit;
}

// Get categories
$stmt = $conn->prepare("SELECT c.name, c.slug FROM blog_post_categories pc JOIN blog_categories c ON pc.category_id = c.id WHERE pc.post_id = ?");
$stmt->bind_param("i", $post['id']);
$stmt->execute();
$categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get tags
$stmt = $conn->prepare("SELECT t.name, t.slug FROM blog_post_tags pt JOIN blog_tags t ON pt.tag_id = t.id WHERE pt.post_id = ?");
$stmt->bind_param("i", $post['id']);
$stmt->execute();
$tags = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// TOC
$toc = json_decode($post['table_of_contents'] ?? '[]', true) ?: [];

// Read time
$read_time = max(1, ceil(str_word_count(strip_tags($post['content'])) / 200));

// Publish date
$pub_date = $post['publish_date'] ? date('M j, Y', strtotime($post['publish_date'])) : date('M j, Y', strtotime($post['created_at']));
$pub_date_iso = $post['publish_date'] ?: date('Y-m-d', strtotime($post['created_at']));
$updated_iso = date('Y-m-d', strtotime($post['updated_at']));

// Meta
$meta_title = $post['meta_title'] ?: $post['title'];
$meta_desc = $post['meta_description'] ?: $post['excerpt'];
$canonical = 'https://manipalonlineadmission.in/blog/' . $post['url_slug'];
$feature_img_url = !empty($post['feature_image']) ? 'https://manipalonlineadmission.in/' . $post['feature_image'] : 'https://manipalonlineadmission.in/images/og-image.jpg';

// Related posts (same category, different post)
$related = [];
if (!empty($categories)) {
    $cat_ids = array_column($categories, 'slug');
    $first_cat = $categories[0]['slug'] ?? '';
    $stmt = $conn->prepare("SELECT p.id, p.title, p.url_slug, p.feature_image, p.feature_image_alt, p.publish_date, p.excerpt 
        FROM blog_posts p 
        JOIN blog_post_categories pc ON p.id = pc.post_id 
        JOIN blog_categories c ON pc.category_id = c.id 
        WHERE c.slug = ? AND p.id != ? AND p.status = 'published' 
        ORDER BY p.publish_date DESC LIMIT 3");
    $stmt->bind_param("si", $first_cat, $post['id']);
    $stmt->execute();
    $related = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// FAQ extraction for schema
$faqs = [];
if (preg_match_all('/<div class="faq-item"[^>]*>.*?<h3[^>]*>(.*?)<\/h3>.*?<p[^>]*>(.*?)<\/p>/is', $post['content'], $faq_matches, PREG_SET_ORDER)) {
    foreach ($faq_matches as $faq) {
        $faqs[] = [
            'question' => strip_tags($faq[1]),
            'answer' => strip_tags($faq[2])
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="/">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($meta_title); ?> | Online Manipal</title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_desc); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($post['focus_keyword'] . ', ' . $post['primary_keyword']); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($post['author_name'] ?? 'Online Manipal'); ?>">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
    <link rel="canonical" href="<?php echo $canonical; ?>">
    
    <!-- Open Graph -->
    <meta property="og:type" content="article">
    <meta property="og:title" content="<?php echo htmlspecialchars($meta_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($meta_desc); ?>">
    <meta property="og:url" content="<?php echo $canonical; ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($feature_img_url); ?>">
    <meta property="og:site_name" content="Online Manipal">
    <meta property="og:locale" content="en_IN">
    <meta property="article:published_time" content="<?php echo $pub_date_iso; ?>">
    <meta property="article:modified_time" content="<?php echo $updated_iso; ?>">
    <?php foreach ($categories as $cat): ?>
        <meta property="article:section" content="<?php echo htmlspecialchars($cat['name']); ?>">
    <?php endforeach; ?>
    <?php foreach ($tags as $tag): ?>
        <meta property="article:tag" content="<?php echo htmlspecialchars($tag['name']); ?>">
    <?php endforeach; ?>
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($meta_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($meta_desc); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($feature_img_url); ?>">

    <!-- Schema.org BlogPosting -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BlogPosting",
        "headline": "<?php echo htmlspecialchars($post['title'], ENT_QUOTES); ?>",
        "description": "<?php echo htmlspecialchars($meta_desc, ENT_QUOTES); ?>",
        "url": "<?php echo $canonical; ?>",
        "image": "<?php echo htmlspecialchars($feature_img_url); ?>",
        "datePublished": "<?php echo $pub_date_iso; ?>",
        "dateModified": "<?php echo $updated_iso; ?>",
        "author": {
            "@type": "Person",
            "name": "<?php echo htmlspecialchars($post['author_name'] ?? 'Online Manipal', ENT_QUOTES); ?>"
        },
        "publisher": {
            "@type": "Organization",
            "name": "Online Manipal",
            "logo": { "@type": "ImageObject", "url": "https://manipalonlineadmission.in/images/OM_Logo.svg" }
        },
        "mainEntityOfPage": { "@type": "WebPage", "@id": "<?php echo $canonical; ?>" },
        "keywords": "<?php echo htmlspecialchars($post['focus_keyword'], ENT_QUOTES); ?>",
        "wordCount": <?php echo str_word_count(strip_tags($post['content'])); ?>,
        "articleSection": "<?php echo htmlspecialchars($categories[0]['name'] ?? 'Blog', ENT_QUOTES); ?>"
    }
    </script>

    <!-- BreadcrumbList -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            { "@type": "ListItem", "position": 1, "name": "Home", "item": "https://manipalonlineadmission.in" },
            { "@type": "ListItem", "position": 2, "name": "Blog", "item": "https://manipalonlineadmission.in/blog" },
            { "@type": "ListItem", "position": 3, "name": "<?php echo htmlspecialchars($post['title'], ENT_QUOTES); ?>", "item": "<?php echo $canonical; ?>" }
        ]
    }
    </script>

    <?php if (!empty($faqs)): ?>
    <!-- FAQPage Schema -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "FAQPage",
        "mainEntity": [
            <?php $faq_json = []; foreach ($faqs as $faq): ?>
            <?php $faq_json[] = '{
                "@type": "Question",
                "name": "' . htmlspecialchars($faq['question'], ENT_QUOTES) . '",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "' . htmlspecialchars($faq['answer'], ENT_QUOTES) . '"
                }
            }'; ?>
            <?php endforeach; echo implode(',', $faq_json); ?>
        ]
    }
    </script>
    <?php endif; ?>

    <link rel="icon" href="images/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="blog-styles.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="topbar">
            <div class="brand">
                <a href="index.html"><img src="images/OM_Logo.svg" alt="Online Manipal Logo" class="main-logo"></a>
            </div>
            <div class="contact">
                <a href="tel:+918920785477" class="phone-number">+91-8920785477</a>
                <a href="index.html#hero-form" class="apply-btn">Apply Now</a>
            </div>
        </div>
    </header>

    <!-- Breadcrumb -->
    <nav class="blog-breadcrumb" aria-label="Breadcrumb">
        <div class="container">
            <a href="index.html">Home</a>
            <span class="bc-sep">/</span>
            <a href="blog.php">Blog</a>
            <?php if (!empty($categories)): ?>
                <span class="bc-sep">/</span>
                <a href="blog.php?category=<?php echo htmlspecialchars($categories[0]['slug']); ?>"><?php echo htmlspecialchars($categories[0]['name']); ?></a>
            <?php endif; ?>
            <span class="bc-sep">/</span>
            <span class="bc-current"><?php echo htmlspecialchars(mb_substr($post['title'], 0, 50)); ?><?php echo mb_strlen($post['title']) > 50 ? '...' : ''; ?></span>
        </div>
    </nav>

    <!-- Blog Post -->
    <article class="blog-post-page">
        <div class="container">
            <!-- Post Header -->
            <div class="blog-post-header">
                <div class="blog-post-cats">
                    <?php foreach ($categories as $cat): ?>
                        <a href="blog.php?category=<?php echo htmlspecialchars($cat['slug']); ?>" class="blog-cat-badge"><?php echo htmlspecialchars($cat['name']); ?></a>
                    <?php endforeach; ?>
                </div>
                <h1 class="blog-post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
                <div class="blog-post-meta-bar">
                    <?php if ($post['author_name']): ?>
                        <div class="blog-post-author-mini">
                            <?php if (!empty($post['author_image'])): ?>
                                <img src="<?php echo htmlspecialchars($post['author_image']); ?>" alt="<?php echo htmlspecialchars($post['author_name']); ?>" class="author-mini-img">
                            <?php endif; ?>
                            <span>By <?php echo htmlspecialchars($post['author_name']); ?></span>
                        </div>
                    <?php endif; ?>
                    <span class="meta-dot"></span>
                    <time datetime="<?php echo $pub_date_iso; ?>"><?php echo $pub_date; ?></time>
                    <span class="meta-dot"></span>
                    <span><?php echo $read_time; ?> min read</span>
                    <?php if ($post['updated_at'] !== $post['created_at']): ?>
                        <span class="meta-dot"></span>
                        <span>Updated: <?php echo date('M j, Y', strtotime($post['updated_at'])); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Feature Image -->
            <?php if (!empty($post['feature_image'])): ?>
            <div class="blog-post-feature-img">
                <img src="<?php echo htmlspecialchars($post['feature_image']); ?>" 
                     alt="<?php echo htmlspecialchars($post['feature_image_alt'] ?? $post['title']); ?>"
                     <?php if (!empty($post['feature_image_title'])): ?>title="<?php echo htmlspecialchars($post['feature_image_title']); ?>"<?php endif; ?>
                     width="1200" height="628" loading="eager">
            </div>
            <?php endif; ?>

            <!-- Content Layout: TOC + Content -->
            <div class="blog-post-layout">
                <!-- Table of Contents (Sticky Sidebar) -->
                <?php if (!empty($toc)): ?>
                <aside class="blog-toc" id="blogTOC">
                    <div class="toc-inner">
                        <h3 class="toc-title">Table of Contents</h3>
                        <nav class="toc-nav">
                            <ol>
                                <?php foreach ($toc as $item): ?>
                                    <li class="toc-level-<?php echo $item['level']; ?>">
                                        <a href="#<?php echo htmlspecialchars($item['id']); ?>"><?php echo htmlspecialchars($item['text']); ?></a>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        </nav>
                    </div>
                </aside>
                <?php endif; ?>

                <!-- Blog Content -->
                <div class="blog-post-content <?php echo empty($toc) ? 'full-width' : ''; ?>">
                    <?php echo $post['content']; ?>
                </div>
            </div>

            <!-- Tags -->
            <?php if (!empty($tags)): ?>
            <div class="blog-post-tags">
                <strong>Tags:</strong>
                <?php foreach ($tags as $tag): ?>
                    <span class="blog-tag"><?php echo htmlspecialchars($tag['name']); ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Author Card -->
            <?php if ($post['author_name']): ?>
            <div class="blog-author-card">
                <div class="author-card-img">
                    <?php if (!empty($post['author_image'])): ?>
                        <img src="<?php echo htmlspecialchars($post['author_image']); ?>" alt="<?php echo htmlspecialchars($post['author_name']); ?>">
                    <?php else: ?>
                        <div class="author-card-placeholder"><?php echo strtoupper(substr($post['author_name'], 0, 1)); ?></div>
                    <?php endif; ?>
                </div>
                <div class="author-card-info">
                    <h4>
                        <?php if (!empty($post['author_page'])): ?>
                            <a href="<?php echo htmlspecialchars($post['author_page']); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($post['author_name']); ?></a>
                        <?php else: ?>
                            <?php echo htmlspecialchars($post['author_name']); ?>
                        <?php endif; ?>
                    </h4>
                    <?php if (!empty($post['author_bio'])): ?>
                        <p><?php echo htmlspecialchars($post['author_bio']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Related Posts -->
            <?php if (!empty($related)): ?>
            <section class="blog-related">
                <h2>Related Articles</h2>
                <div class="blog-related-grid">
                    <?php foreach ($related as $rp): 
                        $rp_date = $rp['publish_date'] ? date('M j, Y', strtotime($rp['publish_date'])) : '';
                    ?>
                    <a href="blog/<?php echo htmlspecialchars($rp['url_slug']); ?>" class="blog-related-card">
                        <?php if (!empty($rp['feature_image'])): ?>
                            <img src="<?php echo htmlspecialchars($rp['feature_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($rp['feature_image_alt'] ?? $rp['title']); ?>"
                                 loading="lazy" width="300" height="157">
                        <?php endif; ?>
                        <div class="related-card-body">
                            <h3><?php echo htmlspecialchars($rp['title']); ?></h3>
                            <?php if ($rp_date): ?><span class="related-date"><?php echo $rp_date; ?></span><?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </div>
    </article>

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

    <!-- TOC Active State Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var tocLinks = document.querySelectorAll('.toc-nav a');
        if (tocLinks.length === 0) return;
        
        var headings = [];
        tocLinks.forEach(function(link) {
            var id = link.getAttribute('href').substring(1);
            var el = document.getElementById(id);
            if (el) headings.push({el: el, link: link});
        });
        
        function updateTOC() {
            var scrollPos = window.scrollY + 120;
            var current = null;
            headings.forEach(function(h) {
                if (h.el.offsetTop <= scrollPos) current = h;
            });
            tocLinks.forEach(function(l) { l.classList.remove('active'); });
            if (current) current.link.classList.add('active');
        }
        
        window.addEventListener('scroll', updateTOC, {passive: true});
        updateTOC();

        // Smooth scroll for TOC links
        tocLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                var target = document.getElementById(this.getAttribute('href').substring(1));
                if (target) {
                    window.scrollTo({top: target.offsetTop - 100, behavior: 'smooth'});
                }
            });
        });
    });
    </script>

    <!-- Google Analytics -->
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
