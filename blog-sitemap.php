<?php
/**
 * Blog Sitemap Generator
 * Generates XML sitemap entries for all published blog posts.
 * Access: /blog-sitemap.xml (add rewrite rule) or include in main sitemap.
 */
require_once 'config.php';

$conn = getDBConnection();
$conn->set_charset("utf8mb4");

header('Content-Type: application/xml; charset=UTF-8');

$base_url = 'https://manipalonlineadmission.in';

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
    
    <!-- Blog Listing Page -->
    <url>
        <loc><?php echo $base_url; ?>/blog</loc>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>

    <?php
    // All published posts
    $posts = $conn->query("SELECT url_slug, updated_at, feature_image, feature_image_alt, title 
        FROM blog_posts 
        WHERE status = 'published' AND (publish_date <= CURDATE() OR publish_date IS NULL) 
        ORDER BY publish_date DESC");
    
    while ($post = $posts->fetch_assoc()):
        $lastmod = date('Y-m-d', strtotime($post['updated_at']));
    ?>
    <url>
        <loc><?php echo $base_url; ?>/blog/<?php echo htmlspecialchars($post['url_slug']); ?></loc>
        <lastmod><?php echo $lastmod; ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
        <?php if (!empty($post['feature_image'])): ?>
        <image:image>
            <image:loc><?php echo $base_url; ?>/<?php echo htmlspecialchars($post['feature_image']); ?></image:loc>
            <image:title><?php echo htmlspecialchars($post['title']); ?></image:title>
            <?php if (!empty($post['feature_image_alt'])): ?>
            <image:caption><?php echo htmlspecialchars($post['feature_image_alt']); ?></image:caption>
            <?php endif; ?>
        </image:image>
        <?php endif; ?>
    </url>
    <?php endwhile; ?>

    <?php
    // Category pages
    $cats = $conn->query("SELECT DISTINCT c.slug FROM blog_categories c 
        JOIN blog_post_categories pc ON c.id = pc.category_id 
        JOIN blog_posts p ON pc.post_id = p.id 
        WHERE p.status = 'published'");
    
    while ($cat = $cats->fetch_assoc()):
    ?>
    <url>
        <loc><?php echo $base_url; ?>/blog/category/<?php echo htmlspecialchars($cat['slug']); ?></loc>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
    <?php endwhile; ?>
</urlset>
<?php closeDBConnection($conn); ?>
