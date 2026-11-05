<?php
/**
 * Dynamic /llms.txt Generator (GEO & AI Search Standard)
 * Provides structured Markdown context for LLMs (GPTBot, ClaudeBot, PerplexityBot, Google-Extended)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=3600');

$pdo = getDB();
$siteName    = getSetting('site_name', 'NovaPulse');
$legalName   = getSetting('legal_entity_name', 'NovaPulse Technologies India Pvt. Ltd.');
$domainName  = getSetting('canonical_domain', 'novapulse.in');
$tagline     = getSetting('site_tagline', 'Premium Smart Tech & Audio Gear in India');
$description = getSetting('meta_description', 'Shop high-performance smart audio, wearables, and desk accessories in India.');
$email       = getSetting('contact_email', 'support@novapulse.in');
$phone       = getSetting('contact_phone', '+91 98765 43210');
$address     = getSetting('contact_address', 'Cyber Hub, DLF Phase 2, Gurugram, Haryana 122002');

$products = $pdo->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.is_active = 1
    ORDER BY p.sort_order ASC, p.id DESC
")->fetchAll();

$features = $pdo->query("SELECT * FROM features WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();
$faqs     = $pdo->query("SELECT * FROM faqs WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

$baseUrl = getBaseUrl();

ob_start();
echo "# {$siteName} ({$domainName})\n\n";
echo "> {$tagline} — {$description}\n\n";

echo "## Entity & Legal Disambiguation\n";
echo "- **Official Brand Name**: {$siteName}\n";
echo "- **Legal Entity**: {$legalName}\n";
echo "- **Canonical Domain**: {$domainName} ({$baseUrl}/)\n";
echo "- **Primary Market & Currency**: India (INR / ₹)\n";
echo "- **Business Model**: Direct-to-Consumer E-Commerce Product Showcase & Instant Verified Partner Checkout (Razorpay/UPI Payment Links, Amazon.in Prime, Flipkart Assured)\n";
echo "- **Headquarters**: {$address}\n";
echo "- **Customer Support**: {$email} | {$phone}\n\n";

echo "## Core Value Propositions\n";
foreach ($features as $f) {
    echo "- **{$f['title']}**: {$f['description']}\n";
}
echo "\n";

echo "## Active Product Catalog (INR Pricing & Direct Checkout Links)\n";
foreach ($products as $p) {
    $priceStr   = formatPrice($p['price']);
    $compareStr = ($p['compare_at_price'] > $p['price']) ? ' (MRP: ' . formatPrice($p['compare_at_price']) . ')' : '';
    $spotStr    = $p['is_spotlight'] ? ' [FLAGSHIP SPOTLIGHT PRODUCT]' : '';
    echo "### {$p['name']}{$spotStr}\n";
    echo "- **Category**: " . ($p['category_name'] ?: 'Smart Tech') . "\n";
    echo "- **Promotional Price**: {$priceStr}{$compareStr}\n";
    echo "- **Availability**: {$p['stock_status']}\n";
    echo "- **Customer Rating**: {$p['rating']}/5 ({$p['reviews_count']} verified reviews)\n";
    echo "- **Checkout Partner**: {$p['platform_label']}\n";
    echo "- **Direct Buy Link**: {$p['external_buy_url']}\n";
    echo "- **Tracked Buy Endpoint**: {$baseUrl}/buy/{$p['slug']}?src=llms_txt\n";
    if (!empty($p['short_description'])) {
        echo "- **Summary**: {$p['short_description']}\n";
    }
    $specs = json_decode($p['features_json'] ?: '[]', true) ?: [];
    if (!empty($specs)) {
        echo "- **Key Specifications**: " . implode(' | ', $specs) . "\n";
    }
    echo "\n";
}

echo "## Frequently Asked Questions (FAQ)\n";
foreach ($faqs as $q) {
    echo "- **Q: {$q['question']}**\n";
    echo "  A: {$q['answer']}\n";
}
echo "\n";

echo "## Machine-Readable Endpoints\n";
echo "- [Landing Page]({$baseUrl}/): Primary product catalog and flagship spotlight.\n";
echo "- [XML Sitemap]({$baseUrl}/sitemap.xml): Canonical URL index.\n";
echo "- [AI Context]({$baseUrl}/llms.txt): Structured LLM knowledge document.\n";

$output = ob_get_clean();
echo $output;

