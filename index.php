<?php
/**
 * Dynamic E-Commerce Product Promotion & External Checkout Landing Page
 * Production GEO, SEO, Core Web Vitals & Security Compliant
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

// Enforce canonical domain (301) and emit RFC 8288 Link header for /llms.txt
enforceCanonicalAndSendGeoHeaders('/');

$pdo = getDB();
$settings = getSiteSettings();

$siteName       = getSetting('site_name', 'NovaPulse');
$legalEntity    = getSetting('legal_entity_name', 'NovaPulse Technologies India Pvt. Ltd.');
$canonicalDom   = getSetting('canonical_domain', 'novapulse.in');
$siteTagline    = getSetting('site_tagline', 'Premium Smart Tech & Audio Gear in India');
$currencySymbol = getSetting('currency_symbol', '₹');
$primaryColor   = getSetting('primary_color', '#4f46e5');
$accentColor    = getSetting('accent_color', '#f97316');
$whatsappNumber = preg_replace('/\D/', '', getSetting('whatsapp_number', '919876543210'));
$baseUrl        = getBaseUrl();

// Dynamic Brand-First Title: <title>{PageName} - {BrandName} | {domain.ext}</title>
$dynamicTitle = formatBrandFirstTitle($siteTagline);

// 1. Fetch Active Hero Banner
$hero = $pdo->query("SELECT * FROM hero_banners WHERE is_active = 1 ORDER BY sort_order ASC, id DESC LIMIT 1")->fetch();

// 2. Fetch Active Features / Trust Benefits
$features = $pdo->query("SELECT * FROM features WHERE is_active = 1 ORDER BY sort_order ASC, id ASC")->fetchAll();

// 3. Fetch Active Categories
$categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, id ASC")->fetchAll();

// 4. Fetch Active Products
$products = $pdo->query("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.is_active = 1
    ORDER BY p.sort_order ASC, p.id DESC
")->fetchAll();

// 5. Identify Flagship Spotlight Product (or fallback to first product)
$spotlightProduct = null;
foreach ($products as $p) {
    if ((int)$p['is_spotlight'] === 1) {
        $spotlightProduct = $p;
        break;
    }
}
if (!$spotlightProduct && !empty($products)) {
    $spotlightProduct = $products[0];
}

// 6. Fetch Active Customer Testimonials
$testimonials = $pdo->query("SELECT * FROM testimonials WHERE is_active = 1 ORDER BY sort_order ASC, id DESC")->fetchAll();

// 7. Fetch Active FAQs
$faqs = $pdo->query("SELECT * FROM faqs WHERE is_active = 1 ORDER BY sort_order ASC, id ASC")->fetchAll();

$heroTrackerUrl = getBuyNowTrackerUrl(
    !empty($hero['product_id']) ? (int)$hero['product_id'] : ($spotlightProduct['id'] ?? null),
    'hero',
    $hero['cta_external_url'] ?? null
);

$heroImageUrl = $hero ? resolveImageUrl($hero['hero_image']) : '';
?>
<!doctype html>
<html lang="en-IN">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5" />
    <title><?= e($dynamicTitle) ?></title>
    <meta name="description" content="<?= e(getSetting('meta_description', $siteTagline)) ?>" />
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1" />

    <!-- Canonical & AI Search (GEO) Discovery Links -->
    <link rel="canonical" href="<?= e($baseUrl . '/') ?>" />
    <link rel="alternate" type="text/plain" title="LLMs Context" href="<?= e($baseUrl . '/llms.txt') ?>" />

    <!-- Open Graph / Social Meta -->
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="<?= e($siteName . ' (' . $canonicalDom . ')') ?>" />
    <meta property="og:title" content="<?= e($dynamicTitle) ?>" />
    <meta property="og:description" content="<?= e(getSetting('meta_description', $siteTagline)) ?>" />
    <meta property="og:url" content="<?= e($baseUrl . '/') ?>" />
    <?php if ($heroImageUrl): ?>
      <meta property="og:image" content="<?= e($heroImageUrl) ?>" />
    <?php endif; ?>

    <!-- Google-Standard 48px-Multiple Favicons with CORS -->
    <link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48x48.png" crossorigin="anonymous" />
    <link rel="icon" type="image/png" sizes="96x96" href="assets/favicons/favicon-96x96.png" crossorigin="anonymous" />
    <link rel="icon" type="image/png" sizes="144x144" href="assets/favicons/favicon-144x144.png" crossorigin="anonymous" />
    <link rel="icon" type="image/png" sizes="192x192" href="assets/favicons/favicon-192x192.png" crossorigin="anonymous" />
    <link rel="icon" type="image/svg+xml" href="assets/favicons/favicon.svg" crossorigin="anonymous" />
    <link rel="shortcut icon" href="favicon.ico" />
    <link rel="apple-touch-icon" sizes="192x192" href="assets/favicons/favicon-192x192.png" />

    <!-- LCP < 1.5s Preload for Primary Hero Visual -->
    <?php if ($heroImageUrl): ?>
      <link rel="preload" as="image" href="<?= e($heroImageUrl) ?>" fetchpriority="high" />
    <?php endif; ?>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

    <!-- Direct, Fixed-Version Pinned CDNs (cdnjs.cloudflare.com) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Custom Landing Page Styles -->
    <link rel="stylesheet" href="assets/css/landing.css" />

    <!-- Dynamic Theme Colors -->
    <style>
      :root {
        --brand-primary: <?= e($primaryColor) ?>;
        --brand-accent: <?= e($accentColor) ?>;
      }
    </style>

    <!-- Dynamic JSON-LD Knowledge Graph (@graph) -->
    <script type="application/ld+json">
<?= generateKnowledgeGraphJsonLd($products, $faqs, $spotlightProduct) ?>
    </script>

    <?= getSetting('custom_head_scripts', '') ?>
  </head>
  <body>
    <div class="page-wrapper">

      <!-- 1. Top Promotional Announcement & Countdown Bar -->
      <?php if (getSetting('announcement_enabled', '1') === '1'): ?>
        <div class="promo-bar">
          <div class="container d-flex flex-wrap justify-content-center align-items-center gap-2 text-center">
            <span class="promo-bar-text"><?= e(getSetting('announcement_text', '🔥 Special Promotional Offer Available Today!')) ?></span>
            <?php if (getSetting('countdown_end')): ?>
              <span class="countdown-pill">
                <i class="bi bi-stopwatch me-1"></i>
                <span id="promoCountdown" data-end="<?= e(getSetting('countdown_end')) ?>">04d : 23h : 59m : 59s</span>
              </span>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- 2. Sticky Navigation Header with Entity Disambiguation -->
      <header class="navbar navbar-expand-lg landing-navbar sticky-top py-2 py-lg-3" itemscope itemtype="https://schema.org/Organization">
        <div class="container">
          <div class="d-flex align-items-center gap-2">
            <a class="navbar-brand-logo" href="<?= e($baseUrl . '/') ?>" itemprop="url">
              <i class="bi bi-lightning-charge-fill text-primary me-1"></i>
              <span itemprop="name"><?= e($siteName) ?></span>
            </a>
          </div>

          <button
            class="navbar-toggler border-0 shadow-none"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar"
            aria-controls="mainNavbar"
            aria-expanded="false"
            aria-label="Toggle navigation"
          >
            <span class="navbar-toggler-icon"></span>
          </button>

          <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav mx-auto mb-3 mb-lg-0 gap-lg-3 fw-medium">
              <?php if ($spotlightProduct): ?>
                <li class="nav-item"><a class="nav-link text-nowrap" href="#spotlight">Spotlight</a></li>
              <?php endif; ?>
              <li class="nav-item"><a class="nav-link text-nowrap" href="#products">Products</a></li>
              <li class="nav-item"><a class="nav-link text-nowrap" href="#benefits">Why Choose Us</a></li>
              <li class="nav-item"><a class="nav-link text-nowrap" href="#reviews">Reviews</a></li>
              <li class="nav-item"><a class="nav-link text-nowrap" href="#faq">FAQ</a></li>
              <li class="nav-item"><a class="nav-link text-nowrap" href="#contact">Inquire</a></li>
            </ul>

            <div class="d-grid d-lg-flex align-items-center">
              <a
                href="<?= e($heroTrackerUrl) ?>"
                target="_blank"
                rel="noopener"
                class="btn btn-buy-primary btn-sm px-4 py-2 text-center text-nowrap"
              >
                Buy Now <i class="bi bi-arrow-up-right-circle-fill ms-1"></i>
              </a>
            </div>
          </div>
        </div>
      </header>

      <main>
        <!-- 3. Dynamic Hero Section with Entity & Purpose Disambiguation -->
        <?php if ($hero): ?>
          <section class="hero-section">
            <div class="container">
              <div class="row align-items-center gy-4">
                <div class="col-lg-6">
                  <?php if (!empty($hero['badge_text'])): ?>
                    <div class="hero-badge mb-3">
                      <i class="bi bi-fire"></i>
                      <span><?= e($hero['badge_text']) ?></span>
                    </div>
                  <?php endif; ?>

                  <h1 class="hero-title mb-3">
                    <?= e($hero['title']) ?>
                    <?php if (!empty($hero['highlight_text'])): ?>
                      <br /><span class="hero-title-highlight"><?= e($hero['highlight_text']) ?></span>
                    <?php endif; ?>
                  </h1>

                  <p class="text-secondary mb-4" style="font-size: 1.05rem; line-height: 1.65;">
                    <?= e($hero['subtitle']) ?>
                  </p>

                  <!-- Entity & Legal Identity Metadata for AI Search / GEO (Visually Hidden) -->
                  <p class="visually-hidden">
                    Official direct-to-consumer product showcase by <?= e($siteName) ?> (<?= e($legalEntity) ?>, <?= e($canonicalDom) ?>) — promoting verified Indian smart audio &amp; lifestyle gear via instant partner payment links.
                  </p>

                  <?php if (!empty($hero['price_tag'])): ?>
                    <div class="d-flex flex-wrap align-items-baseline gap-2 mb-4">
                      <span class="fs-7 text-uppercase fw-bold text-secondary">Special Launch Price:</span>
                      <span class="fs-1 fw-extrabold text-dark lh-1"><?= e($hero['price_tag']) ?></span>
                      <?php if (!empty($hero['compare_price_tag'])): ?>
                        <span class="fs-5 text-secondary text-decoration-line-through"><?= e($hero['compare_price_tag']) ?></span>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>

                  <div class="d-flex flex-wrap align-items-center gap-2 gap-sm-3 mb-4 hero-cta-group">
                    <a
                      href="<?= e($heroTrackerUrl) ?>"
                      target="_blank"
                      rel="noopener"
                      class="btn btn-buy-accent d-inline-flex align-items-center justify-content-center"
                    >
                      <i class="bi bi-bag-check-fill me-2"></i>
                      <span><?= e($hero['cta_text']) ?></span>
                      <i class="bi bi-box-arrow-up-right ms-2"></i>
                    </a>
                    <?php if (!empty($hero['secondary_cta_text'])): ?>
                      <a href="<?= e($hero['secondary_cta_url'] ?: '#products') ?>" class="btn btn-outline-dark rounded-pill px-4 py-2 fw-semibold">
                        <?= e($hero['secondary_cta_text']) ?>
                      </a>
                    <?php endif; ?>
                  </div>

                  <?php if (!empty($hero['trust_text'])): ?>
                    <div class="d-flex flex-wrap align-items-center gap-2 text-secondary fs-7">
                      <span class="text-warning text-nowrap">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                      </span>
                      <span><?= e($hero['trust_text']) ?></span>
                    </div>
                  <?php endif; ?>
                </div>

                <div class="col-lg-6">
                  <div class="hero-visual-wrapper">
                    <img
                      src="<?= e($heroImageUrl) ?>"
                      alt="<?= e($hero['title'] . ' — ' . $siteName) ?>"
                      class="hero-main-img"
                      fetchpriority="high"
                      decoding="async"
                      width="640"
                      height="450"
                    />
                    <div class="hero-floating-card d-none d-sm-flex align-items-center gap-3">
                      <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                        <i class="bi bi-shield-fill-check fs-4"></i>
                      </div>
                      <div>
                        <div class="fw-bold text-dark fs-7">100% Verified Checkout Link</div>
                        <div class="text-secondary" style="font-size: 0.76rem;">Supports UPI, Cards, NetBanking &amp; Official Partners</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>
        <?php endif; ?>

        <!-- 4. Why Choose Us / Key Benefits Strip -->
        <?php if (!empty($features)): ?>
          <section id="benefits" class="py-5 bg-light border-top border-bottom">
            <div class="container">
              <div class="row g-3 g-lg-4">
                <?php foreach ($features as $feat): ?>
                  <div class="col-sm-6 col-lg-3">
                    <div class="benefit-card">
                      <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="benefit-icon-box">
                          <i class="bi <?= e($feat['icon']) ?>"></i>
                        </div>
                        <?php if (!empty($feat['badge'])): ?>
                          <span class="badge rounded-pill text-bg-light border text-secondary"><?= e($feat['badge']) ?></span>
                        <?php endif; ?>
                      </div>
                      <h2 class="h6 fw-bold mb-2"><?= e($feat['title']) ?></h2>
                      <p class="text-secondary fs-7 mb-0"><?= e($feat['description']) ?></p>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </section>
        <?php endif; ?>

        <!-- 5. Flagship Product Spotlight Section -->
        <?php if ($spotlightProduct): ?>
          <?php
            $spotGallery  = json_decode($spotlightProduct['gallery_images'] ?: '[]', true) ?: [$spotlightProduct['main_image']];
            $spotFeatures = json_decode($spotlightProduct['features_json'] ?: '[]', true) ?: [];
            $spotBuyUrl   = getBuyNowTrackerUrl((int)$spotlightProduct['id'], 'spotlight');
            $spotSavings  = ($spotlightProduct['compare_at_price'] > $spotlightProduct['price'])
                ? ($spotlightProduct['compare_at_price'] - $spotlightProduct['price'])
                : 0;
          ?>
          <section id="spotlight" class="py-5">
            <div class="container">
              <div class="spotlight-section">
                <div class="row align-items-stretch gy-4">
                  <div class="col-lg-5">
                    <div class="spotlight-media-card">
                      <div class="d-flex flex-column flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                          <span class="badge bg-white bg-opacity-10 text-white px-3 py-1 rounded-pill fs-7">
                            <i class="bi bi-camera-fill me-1 text-warning"></i> Interactive Preview
                          </span>
                          <span class="badge text-bg-success px-3 py-1 rounded-pill fs-7">
                            <i class="bi bi-check2-circle me-1"></i><?= e($spotlightProduct['stock_status']) ?>
                          </span>
                        </div>

                        <img
                          id="spotlightMainImage"
                          src="<?= e(resolveImageUrl($spotlightProduct['main_image'])) ?>"
                          alt="<?= e($spotlightProduct['name']) ?>"
                          class="spotlight-main-img mb-3"
                          width="520"
                          height="300"
                          loading="lazy"
                          decoding="async"
                        />

                        <?php if (count($spotGallery) > 1): ?>
                          <div class="d-flex gap-2 flex-wrap mb-3">
                            <?php foreach ($spotGallery as $idx => $gImg): ?>
                              <img
                                src="<?= e(resolveImageUrl($gImg)) ?>"
                                data-full="<?= e(resolveImageUrl($gImg)) ?>"
                                alt="<?= e($spotlightProduct['name'] . ' view ' . ($idx + 1)) ?>"
                                class="spotlight-thumb <?= $idx === 0 ? 'active' : '' ?>"
                                width="64"
                                height="64"
                                loading="lazy"
                                decoding="async"
                              />
                            <?php endforeach; ?>
                          </div>
                        <?php endif; ?>
                      </div>

                      <div class="row g-2 mt-2">
                        <div class="col-6">
                          <div class="spotlight-spec-mini d-flex align-items-center gap-2">
                            <i class="bi bi-truck text-warning fs-5"></i>
                            <div>
                              <div class="fw-bold text-white">Free Delivery</div>
                              <div class="text-white-50" style="font-size:0.72rem;">Pan-India Express</div>
                            </div>
                          </div>
                        </div>
                        <div class="col-6">
                          <div class="spotlight-spec-mini d-flex align-items-center gap-2">
                            <i class="bi bi-shield-check text-info fs-5"></i>
                            <div>
                              <div class="fw-bold text-white">1-Yr Warranty</div>
                              <div class="text-white-50" style="font-size:0.72rem;">Official India Support</div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="col-lg-7 d-flex flex-column justify-content-between">
                    <div>
                      <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <span class="badge text-bg-warning text-dark fw-bold px-3 py-2 rounded-pill">
                          <i class="bi bi-star-fill me-1"></i> FLAGSHIP SPOTLIGHT
                        </span>
                        <?php if (!empty($spotlightProduct['discount_badge'])): ?>
                          <span class="badge text-bg-danger px-3 py-2 rounded-pill"><?= e($spotlightProduct['discount_badge']) ?></span>
                        <?php endif; ?>
                        <span class="badge bg-white bg-opacity-10 text-white px-3 py-2 rounded-pill">
                          <i class="bi bi-patch-check-fill me-1 text-info"></i><?= e($spotlightProduct['platform_label'] ?: 'Official Link') ?>
                        </span>
                      </div>

                      <h2 class="h1 fw-bold mb-2"><?= e($spotlightProduct['name']) ?></h2>
                      <?php if (!empty($spotlightProduct['subtitle'])): ?>
                        <p class="text-info fw-medium mb-3"><?= e($spotlightProduct['subtitle']) ?></p>
                      <?php endif; ?>

                      <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <span class="text-warning">
                          <?= str_repeat('<i class="bi bi-star-fill"></i>', (int)round($spotlightProduct['rating'])) ?>
                        </span>
                        <span class="fw-bold"><?= e($spotlightProduct['rating']) ?>/5</span>
                        <span class="text-white-50">(<?= number_format((int)$spotlightProduct['reviews_count']) ?> verified buyer ratings)</span>
                      </div>

                      <p class="text-white-50 mb-3"><?= e($spotlightProduct['full_description'] ?: $spotlightProduct['short_description']) ?></p>

                      <?php if (!empty($spotFeatures)): ?>
                        <ul class="list-unstyled mb-4">
                          <?php foreach ($spotFeatures as $sf): ?>
                            <li class="mb-2 d-flex align-items-start gap-2 fs-7">
                              <i class="bi bi-check-circle-fill text-warning mt-1 flex-shrink-0"></i>
                              <span><?= e($sf) ?></span>
                            </li>
                          <?php endforeach; ?>
                        </ul>
                      <?php endif; ?>
                    </div>

                    <div class="p-3 rounded-4 bg-white bg-opacity-10 d-flex flex-wrap align-items-center justify-content-between gap-3">
                      <div>
                        <div class="text-white-50 fs-7">Special Offer Price (Incl. GST)</div>
                        <div class="d-flex flex-wrap align-items-baseline gap-2">
                          <span class="fs-2 fw-extrabold text-white"><?= formatPrice($spotlightProduct['price']) ?></span>
                          <?php if ($spotlightProduct['compare_at_price'] > $spotlightProduct['price']): ?>
                            <span class="fs-5 text-white-50 text-decoration-line-through"><?= formatPrice($spotlightProduct['compare_at_price']) ?></span>
                          <?php endif; ?>
                          <?php if ($spotSavings > 0): ?>
                            <span class="badge text-bg-success">Save <?= formatPrice($spotSavings) ?></span>
                          <?php endif; ?>
                        </div>
                      </div>

                      <a
                        href="<?= e($spotBuyUrl) ?>"
                        <?= $spotlightProduct['open_in_new_tab'] ? 'target="_blank" rel="noopener"' : '' ?>
                        class="btn btn-buy-accent px-4 py-3 flex-grow-1 flex-sm-grow-0 text-center"
                      >
                        <i class="bi bi-bag-check-fill me-2"></i><?= e($spotlightProduct['buy_button_text']) ?>
                        <i class="bi bi-box-arrow-up-right ms-1"></i>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>
        <?php endif; ?>

        <!-- 6. Dynamic Product Showcase Catalog Grid -->
        <section id="products" class="py-5">
          <div class="container">
            <div class="row justify-content-between align-items-end mb-4 gy-3">
              <div class="col-lg-7">
                <span class="text-primary fw-bold text-uppercase fs-7">Official Store Catalog</span>
                <h2 class="h1 fw-bold mb-1">Featured Products &amp; Instant Checkout</h2>
                <p class="text-secondary mb-0">Click Quick View for full specs or Buy Now to proceed directly to our verified payment link.</p>
              </div>

              <div class="col-lg-4">
                <div class="input-group">
                  <span class="input-group-text bg-white"><i class="bi bi-search text-secondary"></i></span>
                  <input
                    type="search"
                    id="productSearchInput"
                    class="form-control"
                    placeholder="Search products..."
                    aria-label="Search products"
                  />
                </div>
              </div>
            </div>

            <!-- Category Filter Pills -->
            <div class="d-flex flex-wrap gap-2 mb-4">
              <button type="button" class="category-pill-btn active" data-filter="all">
                <i class="bi bi-grid-fill me-1"></i> All (<?= count($products) ?>)
              </button>
              <?php foreach ($categories as $cat): ?>
                <button type="button" class="category-pill-btn" data-filter="<?= e($cat['slug']) ?>">
                  <i class="bi <?= e($cat['icon']) ?> me-1"></i> <?= e($cat['name']) ?>
                </button>
              <?php endforeach; ?>
            </div>

            <!-- Product Cards Row -->
            <div class="row g-4" id="productGridContainer">
              <?php foreach ($products as $prod): ?>
                <?php
                  $cardBuyUrl = getBuyNowTrackerUrl((int)$prod['id'], 'card');
                  $prodGalleryRaw = json_decode($prod['gallery_images'] ?: '[]', true) ?: [];
                  if (empty($prodGalleryRaw)) {
                      $prodGalleryRaw = [$prod['main_image']];
                  }
                  $prodGalleryResolved = array_values(array_unique(array_map('resolveImageUrl', array_merge([$prod['main_image']], $prodGalleryRaw))));
                  $modalPayload = [
                      'id'                  => (int)$prod['id'],
                      'slug'                => $prod['slug'],
                      'name'                => $prod['name'],
                      'subtitle'            => $prod['subtitle'],
                      'category_name'       => $prod['category_name'] ?: 'Featured',
                      'platform_label'      => $prod['platform_label'] ?: 'Official Link',
                      'price_formatted'     => formatPrice($prod['price']),
                      'compare_formatted'   => ($prod['compare_at_price'] > $prod['price']) ? formatPrice($prod['compare_at_price']) : '',
                      'discount_badge'      => $prod['discount_badge'] ?: '',
                      'rating'              => $prod['rating'] ?: '4.9',
                      'reviews_count'       => (int)$prod['reviews_count'],
                      'stock_status'        => $prod['stock_status'] ?: 'In Stock',
                      'short_description'   => $prod['short_description'],
                      'full_description'    => $prod['full_description'],
                      'main_image_resolved' => resolveImageUrl($prod['main_image']),
                      'gallery'             => $prodGalleryResolved,
                      'features'            => json_decode($prod['features_json'] ?: '[]', true) ?: [],
                      'buy_button_text'     => $prod['buy_button_text'] ?: 'Buy Now',
                      'open_in_new_tab'     => (int)$prod['open_in_new_tab']
                  ];
                ?>
                <div
                  class="col-md-6 col-lg-4 product-grid-item"
                  data-category="<?= e($prod['category_slug'] ?: 'uncategorized') ?>"
                  data-title="<?= e($prod['name'] . ' ' . $prod['subtitle'] . ' ' . $prod['category_name']) ?>"
                >
                  <article class="product-card">
                    <div class="product-img-wrap">
                      <img
                        src="<?= e(resolveImageUrl($prod['main_image'])) ?>"
                        alt="<?= e($prod['name']) ?>"
                        width="400"
                        height="235"
                        loading="lazy"
                        decoding="async"
                      />
                      <div class="product-badge-top d-flex gap-1">
                        <?php if (!empty($prod['discount_badge'])): ?>
                          <span class="badge text-bg-danger shadow-sm"><?= e($prod['discount_badge']) ?></span>
                        <?php endif; ?>
                        <?php if ($prod['stock_status'] === 'Limited Stock'): ?>
                          <span class="badge text-bg-warning text-dark shadow-sm">Limited Stock</span>
                        <?php endif; ?>
                      </div>
                      <div class="product-platform-tag">
                        <i class="bi bi-shield-lock-fill me-1 text-info"></i><?= e($prod['platform_label'] ?: 'Verified Link') ?>
                      </div>
                    </div>

                    <div class="p-3 p-sm-4 d-flex flex-column flex-grow-1">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge text-bg-light border text-secondary"><?= e($prod['category_name'] ?: 'General') ?></span>
                        <span class="fs-7 fw-semibold text-warning">
                          <i class="bi bi-star-fill"></i> <?= e($prod['rating']) ?>
                          <span class="text-secondary fw-normal">(<?= (int)$prod['reviews_count'] ?>)</span>
                        </span>
                      </div>

                      <h3 class="h5 fw-bold mb-1"><?= e($prod['name']) ?></h3>
                      <?php if (!empty($prod['subtitle'])): ?>
                        <div class="text-primary fs-7 fw-medium mb-2"><?= e($prod['subtitle']) ?></div>
                      <?php endif; ?>

                      <p class="text-secondary fs-7 mb-3 flex-grow-1">
                        <?= e($prod['short_description']) ?>
                      </p>

                      <div class="d-flex align-items-baseline justify-content-between pt-3 border-top mb-3">
                        <div>
                          <span class="fs-4 fw-extrabold text-dark"><?= formatPrice($prod['price']) ?></span>
                          <?php if ($prod['compare_at_price'] > $prod['price']): ?>
                            <span class="text-secondary text-decoration-line-through ms-1"><?= formatPrice($prod['compare_at_price']) ?></span>
                          <?php endif; ?>
                        </div>
                        <span class="badge <?= $prod['stock_status'] === 'Sold Out' ? 'text-bg-secondary' : 'text-bg-success bg-opacity-10 text-success' ?>">
                          <?= e($prod['stock_status']) ?>
                        </span>
                      </div>

                      <div class="d-flex gap-2">
                        <button
                          type="button"
                          class="btn btn-outline-secondary rounded-pill px-3 btn-quick-view"
                          data-product="<?= e(json_encode($modalPayload)) ?>"
                          title="Quick View Specifications"
                          aria-label="Quick View <?= e($prod['name']) ?>"
                        >
                          <i class="bi bi-eye"></i>
                        </button>
                        <a
                          href="<?= e($cardBuyUrl) ?>"
                          <?= $prod['open_in_new_tab'] ? 'target="_blank" rel="noopener"' : '' ?>
                          class="btn btn-buy-primary flex-grow-1 d-flex align-items-center justify-content-center gap-2 py-2"
                        >
                          <span><?= e($prod['buy_button_text']) ?></span>
                          <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                      </div>
                    </div>
                  </article>
                </div>
              <?php endforeach; ?>
            </div>

            <div id="productsEmptyState" class="text-center py-5 d-none">
              <i class="bi bi-search fs-1 text-secondary"></i>
              <h4 class="mt-3">No matching products found</h4>
              <p class="text-secondary">Try clearing your search or selecting another category tab.</p>
            </div>
          </div>
        </section>

        <!-- 7. Customer Testimonials & Verified Social Proof -->
        <?php if (!empty($testimonials)): ?>
          <section id="reviews" class="py-5 bg-light border-top border-bottom">
            <div class="container">
              <div class="text-center mx-auto mb-5" style="max-width: 640px;">
                <span class="text-primary fw-bold text-uppercase fs-7">Verified Buyer Reviews</span>
                <h2 class="h1 fw-bold">Loved by Customers Across India</h2>
                <p class="text-secondary mb-0">Real stories from buyers who ordered via our official payment and marketplace links.</p>
              </div>

              <div class="row g-4">
                <?php foreach ($testimonials as $testi): ?>
                  <div class="col-md-6 col-lg-4">
                    <div class="testimonial-card d-flex flex-column justify-content-between">
                      <div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                          <span class="text-warning">
                            <?= str_repeat('<i class="bi bi-star-fill"></i> ', (int)$testi['rating']) ?>
                          </span>
                          <?php if ($testi['is_verified']): ?>
                            <span class="badge text-bg-success bg-opacity-10 text-success">
                              <i class="bi bi-patch-check-fill me-1"></i>Verified Buyer
                            </span>
                          <?php endif; ?>
                        </div>

                        <?php if (!empty($testi['review_title'])): ?>
                          <h3 class="h6 fw-bold mb-2">"<?= e($testi['review_title']) ?>"</h3>
                        <?php endif; ?>

                        <p class="text-secondary fs-7 mb-4"><?= e($testi['review_text']) ?></p>
                      </div>

                      <div class="d-flex align-items-center gap-3 pt-3 border-top">
                        <img
                          src="<?= e(resolveImageUrl($testi['customer_avatar'])) ?>"
                          alt="<?= e($testi['customer_name']) ?>"
                          class="rounded-circle border flex-shrink-0"
                          width="46"
                          height="46"
                          loading="lazy"
                          decoding="async"
                          style="width: 46px; height: 46px; object-fit: cover;"
                        />
                        <div>
                          <div class="fw-bold text-dark"><?= e($testi['customer_name']) ?></div>
                          <div class="text-secondary" style="font-size: 0.78rem;">
                            <?= e($testi['customer_role']) ?>
                            <?= $testi['product_name'] ? ' • ' . e($testi['product_name']) : '' ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </section>
        <?php endif; ?>

        <!-- 8. Frequently Asked Questions (FAQ) Accordion -->
        <?php if (!empty($faqs)): ?>
          <section id="faq" class="py-5">
            <div class="container">
              <div class="row justify-content-center">
                <div class="col-lg-8">
                  <div class="text-center mb-4">
                    <span class="text-primary fw-bold text-uppercase fs-7">Got Questions?</span>
                    <h2 class="h1 fw-bold">Frequently Asked Questions</h2>
                    <p class="text-secondary">Everything you need to know about UPI/Card payment links, pan-India delivery, and warranty.</p>
                  </div>

                  <div class="accordion" id="faqAccordion">
                    <?php foreach ($faqs as $idx => $faq): ?>
                      <div class="accordion-item border rounded-4 mb-3 overflow-hidden shadow-sm">
                        <h3 class="accordion-header" id="faqHeading<?= $idx ?>">
                          <button
                            class="accordion-button <?= $idx === 0 ? '' : 'collapsed' ?> fw-bold py-3"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#faqCollapse<?= $idx ?>"
                            aria-expanded="<?= $idx === 0 ? 'true' : 'false' ?>"
                          >
                            <span class="badge text-bg-primary me-2"><?= e($faq['category']) ?></span>
                            <span><?= e($faq['question']) ?></span>
                          </button>
                        </h3>
                        <div
                          id="faqCollapse<?= $idx ?>"
                          class="accordion-collapse collapse <?= $idx === 0 ? 'show' : '' ?>"
                          data-bs-parent="#faqAccordion"
                        >
                          <div class="accordion-body text-secondary">
                            <?= nl2br(e($faq['answer'])) ?>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div>
          </section>
        <?php endif; ?>

        <!-- 9. Pre-Sale Inquiry & Bulk Order Section -->
        <section id="contact" class="py-5 bg-light border-top">
          <div class="container">
            <div class="row gy-4 align-items-center">
              <div class="col-lg-5">
                <span class="text-primary fw-bold text-uppercase fs-7">Direct Support &amp; Bulk Orders</span>
                <h2 class="h1 fw-bold mb-3">Need a Custom Payment Link or GST Invoice?</h2>
                <p class="text-secondary mb-4">
                  Our support team responds within a few hours. Reach out for corporate bulk orders, custom payment links, or product questions.
                </p>

                <div class="d-flex flex-column gap-3">
                  <div class="d-flex align-items-center gap-3 p-3 rounded-4 bg-white border">
                    <i class="bi bi-envelope-paper-fill fs-3 text-primary"></i>
                    <div class="text-truncate">
                      <div class="fs-7 text-secondary">Email Support</div>
                      <a href="mailto:<?= e(getSetting('contact_email')) ?>" class="fw-bold text-dark text-decoration-none">
                        <?= e(getSetting('contact_email')) ?>
                      </a>
                    </div>
                  </div>

                  <div class="d-flex align-items-center gap-3 p-3 rounded-4 bg-white border">
                    <i class="bi bi-telephone-inbound-fill fs-3 text-success"></i>
                    <div>
                      <div class="fs-7 text-secondary">Phone / WhatsApp Support</div>
                      <div class="fw-bold text-dark"><?= e(getSetting('contact_phone')) ?></div>
                    </div>
                  </div>

                  <div class="d-flex align-items-center gap-3 p-3 rounded-4 bg-white border">
                    <i class="bi bi-geo-alt-fill fs-3 text-danger"></i>
                    <div>
                      <div class="fs-7 text-secondary">Registered Office</div>
                      <div class="fw-semibold text-dark fs-7"><?= e(getSetting('contact_address')) ?></div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 p-3 p-sm-4">
                  <h3 class="h4 fw-bold mb-3">Send a Pre-Sale Inquiry</h3>
                  <form id="inquiryForm" method="post" action="contact-submit">
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label class="form-label fw-semibold">Your Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Rahul Sharma" required />
                      </div>
                      <div class="col-md-6">
                        <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="rahul@example.in" required />
                      </div>
                      <div class="col-md-6">
                        <label class="form-label fw-semibold">Phone / WhatsApp Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="+91 98765 43210" />
                      </div>
                      <div class="col-md-6">
                        <label class="form-label fw-semibold">Product of Interest</label>
                        <select name="product_interest" class="form-select">
                          <option value="">-- Select Product --</option>
                          <?php foreach ($products as $prod): ?>
                            <option value="<?= e($prod['name']) ?>"><?= e($prod['name']) ?> (<?= formatPrice($prod['price']) ?>)</option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-12">
                        <label class="form-label fw-semibold">Subject</label>
                        <input type="text" name="subject" class="form-control" placeholder="Question about product specs or payment link" />
                      </div>
                      <div class="col-12">
                        <label class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
                        <textarea name="message" class="form-control" rows="3" placeholder="How can we help you today?" required></textarea>
                      </div>
                      <div class="col-12">
                        <button type="submit" class="btn btn-buy-primary w-100 py-3">
                          <i class="bi bi-send-fill me-2"></i> Send Inquiry
                        </button>
                      </div>
                    </div>
                    <div id="inquiryFeedback" class="d-none"></div>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </section>
      </main>

      <!-- 10. Footer -->
      <footer class="bg-dark text-white pt-5 pb-5 mb-4 mb-md-0">
        <div class="container">
          <div class="row gy-4 pb-4 border-bottom border-secondary border-opacity-25">
            <div class="col-lg-5">
              <a class="navbar-brand-logo text-white d-inline-block mb-2" href="<?= e($baseUrl . '/') ?>">
                <i class="bi bi-lightning-charge-fill text-info me-1"></i><?= e($siteName) ?>
              </a>
              <p class="text-white-50 fs-7 mb-2"><?= e($siteTagline) ?></p>
              <p class="text-white-50 mb-0" style="font-size: 0.8rem;">
                <i class="bi bi-shield-check text-success me-1"></i>
                <?= e(getSetting('footer_disclaimer')) ?>
              </p>
            </div>

            <div class="col-6 col-lg-3">
              <h6 class="text-uppercase fw-bold mb-3">Quick Links</h6>
              <ul class="list-unstyled fs-7 d-flex flex-column gap-2 mb-0">
                <li><a href="#spotlight" class="text-white-50 text-decoration-none">Flagship Spotlight</a></li>
                <li><a href="#products" class="text-white-50 text-decoration-none">All Products</a></li>
                <li><a href="#benefits" class="text-white-50 text-decoration-none">Why Choose Us</a></li>
                <li><a href="#reviews" class="text-white-50 text-decoration-none">Customer Reviews</a></li>
                <li><a href="#faq" class="text-white-50 text-decoration-none">FAQs</a></li>
                <li><a href="llms.txt" class="text-white-50 text-decoration-none">AI Context (llms.txt)</a></li>
              </ul>
            </div>

            <div class="col-6 col-lg-4">
              <h6 class="text-uppercase fw-bold mb-3">Connect With Us</h6>
              <div class="d-flex gap-2 mb-3">
                <?php if (getSetting('instagram_url')): ?>
                  <a href="<?= e(getSetting('instagram_url')) ?>" target="_blank" rel="noopener" class="btn btn-outline-light btn-sm rounded-circle" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                <?php endif; ?>
                <?php if (getSetting('facebook_url')): ?>
                  <a href="<?= e(getSetting('facebook_url')) ?>" target="_blank" rel="noopener" class="btn btn-outline-light btn-sm rounded-circle" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                <?php endif; ?>
                <?php if (getSetting('twitter_url')): ?>
                  <a href="<?= e(getSetting('twitter_url')) ?>" target="_blank" rel="noopener" class="btn btn-outline-light btn-sm rounded-circle" aria-label="X"><i class="bi bi-twitter-x"></i></a>
                <?php endif; ?>
                <?php if (getSetting('youtube_url')): ?>
                  <a href="<?= e(getSetting('youtube_url')) ?>" target="_blank" rel="noopener" class="btn btn-outline-light btn-sm rounded-circle" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                <?php endif; ?>
              </div>
              <div class="fs-7 text-white-50">
                &copy; <?= date('Y') ?> <?= e($siteName) ?>. All Rights Reserved.
              </div>
            </div>
          </div>
        </div>
      </footer>

    </div><!-- /.page-wrapper -->

    <!-- Quick-View Product Modal -->
    <div class="modal fade" id="quickViewModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
          <div class="modal-body p-3 p-sm-4">
            <div class="row g-3 g-md-4 align-items-stretch">
              <!-- Left Column: Landscape Product Image + Gallery + Buyer Assurance -->
              <div class="col-md-5 d-flex">
                <div class="qv-media-card w-100 d-flex flex-column justify-content-between p-3 rounded-4 bg-light border">
                  <div>
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                      <span id="qvStockBadge" class="badge text-bg-success rounded-pill px-2 py-1">In Stock</span>
                      <span id="qvRatingBadge" class="badge text-bg-warning text-dark rounded-pill px-2 py-1">★ 4.9</span>
                    </div>

                    <img
                      id="qvMainImage"
                      src=""
                      alt="Product"
                      width="360"
                      height="215"
                      decoding="async"
                      class="qv-main-img w-100 rounded-3 border bg-white"
                    />
                    <div id="qvGalleryThumbs" class="d-flex gap-2 flex-wrap mt-2"></div>
                  </div>

                  <div class="d-flex flex-column gap-2 mt-3 pt-2 border-top">
                    <div class="d-flex align-items-center gap-2 p-2 rounded-3 bg-white border">
                      <i class="bi bi-truck text-primary fs-5 ms-1"></i>
                      <div style="line-height: 1.2;">
                        <div class="fw-bold text-dark" style="font-size: 0.76rem;">Free Pan-India Express Delivery</div>
                        <div class="text-secondary" style="font-size: 0.7rem;">Dispatched within 24 hrs • Tracked Shipping</div>
                      </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 p-2 rounded-3 bg-white border">
                      <i class="bi bi-shield-check text-success fs-5 ms-1"></i>
                      <div style="line-height: 1.2;">
                        <div class="fw-bold text-dark" style="font-size: 0.76rem;">1-Year Official India Warranty</div>
                        <div class="text-secondary" style="font-size: 0.7rem;">100% Genuine • GST Invoice Included</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Right Column: Compact Specs & Direct Partner Checkout -->
              <div class="col-md-7 d-flex flex-column justify-content-between">
                <div>
                  <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                    <div class="d-flex flex-wrap gap-1">
                      <span id="qvCategory" class="badge text-bg-primary"></span>
                      <span id="qvPlatform" class="badge text-bg-secondary"></span>
                      <span id="qvDiscountBadge" class="badge text-bg-danger d-none"></span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>

                  <h3 id="qvTitle" class="h4 fw-bold mb-1"></h3>
                  <div id="qvSubtitle" class="text-primary fs-7 fw-semibold mb-2"></div>

                  <div class="d-flex align-items-baseline gap-2 py-2 px-3 rounded-3 bg-light border mb-2">
                    <span id="qvPrice" class="fs-3 fw-extrabold text-dark"></span>
                    <span id="qvComparePrice" class="fs-6 text-secondary text-decoration-line-through"></span>
                    <span class="ms-auto badge text-bg-success bg-opacity-10 text-success">Incl. all taxes</span>
                  </div>

                  <p id="qvDescription" class="text-secondary fs-7 mb-2"></p>
                  <ul id="qvFeaturesList" class="list-unstyled fs-7 mb-2"></ul>
                </div>

                <div class="d-grid pt-2">
                  <a id="qvBuyButton" href="#" class="btn btn-buy-accent py-2 fw-bold fs-6">
                    Buy Now
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Floating WhatsApp Button (contain: layout paint; will-change: transform) -->
    <?php if (!empty($whatsappNumber)): ?>
      <a
        href="https://wa.me/<?= e($whatsappNumber) ?>?text=<?= urlencode('Hi ' . $siteName . ', I have a question about your products!') ?>"
        target="_blank"
        rel="noopener"
        class="whatsapp-float"
        title="Chat on WhatsApp"
        aria-label="Chat on WhatsApp"
      >
        <i class="bi bi-whatsapp"></i>
      </a>
    <?php endif; ?>

    <!-- Sticky Bottom Conversion Bar (contain: layout paint; will-change: transform) -->
    <?php if (getSetting('sticky_bar_enabled', '1') === '1' && $spotlightProduct): ?>
      <div id="stickyConversionBar" class="sticky-conversion-bar">
        <div class="container d-flex align-items-center justify-content-between gap-2">
          <div class="d-flex align-items-center gap-2" style="min-width: 0; flex: 1 1 auto;">
            <img
              src="<?= e(resolveImageUrl($spotlightProduct['main_image'])) ?>"
              alt="<?= e($spotlightProduct['name']) ?>"
              width="42"
              height="42"
              loading="lazy"
              decoding="async"
              class="rounded-3 d-none d-sm-block flex-shrink-0"
              style="width: 42px; height: 42px; object-fit: cover;"
            />
            <div style="min-width: 0;">
              <div class="fw-bold text-white text-truncate" style="font-size: 0.92rem;"><?= e($spotlightProduct['name']) ?></div>
              <div class="text-white-50 text-truncate" style="font-size: 0.78rem;">
                <span class="text-warning fw-bold"><?= formatPrice($spotlightProduct['price']) ?></span>
                <?php if ($spotlightProduct['compare_at_price'] > $spotlightProduct['price']): ?>
                  <span class="text-decoration-line-through ms-1"><?= formatPrice($spotlightProduct['compare_at_price']) ?></span>
                <?php endif; ?>
                <span class="d-none d-md-inline"> • <?= e($spotlightProduct['platform_label']) ?></span>
              </div>
            </div>
          </div>
          <a
            href="<?= e(getBuyNowTrackerUrl((int)$spotlightProduct['id'], 'sticky_bar')) ?>"
            <?= $spotlightProduct['open_in_new_tab'] ? 'target="_blank" rel="noopener"' : '' ?>
            class="btn btn-buy-accent btn-sm px-3 py-2 text-nowrap flex-shrink-0"
          >
            <i class="bi bi-bag-check-fill me-1"></i> <?= e($spotlightProduct['buy_button_text']) ?>
          </a>
        </div>
      </div>
    <?php endif; ?>

    <!-- Direct Pinned CDN Bootstrap JS & Custom Landing JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="assets/js/landing.js"></script>
  </body>
</html>
