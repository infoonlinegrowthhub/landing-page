<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';

requireAdminAuth();
$pdo = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;

$product = [
    'category_id'       => '',
    'name'              => '',
    'slug'              => '',
    'subtitle'          => '',
    'short_description' => '',
    'full_description'  => '',
    'price'             => '1999.00',
    'compare_at_price'  => '3499.00',
    'discount_badge'    => 'SAVE 40%',
    'main_image'        => '',
    'gallery_images'    => '[]',
    'features_json'     => '[]',
    'external_buy_url'  => '',
    'buy_button_text'   => 'Buy Now — ₹1,999',
    'platform_label'    => 'Razorpay / UPI Link',
    'open_in_new_tab'   => 1,
    'rating'            => '4.9',
    'reviews_count'     => 120,
    'stock_status'      => 'In Stock',
    'is_featured'       => 1,
    'is_spotlight'      => 0,
    'is_active'         => 1,
    'sort_order'        => 1
];

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        setFlashMessage('danger', 'Product not found.');
        header('Location: products');
        exit;
    }
    $product = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $name             = trim($_POST['name'] ?? '');
    $categoryId       = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $subtitle         = trim($_POST['subtitle'] ?? '');
    $shortDesc        = trim($_POST['short_description'] ?? '');
    $fullDesc         = trim($_POST['full_description'] ?? '');
    $price            = (float)($_POST['price'] ?? 0);
    $comparePrice     = !empty($_POST['compare_at_price']) ? (float)$_POST['compare_at_price'] : null;
    $discountBadge    = trim($_POST['discount_badge'] ?? '');
    $externalBuyUrl   = trim($_POST['external_buy_url'] ?? '');
    $buyButtonText    = trim($_POST['buy_button_text'] ?? 'Buy Now');
    $platformLabel    = trim($_POST['platform_label'] ?? 'Official Store');
    $openInNewTab     = isset($_POST['open_in_new_tab']) ? 1 : 0;
    $rating           = min(5.0, max(1.0, (float)($_POST['rating'] ?? 4.9)));
    $reviewsCount     = max(0, (int)($_POST['reviews_count'] ?? 100));
    $stockStatus      = $_POST['stock_status'] ?? 'In Stock';
    $isFeatured       = isset($_POST['is_featured']) ? 1 : 0;
    $isSpotlight      = isset($_POST['is_spotlight']) ? 1 : 0;
    $isActive         = isset($_POST['is_active']) ? 1 : 0;
    $sortOrder        = (int)($_POST['sort_order'] ?? 0);

    // Handle main image upload or URL
    $uploadedImage = handleImageUpload('main_image_file', 'prod');
    $mainImage = $uploadedImage ?: trim($_POST['main_image_url'] ?? $product['main_image']);
    if ($mainImage === '') {
        $mainImage = 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=900&q=85';
    }

    // Parse Gallery URLs (one per line)
    $galleryLines = preg_split('/\r\n|\r|\n/', trim($_POST['gallery_urls'] ?? ''));
    $galleryClean = array_values(array_filter(array_map('trim', $galleryLines)));
    if (empty($galleryClean)) {
        $galleryClean = [$mainImage];
    }
    $galleryJson = json_encode($galleryClean);

    // Parse Feature Bullet Points (one per line)
    $featureLines = preg_split('/\r\n|\r|\n/', trim($_POST['features_lines'] ?? ''));
    $featureClean = array_values(array_filter(array_map('trim', $featureLines)));
    $featuresJson = json_encode($featureClean);

    $slug = slugify($name);
    // Ensure unique slug
    $slugCheckStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE slug = ? AND id != ?");
    $slugCheckStmt->execute([$slug, $id]);
    if ((int)$slugCheckStmt->fetchColumn() > 0) {
        $slug .= '-' . rand(100, 999);
    }

    if ($isSpotlight) {
        $pdo->exec("UPDATE products SET is_spotlight = 0");
    }

    if ($isEdit) {
        $sql = "UPDATE products SET
            category_id = ?, name = ?, slug = ?, subtitle = ?, short_description = ?, full_description = ?,
            price = ?, compare_at_price = ?, discount_badge = ?, main_image = ?, gallery_images = ?,
            features_json = ?, external_buy_url = ?, buy_button_text = ?, platform_label = ?,
            open_in_new_tab = ?, rating = ?, reviews_count = ?, stock_status = ?,
            is_featured = ?, is_spotlight = ?, is_active = ?, sort_order = ?
            WHERE id = ?";
        $pdo->prepare($sql)->execute([
            $categoryId, $name, $slug, $subtitle, $shortDesc, $fullDesc,
            $price, $comparePrice, $discountBadge, $mainImage, $galleryJson,
            $featuresJson, $externalBuyUrl, $buyButtonText, $platformLabel,
            $openInNewTab, $rating, $reviewsCount, $stockStatus,
            $isFeatured, $isSpotlight, $isActive, $sortOrder, $id
        ]);
        setFlashMessage('success', 'Product & External Buy Link updated successfully!');
    } else {
        $sql = "INSERT INTO products (
            category_id, name, slug, subtitle, short_description, full_description,
            price, compare_at_price, discount_badge, main_image, gallery_images,
            features_json, external_buy_url, buy_button_text, platform_label,
            open_in_new_tab, rating, reviews_count, stock_status,
            is_featured, is_spotlight, is_active, sort_order
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([
            $categoryId, $name, $slug, $subtitle, $shortDesc, $fullDesc,
            $price, $comparePrice, $discountBadge, $mainImage, $galleryJson,
            $featuresJson, $externalBuyUrl, $buyButtonText, $platformLabel,
            $openInNewTab, $rating, $reviewsCount, $stockStatus,
            $isFeatured, $isSpotlight, $isActive, $sortOrder
        ]);
        setFlashMessage('success', 'New product added to landing page!');
    }

    header('Location: products');
    exit;
}

$galleryArray  = json_decode($product['gallery_images'] ?: '[]', true) ?: [];
$featuresArray = json_decode($product['features_json'] ?: '[]', true) ?: [];

$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC")->fetchAll();
$csrfToken  = generateCsrfToken();

$pageTitle = $isEdit ? 'Edit Product & Buy Link' : 'Add New Product & Buy Link';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6">
          <h1 class="mb-0 fs-3"><?= e($pageTitle) ?></h1>
        </div>
        <div class="col-sm-6 text-sm-end">
          <a href="products" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Products
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>" />

        <div class="row">
          <!-- Left Column: Product Details & External Link -->
          <div class="col-lg-8">
            <!-- External Buy Link Card (Primary Focus) -->
            <div class="card card-primary card-outline mb-4">
              <div class="card-header">
                <h3 class="card-title fw-bold">
                  <i class="bi bi-link-45deg me-1 text-primary"></i>
                  External "Buy Now" Website or Payment Link Configuration
                </h3>
              </div>
              <div class="card-body">
                <div class="mb-3">
                  <label class="form-label fw-semibold">External Checkout / Payment Link / Store URL <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-globe2"></i></span>
                    <input
                      type="url"
                      name="external_buy_url"
                      class="form-control form-control-lg"
                      placeholder="https://buy.stripe.com/... or https://rzp.io/l/... or https://amazon.com/dp/..."
                      value="<?= e($product['external_buy_url']) ?>"
                      required
                    />
                  </div>
                  <div class="form-text">
                    When visitors click <strong>Buy Now</strong> on the landing page, they will be tracked and redirected directly to this external URL.
                  </div>
                </div>

                <div class="row g-3">
                  <div class="col-md-5">
                    <label class="form-label fw-semibold">Buy Button Label</label>
                    <input
                      type="text"
                      name="buy_button_text"
                      class="form-control"
                      placeholder="e.g. Buy Now — ₹2,499, Buy on Amazon.in, Pay via UPI"
                      value="<?= e($product['buy_button_text']) ?>"
                      required
                    />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label fw-semibold">Platform / Trust Label</label>
                    <input
                      type="text"
                      name="platform_label"
                      class="form-control"
                      placeholder="e.g. Razorpay / UPI, Amazon.in Prime, Flipkart"
                      value="<?= e($product['platform_label']) ?>"
                    />
                  </div>
                  <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check form-switch mb-2">
                      <input
                        class="form-check-input"
                        type="checkbox"
                        id="openNewTab"
                        name="open_in_new_tab"
                        value="1"
                        <?= $product['open_in_new_tab'] ? 'checked' : '' ?>
                      />
                      <label class="form-check-label" for="openNewTab">Open in New Tab</label>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Core Product Information -->
            <div class="card mb-4">
              <div class="card-header">
                <h3 class="card-title">Product Information &amp; Copywriting</h3>
              </div>
              <div class="card-body">
                <div class="row g-3 mb-3">
                  <div class="col-md-8">
                    <label class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
                    <input
                      type="text"
                      name="name"
                      class="form-control"
                      placeholder="e.g. AeroSonic Pro ANC Spatial Headphones"
                      value="<?= e($product['name']) ?>"
                      required
                    />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label fw-semibold">Category</label>
                    <select name="category_id" class="form-select">
                      <option value="">-- Select Category --</option>
                      <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>" <?= (int)$product['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>>
                          <?= e($cat['name']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Tagline / Key Highlight Subtitle</label>
                  <input
                    type="text"
                    name="subtitle"
                    class="form-control"
                    placeholder="e.g. 45dB Hybrid Active Noise Cancellation • 60-Hour Battery • Hi-Res Audio"
                    value="<?= e($product['subtitle']) ?>"
                  />
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Short Card Summary</label>
                  <textarea
                    name="short_description"
                    class="form-control"
                    rows="2"
                    placeholder="Concise 1-2 sentence hook shown on the product card..."
                  ><?= e($product['short_description']) ?></textarea>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Full Detailed Description (Quick-View Modal &amp; Spotlight)</label>
                  <textarea
                    name="full_description"
                    class="form-control"
                    rows="4"
                    placeholder="Detailed persuasive description of the product..."
                  ><?= e($product['full_description']) ?></textarea>
                </div>

                <div class="mb-0">
                  <label class="form-label fw-semibold">Key Specifications / Feature Bullet Points (One per line)</label>
                  <textarea
                    name="features_lines"
                    class="form-control font-monospace fs-7"
                    rows="5"
                    placeholder="45dB Adaptive Hybrid Active Noise Cancellation&#10;Up to 60 Hours Playback on a Single Charge&#10;Dual-Device Multipoint Bluetooth 5.4"
                  ><?= e(implode("\n", $featuresArray)) ?></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column: Pricing, Images & Visibility -->
          <div class="col-lg-4">
            <!-- Pricing & Badges -->
            <div class="card mb-4">
              <div class="card-header">
                <h3 class="card-title">Pricing &amp; Promotional Badge</h3>
              </div>
              <div class="card-body">
                <div class="row g-3 mb-3">
                  <div class="col-6">
                    <label class="form-label fw-semibold">Promo Price</label>
                    <div class="input-group">
                      <span class="input-group-text"><?= e(getSetting('currency_symbol', '₹')) ?></span>
                      <input
                        type="number"
                        step="0.01"
                        name="price"
                        class="form-control"
                        value="<?= e($product['price']) ?>"
                        required
                      />
                    </div>
                  </div>
                  <div class="col-6">
                    <label class="form-label fw-semibold">Compare-At Price</label>
                    <div class="input-group">
                      <span class="input-group-text"><?= e(getSetting('currency_symbol', '₹')) ?></span>
                      <input
                        type="number"
                        step="0.01"
                        name="compare_at_price"
                        class="form-control"
                        value="<?= e($product['compare_at_price']) ?>"
                      />
                    </div>
                  </div>
                </div>

                <div class="row g-3">
                  <div class="col-6">
                    <label class="form-label fw-semibold">Discount / Ribbon Badge</label>
                    <input
                      type="text"
                      name="discount_badge"
                      class="form-control"
                      placeholder="e.g. SAVE 40%, HOT DEAL"
                      value="<?= e($product['discount_badge']) ?>"
                    />
                  </div>
                  <div class="col-6">
                    <label class="form-label fw-semibold">Stock Status</label>
                    <select name="stock_status" class="form-select">
                      <?php foreach (['In Stock', 'Limited Stock', 'Pre-Order', 'Sold Out'] as $status): ?>
                        <option value="<?= e($status) ?>" <?= $product['stock_status'] === $status ? 'selected' : '' ?>>
                          <?= e($status) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <!-- Product Images -->
            <div class="card mb-4">
              <div class="card-header">
                <h3 class="card-title">Product Visuals &amp; Gallery</h3>
              </div>
              <div class="card-body">
                <?php if (!empty($product['main_image'])): ?>
                  <div class="mb-3 text-center">
                    <img
                      src="<?= e(resolveImageUrl($product['main_image'], true)) ?>"
                      alt="Preview"
                      class="img-fluid rounded border"
                      style="max-height: 160px; object-fit: cover;"
                    />
                  </div>
                <?php endif; ?>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Upload Main Image File</label>
                  <input type="file" name="main_image_file" class="form-control" accept="image/*" />
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Or Paste Main Image URL</label>
                  <input
                    type="text"
                    name="main_image_url"
                    class="form-control"
                    placeholder="https://images.unsplash.com/..."
                    value="<?= e($product['main_image']) ?>"
                  />
                </div>

                <div class="mb-0">
                  <label class="form-label fw-semibold">Gallery Image URLs (One per line)</label>
                  <textarea
                    name="gallery_urls"
                    class="form-control fs-7"
                    rows="3"
                    placeholder="https://images.unsplash.com/..."
                  ><?= e(implode("\n", $galleryArray)) ?></textarea>
                </div>
              </div>
            </div>

            <!-- Social Proof & Visibility -->
            <div class="card mb-4">
              <div class="card-header">
                <h3 class="card-title">Social Proof &amp; Display Settings</h3>
              </div>
              <div class="card-body">
                <div class="row g-3 mb-3">
                  <div class="col-4">
                    <label class="form-label fw-semibold">Rating</label>
                    <input type="number" step="0.1" min="1" max="5" name="rating" class="form-control" value="<?= e($product['rating']) ?>" />
                  </div>
                  <div class="col-4">
                    <label class="form-label fw-semibold">Reviews</label>
                    <input type="number" name="reviews_count" class="form-control" value="<?= (int)$product['reviews_count'] ?>" />
                  </div>
                  <div class="col-4">
                    <label class="form-label fw-semibold">Sort Order</label>
                    <input type="number" name="sort_order" class="form-control" value="<?= (int)$product['sort_order'] ?>" />
                  </div>
                </div>

                <div class="form-check form-switch mb-2">
                  <input class="form-check-input" type="checkbox" id="isActive" name="is_active" value="1" <?= $product['is_active'] ? 'checked' : '' ?> />
                  <label class="form-check-label fw-semibold" for="isActive">Visible on Landing Page</label>
                </div>

                <div class="form-check form-switch mb-2">
                  <input class="form-check-input" type="checkbox" id="isFeatured" name="is_featured" value="1" <?= $product['is_featured'] ? 'checked' : '' ?> />
                  <label class="form-check-label" for="isFeatured">Show in Featured Catalog Grid</label>
                </div>

                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="isSpotlight" name="is_spotlight" value="1" <?= $product['is_spotlight'] ? 'checked' : '' ?> />
                  <label class="form-check-label text-warning-emphasis fw-semibold" for="isSpotlight">
                    <i class="bi bi-star-fill text-warning me-1"></i> Set as Flagship Spotlight Product
                  </label>
                </div>
              </div>
              <div class="card-footer d-grid">
                <button type="submit" class="btn btn-primary btn-lg fw-semibold">
                  <i class="bi bi-check2-circle me-1"></i>
                  <?= $isEdit ? 'Save Product & External Link' : 'Create Product & External Link' ?>
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

