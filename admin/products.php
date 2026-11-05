<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';

requireAdminAuth();
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        if ($action === 'delete') {
            $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
            setFlashMessage('success', 'Product removed successfully.');
        } elseif ($action === 'toggle_active') {
            $pdo->prepare("UPDATE products SET is_active = IF(is_active=1, 0, 1) WHERE id = ?")->execute([$id]);
            setFlashMessage('info', 'Product visibility updated.');
        } elseif ($action === 'set_spotlight') {
            $pdo->exec("UPDATE products SET is_spotlight = 0");
            $pdo->prepare("UPDATE products SET is_spotlight = 1 WHERE id = ?")->execute([$id]);
            setFlashMessage('success', 'Flagship Spotlight Product updated on the landing page!');
        }
    }
    header('Location: products');
    exit;
}

$pageTitle = 'Products & Payment Links';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$categoryFilter = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$searchQuery    = trim($_GET['q'] ?? '');

$sql = "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
$params = [];

if ($categoryFilter > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $categoryFilter;
}
if ($searchQuery !== '') {
    $sql .= " AND (p.name LIKE ? OR p.external_buy_url LIKE ? OR p.platform_label LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}
$sql .= " ORDER BY p.sort_order ASC, p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC")->fetchAll();
$flash = getFlashMessage();
$csrfToken = generateCsrfToken();
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6">
          <h1 class="mb-0 fs-3 fw-bold">Products &amp; Payment Links</h1>
        </div>
        <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
          <a href="product-form" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add New Product
          </a>
        </div>
      </div>
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

      <!-- Filter Bar -->
      <div class="card mb-4">
        <div class="card-body py-3">
          <form method="get" class="row g-2 align-items-center">
            <div class="col-md-5">
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input
                  type="text"
                  name="q"
                  class="form-control"
                  placeholder="Search by product name, payment link, or platform..."
                  value="<?= e($searchQuery) ?>"
                />
              </div>
            </div>
            <div class="col-md-4">
              <select name="cat" class="form-select">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= (int)$cat['id'] ?>" <?= $categoryFilter === (int)$cat['id'] ? 'selected' : '' ?>>
                    <?= e($cat['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
              <button type="submit" class="btn btn-outline-primary flex-grow-1">Filter</button>
              <a href="products" class="btn btn-outline-secondary">Reset</a>
            </div>
          </form>
        </div>
      </div>

      <!-- Products Table Card -->
      <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
          <h3 class="card-title fw-semibold mb-0">Promoted Products Catalog (<?= count($products) ?>)</h3>
          <span class="text-secondary fs-7">
            <i class="bi bi-star-fill text-warning me-1"></i> Click the star button to set the Flagship Spotlight product
          </span>
        </div>
        <div class="card-body table-responsive p-0">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th style="width: 60px;">Order</th>
                <th>Product</th>
                <th>Pricing</th>
                <th>External Payment Link &amp; CTA</th>
                <th class="text-center">Clicks</th>
                <th class="text-center">Spotlight</th>
                <th class="text-center">Status</th>
                <th class="text-end" style="width: 135px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($products)): ?>
                <tr>
                  <td colspan="8" class="text-center py-5 text-secondary">
                    No products found. <a href="product-form">Add your first product</a>.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($products as $prod): ?>
                  <tr>
                    <td>
                      <span class="badge text-bg-light border">#<?= (int)$prod['sort_order'] ?></span>
                    </td>
                    <td>
                      <div class="d-flex align-items-center gap-3">
                        <img
                          src="<?= e(resolveImageUrl($prod['main_image'], true)) ?>"
                          alt="<?= e($prod['name']) ?>"
                          class="product-thumb-sm border shadow-sm"
                        />
                        <div>
                          <div class="fw-bold"><?= e($prod['name']) ?></div>
                          <small class="text-secondary">
                            <?= e($prod['category_name'] ?? 'Uncategorized') ?> •
                            <i class="bi bi-star-fill text-warning"></i> <?= e($prod['rating']) ?> (<?= (int)$prod['reviews_count'] ?>)
                          </small>
                        </div>
                      </div>
                    </td>
                    <td class="text-nowrap">
                      <div class="fw-bold fs-6 text-success"><?= formatPrice($prod['price']) ?></div>
                      <?php if ($prod['compare_at_price'] > $prod['price']): ?>
                        <small class="text-secondary text-decoration-line-through"><?= formatPrice($prod['compare_at_price']) ?></small>
                      <?php endif; ?>
                      <?php if (!empty($prod['discount_badge'])): ?>
                        <div><span class="badge text-bg-danger mt-1"><?= e($prod['discount_badge']) ?></span></div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="d-flex flex-wrap gap-1 mb-1">
                        <span class="badge text-bg-primary"><?= e($prod['buy_button_text']) ?></span>
                        <span class="badge text-bg-light border text-dark"><?= e($prod['platform_label'] ?: 'External URL') ?></span>
                      </div>
                      <a
                        href="<?= e($prod['external_buy_url']) ?>"
                        target="_blank"
                        rel="noopener"
                        class="external-url-pill fs-7 text-decoration-none"
                        title="<?= e($prod['external_buy_url']) ?>"
                      >
                        <i class="bi bi-link-45deg"></i><?= e($prod['external_buy_url']) ?>
                      </a>
                    </td>
                    <td class="text-center">
                      <span class="badge rounded-pill text-bg-info px-3"><?= number_format((int)$prod['clicks_count']) ?></span>
                    </td>
                    <td class="text-center">
                      <form method="post" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>" />
                        <input type="hidden" name="action" value="set_spotlight" />
                        <input type="hidden" name="id" value="<?= (int)$prod['id'] ?>" />
                        <button
                          type="submit"
                          class="btn <?= $prod['is_spotlight'] ? 'btn-warning' : 'btn-outline-secondary' ?> action-icon-btn"
                          title="Set as Flagship Spotlight Product"
                        >
                          <i class="bi <?= $prod['is_spotlight'] ? 'bi-star-fill' : 'bi-star' ?>"></i>
                        </button>
                      </form>
                    </td>
                    <td class="text-center">
                      <form method="post" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>" />
                        <input type="hidden" name="action" value="toggle_active" />
                        <input type="hidden" name="id" value="<?= (int)$prod['id'] ?>" />
                        <button
                          type="submit"
                          class="btn btn-sm rounded-pill px-3 <?= $prod['is_active'] ? 'btn-success' : 'btn-secondary' ?>"
                        >
                          <?= $prod['is_active'] ? 'Active' : 'Hidden' ?>
                        </button>
                      </form>
                    </td>
                    <td class="text-end">
                      <div class="action-btn-group">
                        <a
                          href="../buy/<?= e($prod['slug']) ?>?src=card"
                          target="_blank"
                          class="btn btn-outline-success action-icon-btn"
                          title="Test External Buy Link"
                        >
                          <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                        <a
                          href="product-form?id=<?= (int)$prod['id'] ?>"
                          class="btn btn-outline-primary action-icon-btn"
                          title="Edit Product"
                        >
                          <i class="bi bi-pencil"></i>
                        </a>
                        <form method="post" class="d-inline m-0" onsubmit="return confirm('Delete this product permanently?');">
                          <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>" />
                          <input type="hidden" name="action" value="delete" />
                          <input type="hidden" name="id" value="<?= (int)$prod['id'] ?>" />
                          <button type="submit" class="btn btn-outline-danger action-icon-btn" title="Delete Product">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
