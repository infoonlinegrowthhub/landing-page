<?php
/**
 * Shared Helper Functions — SEO, GEO (JSON-LD @graph, RFC 8288 Link), Security & Formatting
 */

require_once __DIR__ . '/database.php';

function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT//IGNORE', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return $text ?: 'item-' . time();
}

function getSiteSettings(bool $refresh = false): array {
    static $settings = null;
    if ($settings !== null && !$refresh) {
        return $settings;
    }

    $pdo = getDB();
    $rows = $pdo->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll();
    $settings = [];
    foreach ($rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

function getSetting(string $key, string $default = ''): string {
    $settings = getSiteSettings();
    return isset($settings[$key]) && $settings[$key] !== '' ? (string)$settings[$key] : $default;
}

function updateSiteSetting(string $key, ?string $value): void {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO site_settings (setting_key, setting_value)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");
    $stmt->execute([$key, (string)$value]);
}

function formatPrice($amount, ?string $currency = null): string {
    if ($currency === null) {
        $currency = getSetting('currency_symbol', '₹');
    }
    $num = (float)$amount;
    if (floor($num) == $num) {
        return $currency . number_format($num, 0);
    }
    return $currency . number_format($num, 2);
}

/**
 * Resolves the canonical base URL for the application
 */
function getBaseUrl(): string {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    $scheme = $isHttps ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $host   = preg_replace('/^www\./i', '', $host);

    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $scriptDir = preg_replace('~/admin$~', '', rtrim($scriptDir, '/'));

    return $scheme . '://' . $host . $scriptDir;
}

/**
 * Formats <title>{PageName} - {BrandName} | {domain.ext}</title>
 * Google & GEO Standard: Dynamic Brand-First Titles (Never static "Home - Title")
 */
function formatBrandFirstTitle(string $pageName): string {
    $brandName  = getSetting('site_name', 'NovaPulse');
    $domainName = getSetting('canonical_domain', 'landingpage.buildmyngo.online');
    return trim($pageName) . ' - ' . $brandName . ' | ' . $domainName;
}

/**
 * Enforces single canonical domain (301 redirect) and sends RFC 8288 Link headers for /llms.txt
 */
function enforceCanonicalAndSendGeoHeaders(string $canonicalPath = '/'): void {
    if (headers_sent()) {
        return;
    }

    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (stripos($host, 'www.') === 0) {
        $cleanHost = substr($host, 4);
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $scheme = $isHttps ? 'https' : 'http';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: ' . $scheme . '://' . $cleanHost . $uri, true, 301);
        exit;
    }

    $baseUrl      = getBaseUrl();
    $llmsUrl      = $baseUrl . '/llms.txt';
    $canonicalUrl = rtrim($baseUrl, '/') . '/' . ltrim($canonicalPath, '/');
    if ($canonicalPath === '/' || $canonicalPath === '') {
        $canonicalUrl = rtrim($baseUrl, '/') . '/';
    }

    // RFC 8288 Link header for AI Search / GEO & Canonical discovery
    header('Link: <' . $llmsUrl . '>; rel="llms-txt"; type="text/plain; charset=utf-8", <' . $canonicalUrl . '>; rel="canonical"', false);
}

/**
 * Dynamically builds the Schema.org JSON-LD Knowledge Graph (@graph)
 * Includes Organization, WebSite, WebPage, ItemList (Products), and FAQPage
 */
function generateKnowledgeGraphJsonLd(array $products, array $faqs, ?array $spotlightProduct = null): string {
    $baseUrl     = getBaseUrl();
    $siteName    = getSetting('site_name', 'NovaPulse');
    $legalName   = getSetting('legal_entity_name', 'NovaPulse Technologies India Pvt. Ltd.');
    $domainName  = getSetting('canonical_domain', 'novapulse.in');
    $tagline     = getSetting('site_tagline', 'Premium Smart Tech & Audio Gear in India');
    $description = getSetting('meta_description', $tagline);
    $email       = getSetting('contact_email', 'support@novapulse.in');
    $phone       = getSetting('contact_phone', '+91 98765 43210');
    $address     = getSetting('contact_address', 'Cyber Hub, DLF Phase 2, Gurugram, Haryana 122002');

    $sameAs = array_values(array_filter([
        getSetting('instagram_url'),
        getSetting('facebook_url'),
        getSetting('twitter_url'),
        getSetting('youtube_url'),
    ]));

    // 1. Organization Entity
    $organizationNode = [
        '@type'        => 'Organization',
        '@id'          => $baseUrl . '/#organization',
        'name'         => $siteName,
        'legalName'    => $legalName,
        'alternateName'=> $siteName . ' India (' . $domainName . ')',
        'url'          => $baseUrl . '/',
        'logo'         => [
            '@type'  => 'ImageObject',
            '@id'    => $baseUrl . '/#logo',
            'url'    => $baseUrl . '/assets/favicons/favicon-192x192.png',
            'width'  => 192,
            'height' => 192,
            'caption'=> $siteName
        ],
        'contactPoint' => [
            '@type'             => 'ContactPoint',
            'telephone'         => $phone,
            'email'             => $email,
            'contactType'       => 'customer support',
            'areaServed'        => 'IN',
            'availableLanguage' => ['English', 'Hindi']
        ],
        'address'      => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $address,
            'addressLocality' => 'Gurugram',
            'addressRegion'   => 'Haryana',
            'postalCode'      => '122002',
            'addressCountry'  => 'IN'
        ],
        'sameAs'       => $sameAs
    ];

    // 2. WebSite Entity
    $websiteNode = [
        '@type'           => 'WebSite',
        '@id'             => $baseUrl . '/#website',
        'url'             => $baseUrl . '/',
        'name'            => $siteName . ' | ' . $domainName,
        'description'     => $tagline,
        'inLanguage'      => 'en-IN',
        'publisher'       => ['@id' => $baseUrl . '/#organization'],
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => [
                '@type'       => 'EntryPoint',
                'urlTemplate' => $baseUrl . '/?q={search_term_string}'
            ],
            'query-input' => 'required name=search_term_string'
        ]
    ];

    // 3. WebPage Entity
    $webpageNode = [
        '@type'       => 'CollectionPage',
        '@id'         => $baseUrl . '/#webpage',
        'url'         => $baseUrl . '/',
        'name'        => formatBrandFirstTitle($tagline),
        'description' => $description,
        'inLanguage'  => 'en-IN',
        'isPartOf'    => ['@id' => $baseUrl . '/#website'],
        'about'       => ['@id' => $baseUrl . '/#organization']
    ];

    // 4. Product Catalog ItemList
    $itemListElements = [];
    foreach ($products as $index => $prod) {
        $availabilityMap = [
            'In Stock'      => 'https://schema.org/InStock',
            'Limited Stock' => 'https://schema.org/LimitedAvailability',
            'Pre-Order'     => 'https://schema.org/PreOrder',
            'Sold Out'      => 'https://schema.org/OutOfStock',
        ];
        $schemaAvail = $availabilityMap[$prod['stock_status']] ?? 'https://schema.org/InStock';

        $itemListElements[] = [
            '@type'    => 'ListItem',
            'position' => $index + 1,
            'item'     => [
                '@type'       => 'Product',
                '@id'         => $baseUrl . '/#product-' . (int)$prod['id'],
                'name'        => $prod['name'],
                'description' => $prod['short_description'] ?: $prod['subtitle'],
                'image'       => resolveImageUrl($prod['main_image']),
                'sku'         => 'NP-IN-' . str_pad((string)$prod['id'], 4, '0', STR_PAD_LEFT),
                'brand'       => [
                    '@type' => 'Brand',
                    'name'  => $siteName
                ],
                'offers'      => [
                    '@type'           => 'Offer',
                    'url'             => $prod['external_buy_url'],
                    'priceCurrency'   => 'INR',
                    'price'           => number_format((float)$prod['price'], 2, '.', ''),
                    'availability'    => $schemaAvail,
                    'itemCondition'   => 'https://schema.org/NewCondition',
                    'seller'          => ['@id' => $baseUrl . '/#organization']
                ],
                'aggregateRating' => [
                    '@type'       => 'AggregateRating',
                    'ratingValue' => (string)$prod['rating'],
                    'reviewCount' => (string)max(1, (int)$prod['reviews_count']),
                    'bestRating'  => '5',
                    'worstRating' => '1'
                ]
            ]
        ];
    }

    $catalogNode = [
        '@type'           => 'ItemList',
        '@id'             => $baseUrl . '/#catalog',
        'name'            => $siteName . ' Official Product Catalog',
        'numberOfItems'   => count($itemListElements),
        'itemListElement' => $itemListElements
    ];

    $graph = [$organizationNode, $websiteNode, $webpageNode, $catalogNode];

    // 5. FAQPage Entity
    if (!empty($faqs)) {
        $faqEntities = [];
        foreach ($faqs as $q) {
            $faqEntities[] = [
                '@type'          => 'Question',
                'name'           => $q['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $q['answer']
                ]
            ];
        }
        $graph[] = [
            '@type'      => 'FAQPage',
            '@id'        => $baseUrl . '/#faqpage',
            'isPartOf'   => ['@id' => $baseUrl . '/#webpage'],
            'mainEntity' => $faqEntities
        ];
    }

    return json_encode([
        '@context' => 'https://schema.org',
        '@graph'   => $graph
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

/**
 * Generates the clean extensionless outbound click-tracking URL for any product or hero CTA
 * Uses /buy/{product-slug}?src={placement} when slug is available, or go?id={id}&src={placement}
 */
function getBuyNowTrackerUrl(?int $productId, string $placement = 'card', ?string $fallbackUrl = null, ?string $productSlug = null): string {
    if (!empty($productSlug)) {
        return 'buy/' . rawurlencode($productSlug) . '?src=' . rawurlencode($placement);
    }
    if ($productId && $productId > 0) {
        static $slugMap = null;
        if ($slugMap === null) {
            $slugMap = [];
            try {
                $pdo = getDB();
                $rows = $pdo->query("SELECT id, slug FROM products")->fetchAll();
                foreach ($rows as $r) {
                    $slugMap[(int)$r['id']] = $r['slug'];
                }
            } catch (Exception $e) {}
        }
        if (!empty($slugMap[$productId])) {
            return 'buy/' . rawurlencode($slugMap[$productId]) . '?src=' . rawurlencode($placement);
        }
    }

    $params = ['src' => $placement];
    if ($productId) {
        $params['id'] = $productId;
    }
    if ($fallbackUrl) {
        $params['url'] = $fallbackUrl;
    }
    return 'go?' . http_build_query($params);
}

/**
 * Handles file uploads into /assets/uploads/
 */
function handleImageUpload(string $fileInputName, string $prefix = 'img'): ?string {
    if (empty($_FILES[$fileInputName]['name']) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
    $origName = $_FILES[$fileInputName]['name'];
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExts, true)) {
        return null;
    }

    $uploadDir = __DIR__ . '/../assets/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $filename = $prefix . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    $targetPath = $uploadDir . $filename;

    if (move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $targetPath)) {
        return 'assets/uploads/' . $filename;
    }

    return null;
}

/**
 * Resolves an image path for display from either root or /admin/
 */
function resolveImageUrl(?string $path, bool $inAdmin = false): string {
    if (empty($path)) {
        return 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80';
    }
    if (preg_match('~^https?://~i', $path) || str_starts_with($path, '../')) {
        return $path;
    }
    return $inAdmin ? '../' . ltrim($path, '/') : ltrim($path, '/');
}
