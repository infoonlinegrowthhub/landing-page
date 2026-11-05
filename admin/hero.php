<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';

requireAdminAuth();
$pdo = getDB();

$hero = $pdo->query("SELECT * FROM hero_banners ORDER BY sort_order ASC, id DESC LIMIT 1")->fetch();
if (!$hero) {
    $hero = [
        'id'                 => 0,
        'badge_text'         => 'LAUNCH SPECIAL • 40% OFF TODAY',
        'title'              => 'Pure Studio Sound.',
        'highlight_text'     => 'Zero Distractions.',
        'subtitle'           => 'Experience our flagship smart gear with instant external partner checkout.',
        'price_tag'          => '$149',
        'compare_price_tag'  => '$249',
        'cta_text'           => 'Buy Now — Instant Checkout',
        'cta_external_url'   => 'https://buy.stripe.com/test_aerosonic_pro_checkout',
        'secondary_cta_text' => 'Explore All Products',
        'secondary_cta_url'  => '#products',
        'hero_image'         => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=1100&q=85',
        'trust_text'         => '4.9/5 Rating from 1,420+ Verified Buyers • 30-Day Money-Back Guarantee',
        'product_id'         => 1,
        'is_active'          => 1
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $badgeText        = trim($_POST['badge_text'] ?? '');
    $title            = trim($_POST['title'] ?? '');
    $highlightText    = trim($_POST['highlight_text'] ?? '');
    $subtitle         = trim($_POST['subtitle'] ?? '');
    $priceTag         = trim($_POST['price_tag'] ?? '');
    $comparePriceTag  = trim($_POST['compare_price_tag'] ?? '');
    $ctaText          = trim($_POST['cta_text'] ?? 'Buy Now');
    $ctaExternalUrl   = trim($_POST['cta_external_url'] ?? '');
    $secondaryCtaText = trim($_POST['secondary_cta_text'] ?? '');
    $secondaryCtaUrl  = trim($_POST['secondary_cta_url'] ?? '#products');
    $trustText        = trim($_POST['trust_text'] ?? '');
    $productId        = !empty($_POST['product_id']) ? (int)$_POST['product_id'] : null;

    $uploadedHero = handleImageUpload('hero_image_file', 'hero');
    $heroImage    = $uploadedHero ?: trim($_POST['hero_image_url'] ?? $hero['hero_image']);

    if ((int)$hero['id'] > 0) {
        $stmt = $pdo->prepare("
            UPDATE hero_banners SET
                badge_text = ?, title = ?, highlight_text = ?, subtitle = ?,
                price_tag = ?, compare_price_tag = ?, cta_text = ?, cta_external_url = ?,
                secondary_cta_text = ?, secondary_cta_url = ?, hero_image = ?,
                trust_text = ?, product_id = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $badgeText, $title, $highlightText, $subtitle,
            $priceTag, $comparePriceTag, $ctaText, $ctaExternalUrl,
            $secondaryCtaText, $secondaryCtaUrl, $heroImage,
            $trustText, $productId, (int)$hero['id']
        ]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO hero_banners (
                badge_text, title, highlight_text, subtitle,
                price_tag, compare_price_tag, cta_text, cta_external_url,
                secondary_cta_text, secondary_cta_url, hero_image,
                trust_text, product_id, is_active, sort_order
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1)
        ");
        $stmt->execute([
            $badgeText, $title, $highlightText, $subtitle,
            $priceTag, $comparePriceTag, $ctaText, $ctaExternalUrl,
            $secondaryCtaText, $secondaryCtaUrl, $heroImage,
            $trustText, $productId
        ]);
    }

    setFlashMessage('success', 'Hero section & primary Buy Now external link updated!');
    header('Location: hero');
    exit;
}

$allProducts = $pdo->query("SELECT id, name, external_buy_url FROM products ORDER BY sort_order ASC")->fetchAll();
$pageTitle = 'Hero Banner & Primary CTA';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
$flash = getFlashMessage();
$csrfToken = generateCsrfToken();
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <h1 class="mb-0 fs-3">Hero Banner &amp; Primary "Buy Now" CTA</h1>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show">
          <?= e($flash['message']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>" />

        <div class="row">
          <div class="col-lg-8">
            <div class="card card-primary card-outline mb-4">
              <div class="card-header">
                <h3 class="card-title">Hero Copywriting &amp; External Buy Link</h3>
              </div>
              <div class="card-body">
                <div class="row g-3 mb-3">
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">Promo Ribbon / Top Badge</label>
                    <input type="text" name="badge_text" class="form-control" value="<?= e($hero['badge_text']) ?>" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">Link to Product (for Analytics Attribution)</label>
                    <select name="product_id" class="form-select">
                      <option value="">-- Standalone Hero Link --</option>
                      <?php foreach ($allProducts as $ap): ?>
                        <option value="<?= (int)$ap['id'] ?>" <?= (int)$hero['product_id'] === (int)$ap['id'] ? 'selected' : '' ?>>
                          <?= e($ap['name']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>

                <div class="row g-3 mb-3">
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">Main Headline</label>
                    <input type="text" name="title" class="form-control" value="<?= e($hero['title']) ?>" required />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">Gradient Highlight Headline</label>
                    <input type="text" name="highlight_text" class="form-control" value="<?= e($hero['highlight_text']) ?>" />
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Hero Subtitle / Value Proposition</label>
                  <textarea name="subtitle" class="form-control" rows="3"><?= e($hero['subtitle']) ?></textarea>
                </div>

                <div class="row g-3 mb-3">
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">Primary CTA Button Text</label>
                    <input type="text" name="cta_text" class="form-control" value="<?= e($hero['cta_text']) ?>" required />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">Primary External Payment / Website Link <span class="text-danger">*</span></label>
                    <input type="url" name="cta_external_url" class="form-control" value="<?= e($hero['cta_external_url']) ?>" required />
                  </div>
                </div>

                <div class="row g-3 mb-3">
                  <div class="col-md-3">
                    <label class="form-label fw-semibold">Hero Price Callout</label>
                    <input type="text" name="price_tag" class="form-control" value="<?= e($hero['price_tag']) ?>" placeholder="₹2,499" />
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-semibold">Compare Price Callout</label>
                    <input type="text" name="compare_price_tag" class="form-control" value="<?= e($hero['compare_price_tag']) ?>" placeholder="₹4,999" />
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-semibold">Secondary Button Text</label>
                    <input type="text" name="secondary_cta_text" class="form-control" value="<?= e($hero['secondary_cta_text']) ?>" />
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-semibold">Secondary Button Anchor/URL</label>
                    <input type="text" name="secondary_cta_url" class="form-control" value="<?= e($hero['secondary_cta_url']) ?>" />
                  </div>
                </div>

                <div class="mb-0">
                  <label class="form-label fw-semibold">Trust Micro-Copy Below CTA</label>
                  <input type="text" name="trust_text" class="form-control" value="<?= e($hero['trust_text']) ?>" />
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-4">
            <div class="card mb-4">
              <div class="card-header">
                <h3 class="card-title">Hero Showcase Image</h3>
              </div>
              <div class="card-body">
                <div class="text-center mb-3">
                  <img
                    src="<?= e(resolveImageUrl($hero['hero_image'], true)) ?>"
                    alt="Hero Preview"
                    class="img-fluid rounded border shadow-sm"
                    style="max-height: 220px; object-fit: cover;"
                  />
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Upload New Hero Image</label>
                  <input type="file" name="hero_image_file" class="form-control" accept="image/*" />
                </div>
                <div class="mb-0">
                  <label class="form-label fw-semibold">Or Hero Image URL</label>
                  <input type="text" name="hero_image_url" class="form-control" value="<?= e($hero['hero_image']) ?>" />
                </div>
              </div>
              <div class="card-footer d-grid">
                <button type="submit" class="btn btn-primary btn-lg">
                  <i class="bi bi-check2-circle me-1"></i> Save Hero Section
                </button>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

