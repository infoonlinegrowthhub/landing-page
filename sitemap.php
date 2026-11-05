<?php
/**
 * Dynamic XML Sitemap Generator
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

header('Content-Type: application/xml; charset=utf-8');

$pdo = getDB();
$baseUrl = getBaseUrl();
$lastMod = date('c');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc><?= e($baseUrl . '/') ?></loc>
    <lastmod><?= e($lastMod) ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
  </url>
  <url>
    <loc><?= e($baseUrl . '/llms.txt') ?></loc>
    <lastmod><?= e($lastMod) ?></lastmod>
    <changefreq>daily</changefreq>
    <priority>0.8</priority>
  </url>
</urlset>

