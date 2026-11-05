<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';

requireAdminAuth();
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $keys = [
        'site_name', 'legal_entity_name', 'canonical_domain', 'site_tagline', 'logo_text', 'currency_symbol',
        'primary_color', 'accent_color', 'announcement_text', 'countdown_end',
        'whatsapp_number', 'contact_email', 'contact_phone', 'contact_address',
        'facebook_url', 'instagram_url', 'twitter_url', 'youtube_url',
        'meta_title', 'meta_description', 'footer_disclaimer', 'custom_head_scripts'
    ];

    foreach ($keys as $k) {
        if (isset($_POST[$k])) {
            updateSiteSetting($k, trim($_POST[$k]));
        }
    }

    updateSiteSetting('announcement_enabled', isset($_POST['announcement_enabled']) ? '1' : '0');
    updateSiteSetting('sticky_bar_enabled', isset($_POST['sticky_bar_enabled']) ? '1' : '0');

    setFlashMessage('success', 'Site & Landing Page configuration saved successfully!');
    header('Location: settings');
    exit;
}

$settings = getSiteSettings(true);
$pageTitle = 'Site, Branding & Conversion Settings';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
$flash = getFlashMessage();
$csrfToken = generateCsrfToken();
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <h1 class="mb-0 fs-3">Site, Theme &amp; Conversion Bar Settings</h1>
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

      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>" />

        <div class="row">
          <div class="col-lg-6">
            <!-- Branding & Theme -->
            <div class="card card-primary card-outline mb-4">
              <div class="card-header">
                <h3 class="card-title"><i class="bi bi-palette-fill me-1 text-primary"></i> Brand Identity &amp; Dynamic Theme Colors</h3>
              </div>
              <div class="card-body">
                <div class="row g-3 mb-3">
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">Brand / Store Name</label>
                    <input type="text" name="site_name" class="form-control" value="<?= e($settings['site_name'] ?? 'NovaPulse') ?>" required />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">Currency Symbol</label>
                    <input type="text" name="currency_symbol" class="form-control" value="<?= e($settings['currency_symbol'] ?? '₹') ?>" placeholder="₹, $, €, £" required />
                  </div>
                </div>

                <div class="row g-3 mb-3">
                  <div class="col-md-7">
                    <label class="form-label fw-semibold">Legal Entity Name (GEO Disambiguation)</label>
                    <input type="text" name="legal_entity_name" class="form-control" value="<?= e($settings['legal_entity_name'] ?? 'NovaPulse Technologies India Pvt. Ltd.') ?>" />
                  </div>
                  <div class="col-md-5">
                    <label class="form-label fw-semibold">Canonical Domain</label>
                    <input type="text" name="canonical_domain" class="form-control" value="<?= e($settings['canonical_domain'] ?? 'novapulse.in') ?>" />
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Store Tagline</label>
                  <input type="text" name="site_tagline" class="form-control" value="<?= e($settings['site_tagline'] ?? '') ?>" />
                </div>

                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">Primary Brand Color</label>
                    <input type="color" name="primary_color" class="form-control form-control-color w-100" value="<?= e($settings['primary_color'] ?? '#4f46e5') ?>" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">CTA Accent Color</label>
                    <input type="color" name="accent_color" class="form-control form-control-color w-100" value="<?= e($settings['accent_color'] ?? '#f97316') ?>" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Conversion Bars & Countdown -->
            <div class="card mb-4">
              <div class="card-header">
                <h3 class="card-title"><i class="bi bi-lightning-fill me-1 text-warning"></i> Urgency Promo Bar &amp; Sticky Buy Bar</h3>
              </div>
              <div class="card-body">
                <div class="form-check form-switch mb-3">
                  <input class="form-check-input" type="checkbox" name="announcement_enabled" id="annEnabled" value="1" <?= ($settings['announcement_enabled'] ?? '1') === '1' ? 'checked' : '' ?> />
                  <label class="form-check-label fw-semibold" for="annEnabled">Enable Top Promotional Announcement Bar</label>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Announcement Bar Text</label>
                  <input type="text" name="announcement_text" class="form-control" value="<?= e($settings['announcement_text'] ?? '') ?>" />
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold">Flash Offer Countdown End Date/Time</label>
                  <input type="text" name="countdown_end" class="form-control" value="<?= e($settings['countdown_end'] ?? '') ?>" placeholder="YYYY-MM-DD HH:MM:SS" />
                </div>

                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" name="sticky_bar_enabled" id="stickyEnabled" value="1" <?= ($settings['sticky_bar_enabled'] ?? '1') === '1' ? 'checked' : '' ?> />
                  <label class="form-check-label fw-semibold" for="stickyEnabled">Enable Bottom Sticky "Buy Now" Conversion Bar on Scroll</label>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-6">
            <!-- Contact, WhatsApp & Social -->
            <div class="card mb-4">
              <div class="card-header">
                <h3 class="card-title"><i class="bi bi-whatsapp me-1 text-success"></i> Contact, WhatsApp &amp; Social Channels</h3>
              </div>
              <div class="card-body">
                <div class="row g-3 mb-3">
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">WhatsApp Number (Digits Only)</label>
                    <input type="text" name="whatsapp_number" class="form-control" value="<?= e($settings['whatsapp_number'] ?? '') ?>" placeholder="15552345678" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">Support Phone</label>
                    <input type="text" name="contact_phone" class="form-control" value="<?= e($settings['contact_phone'] ?? '') ?>" />
                  </div>
                </div>

                <div class="row g-3 mb-3">
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">Support Email</label>
                    <input type="email" name="contact_email" class="form-control" value="<?= e($settings['contact_email'] ?? '') ?>" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">Business Address</label>
                    <input type="text" name="contact_address" class="form-control" value="<?= e($settings['contact_address'] ?? '') ?>" />
                  </div>
                </div>

                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">Instagram URL</label>
                    <input type="url" name="instagram_url" class="form-control" value="<?= e($settings['instagram_url'] ?? '') ?>" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">Facebook URL</label>
                    <input type="url" name="facebook_url" class="form-control" value="<?= e($settings['facebook_url'] ?? '') ?>" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">X / Twitter URL</label>
                    <input type="url" name="twitter_url" class="form-control" value="<?= e($settings['twitter_url'] ?? '') ?>" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-semibold">YouTube URL</label>
                    <input type="url" name="youtube_url" class="form-control" value="<?= e($settings['youtube_url'] ?? '') ?>" />
                  </div>
                </div>
              </div>
            </div>

            <!-- SEO & Tracking Pixels -->
            <div class="card mb-4">
              <div class="card-header">
                <h3 class="card-title"><i class="bi bi-search me-1 text-info"></i> SEO Meta &amp; External Partner Disclaimer</h3>
              </div>
              <div class="card-body">
                <div class="mb-3">
                  <label class="form-label fw-semibold">SEO Meta Title</label>
                  <input type="text" name="meta_title" class="form-control" value="<?= e($settings['meta_title'] ?? '') ?>" />
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">SEO Meta Description</label>
                  <textarea name="meta_description" class="form-control" rows="2"><?= e($settings['meta_description'] ?? '') ?></textarea>
                </div>
                <div class="mb-0">
                  <label class="form-label fw-semibold">Footer External Checkout / Affiliate Disclosure</label>
                  <textarea name="footer_disclaimer" class="form-control" rows="2"><?= e($settings['footer_disclaimer'] ?? '') ?></textarea>
                </div>
              </div>
              <div class="card-footer d-grid">
                <button type="submit" class="btn btn-primary btn-lg">
                  <i class="bi bi-save me-1"></i> Save All Site Settings
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

