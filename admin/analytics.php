<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';

requireAdminAuth();
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    if (($_POST['action'] ?? '') === 'clear_logs') {
        $pdo->exec("TRUNCATE TABLE click_logs");
        setFlashMessage('info', 'Click logs cleared.');
        header('Location: analytics');
        exit;
    }
}

$filterProduct   = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$filterPlacement = trim($_GET['placement'] ?? '');

$sql = "
    SELECT l.*, p.name AS product_name, p.platform_label
    FROM click_logs l
    LEFT JOIN products p ON l.product_id = p.id
    WHERE 1=1
";
$params = [];
if ($filterProduct > 0) {
    $sql .= " AND l.product_id = ?";
    $params[] = $filterProduct;
}
if ($filterPlacement !== '') {
    $sql .= " AND l.placement = ?";
    $params[] = $filterPlacement;
}
$sql .= " ORDER BY l.id DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Product conversion leaderboard
$leaderboard = $pdo->query("
    SELECT p.id, p.name, p.price, p.platform_label, p.external_buy_url, p.clicks_count,
           COUNT(l.id) AS logged_clicks
    FROM products p
    LEFT JOIN click_logs l ON l.product_id = p.id
    GROUP BY p.id
    ORDER BY p.clicks_count DESC
")->fetchAll();

$allProducts = $pdo->query("SELECT id, name FROM products ORDER BY name ASC")->fetchAll();
$pageTitle = 'Buy Now Outbound Click Analytics';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
$flash = getFlashMessage();
$csrfToken = generateCsrfToken();
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6">
          <h1 class="mb-0 fs-3">Outbound "Buy Now" Click Analytics</h1>
        </div>
        <div class="col-sm-6 text-sm-end">
          <form method="post" class="d-inline" onsubmit="return confirm('Clear recent click log history?');">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>" />
            <input type="hidden" name="action" value="clear_logs" />
            <button type="submit" class="btn btn-sm btn-outline-danger">
              <i class="bi bi-trash me-1"></i> Clear Click History
            </button>
          </form>
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

      <!-- Product Performance Leaderboard -->
      <div class="card mb-4">
        <div class="card-header">
          <h3 class="card-title"><i class="bi bi-trophy-fill text-warning me-2"></i>Product External Link Leaderboard</h3>
        </div>
        <div class="card-body table-responsive p-0">
          <table class="table table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>Product</th>
                <th>Platform</th>
                <th>Destination Payment / Product Link</th>
                <th class="text-center">Total Buy Clicks</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($leaderboard as $lb): ?>
                <tr>
                  <td class="fw-bold"><?= e($lb['name']) ?> (<?= formatPrice($lb['price']) ?>)</td>
                  <td><span class="badge text-bg-secondary"><?= e($lb['platform_label']) ?></span></td>
                  <td>
                    <a href="<?= e($lb['external_buy_url']) ?>" target="_blank" class="text-decoration-none fs-7">
                      <i class="bi bi-box-arrow-up-right me-1"></i><?= e($lb['external_buy_url']) ?>
                    </a>
                  </td>
                  <td class="text-center">
                    <span class="badge text-bg-primary fs-6"><?= number_format((int)$lb['clicks_count']) ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Detailed Outbound Click Logs -->
      <div class="card">
        <div class="card-header">
          <div class="row align-items-center g-2">
            <div class="col-md-4">
              <h3 class="card-title mb-0">Recent Outbound Click Events (Last 100)</h3>
            </div>
            <div class="col-md-8">
              <form method="get" class="d-flex gap-2 justify-content-md-end">
                <select name="product_id" class="form-select form-select-sm" style="max-width: 220px;">
                  <option value="0">All Products</option>
                  <?php foreach ($allProducts as $ap): ?>
                    <option value="<?= (int)$ap['id'] ?>" <?= $filterProduct === (int)$ap['id'] ? 'selected' : '' ?>>
                      <?= e($ap['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <select name="placement" class="form-select form-select-sm" style="max-width: 170px;">
                  <option value="">All Placements</option>
                  <?php foreach (['hero', 'spotlight', 'card', 'modal', 'sticky_bar'] as $pl): ?>
                    <option value="<?= $pl ?>" <?= $filterPlacement === $pl ? 'selected' : '' ?>>
                      <?= ucfirst(str_replace('_', ' ', $pl)) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                <a href="analytics" class="btn btn-sm btn-outline-secondary">Reset</a>
              </form>
            </div>
          </div>
        </div>
        <div class="card-body table-responsive p-0">
          <table class="table table-hover align-middle mb-0 fs-7">
            <thead>
              <tr>
                <th>Time</th>
                <th>Product</th>
                <th>Button Placement</th>
                <th>Redirected External URL</th>
                <th>Visitor IP</th>
                <th>Referrer</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($logs)): ?>
                <tr>
                  <td colspan="6" class="text-center py-4 text-secondary">No click events match your filter.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($logs as $log): ?>
                  <tr>
                    <td class="text-nowrap"><?= date('M j, Y H:i:s', strtotime($log['clicked_at'])) ?></td>
                    <td class="fw-semibold"><?= e($log['product_name'] ?: 'Hero CTA') ?></td>
                    <td><span class="badge text-bg-info"><?= e($log['placement']) ?></span></td>
                    <td>
                      <a href="<?= e($log['target_url']) ?>" target="_blank" class="external-url-pill text-decoration-none">
                        <?= e($log['target_url']) ?>
                      </a>
                    </td>
                    <td><code><?= e($log['ip_address']) ?></code></td>
                    <td class="text-truncate" style="max-width:160px;"><?= e($log['referrer']) ?></td>
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

