<?php
/**
 * Outbound "Buy Now" Click Tracker & External Website / Payment Link Redirector
 * Supports clean slug URLs (/buy/{product-slug}) and ID queries (/go?id=1)
 * Returns HTTP 410 Gone for permanently removed products
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

$pdo = getDB();

$productId   = isset($_GET['id']) ? (int)$_GET['id'] : null;
$productSlug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$placement   = isset($_GET['src']) ? preg_replace('/[^a-z0-9_-]/i', '', $_GET['src']) : 'card';
$fallbackUrl = isset($_GET['url']) ? trim($_GET['url']) : '';

$targetUrl = '';
$resolvedProductId = null;

if ($productSlug !== '') {
    $stmt = $pdo->prepare("SELECT id, external_buy_url, is_active FROM products WHERE slug = ? LIMIT 1");
    $stmt->execute([$productSlug]);
    $product = $stmt->fetch();

    if (!$product) {
        require __DIR__ . '/410.php';
        exit;
    }

    if (!empty($product['external_buy_url'])) {
        $resolvedProductId = (int)$product['id'];
        $targetUrl = trim($product['external_buy_url']);
        $pdo->prepare("UPDATE products SET clicks_count = clicks_count + 1 WHERE id = ?")->execute([$resolvedProductId]);
    }
} elseif ($productId !== null && $productId > 0) {
    $stmt = $pdo->prepare("SELECT id, external_buy_url, is_active FROM products WHERE id = ? LIMIT 1");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        require __DIR__ . '/410.php';
        exit;
    }

    if (!empty($product['external_buy_url'])) {
        $resolvedProductId = (int)$product['id'];
        $targetUrl = trim($product['external_buy_url']);
        $pdo->prepare("UPDATE products SET clicks_count = clicks_count + 1 WHERE id = ?")->execute([$resolvedProductId]);
    }
}

if (empty($targetUrl) && !empty($fallbackUrl)) {
    if (preg_match('~^https?://~i', $fallbackUrl)) {
        $targetUrl = $fallbackUrl;
    }
}

if (empty($targetUrl)) {
    $hero = $pdo->query("SELECT product_id, cta_external_url FROM hero_banners WHERE is_active = 1 ORDER BY sort_order ASC, id DESC LIMIT 1")->fetch();
    if ($hero && !empty($hero['cta_external_url'])) {
        $targetUrl = trim($hero['cta_external_url']);
        if (!$resolvedProductId && !empty($hero['product_id'])) {
            $resolvedProductId = (int)$hero['product_id'];
        }
    }
}

if (empty($targetUrl)) {
    header('Location: ' . getBaseUrl() . '/', true, 302);
    exit;
}

if (!preg_match('~^https?://~i', $targetUrl)) {
    $targetUrl = 'https://' . ltrim($targetUrl, '/');
}

// Log the click into click_logs
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 490);
$referrer  = substr($_SERVER['HTTP_REFERER'] ?? 'Direct', 0, 490);

$logStmt = $pdo->prepare("
    INSERT INTO click_logs (product_id, target_url, placement, ip_address, user_agent, referrer)
    VALUES (?, ?, ?, ?, ?, ?)
");
$logStmt->execute([
    $resolvedProductId,
    $targetUrl,
    $placement ?: 'card',
    $ipAddress,
    $userAgent,
    $referrer
]);

header('Location: ' . $targetUrl, true, 302);
exit;
