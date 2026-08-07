<?php
/**
 * Sitemap.
 *
 * Built from the database rather than kept as a static file, so a new product
 * is listed the moment it goes live and a deleted one disappears. Google is
 * told about it by robots.txt.
 */
define('PHELYZ_ACCESS', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=UTF-8');

/** One <url> entry. */
function urlEntry($loc, $lastmod = null, $changefreq = 'weekly', $priority = '0.5') {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($loc, ENT_XML1) . "</loc>\n";
    if ($lastmod) echo "    <lastmod>" . date('Y-m-d', strtotime($lastmod)) . "</lastmod>\n";
    echo "    <changefreq>{$changefreq}</changefreq>\n";
    echo "    <priority>{$priority}</priority>\n";
    echo "  </url>\n";
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// The pages worth ranking for, most important first.
urlEntry(SITE_URL . '/',            null, 'daily',   '1.0');
urlEntry(SITE_URL . '/shop.php',    null, 'daily',   '0.9');
urlEntry(SITE_URL . '/about.php',   null, 'monthly', '0.5');
urlEntry(SITE_URL . '/contact.php', null, 'monthly', '0.5');
urlEntry(SITE_URL . '/faq.php',     null, 'monthly', '0.5');
urlEntry(SITE_URL . '/terms.php',   null, 'yearly',  '0.3');
urlEntry(SITE_URL . '/track.php',   null, 'monthly', '0.4');

// Category listings
try {
    foreach (getAllCategories() as $c) {
        urlEntry(SITE_URL . '/shop.php?category=' . (int)$c['id'], null, 'weekly', '0.7');
    }
} catch (Exception $e) {}

// Every live product. These are the pages that actually win searches.
try {
    $products = getDB()->fetchAll(
        "SELECT id, updated_at FROM products WHERE is_active = 1 ORDER BY updated_at DESC"
    );
    foreach ($products as $p) {
        urlEntry(SITE_URL . '/product.php?id=' . (int)$p['id'], $p['updated_at'], 'weekly', '0.8');
    }
} catch (Exception $e) {}

echo "</urlset>\n";
