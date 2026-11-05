<?php
$pageTitle = 'Store Dashboard';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

// Fetch KPI metrics
$totalProducts   = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$activeProducts  = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();
$totalClicksLog  = (int)$pdo->query("SELECT COUNT(*) FROM click_logs")->fetchColumn();
$totalClicksProd = (int)$pdo->query("SELECT COALESCE(SUM(clicks_count), 0) FROM products")->fetchColumn();
$totalClicks     = max($totalClicksLog, $totalClicksProd);
$todayClicks     = (int)$pdo->query("SELECT COUNT(*) FROM click_logs WHERE DATE(clicked_at) = CURDATE()")->fetchColumn();
$totalInquiries  = (int)$pdo->query("SELECT COUNT(*) FROM inquiries")->fetchColumn();

// 7-day click trend data
$chartLabels = [];
$chartData   = [];
for ($i = 6; $i >= 0; $i--) {
    $dateStr = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('M j', strtotime($dateStr));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM click_logs WHERE DATE(clicked_at) = ?");
    $stmt->execute([$dateStr]);
    $chartData[] = (int)$stmt->fetchColumn();
}

// Clicks by placement breakdown
$placementRows = $pdo->query("
    SELECT placement, COUNT(*) AS cnt
    FROM click_logs
    GROUP BY placement
    ORDER BY cnt DESC
")->fetchAll();
$placementLabels = [];
$placementCounts = [];
foreach ($placementRows as $pr) {
    $placementLabels[] = ucfirst(str_replace('_', ' ', $pr['placement']));
    $placementCounts[] = (int)$pr['cnt'];
}

// Top products
$topProducts = $pdo->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.clicks_count DESC, p.sort_order ASC
    LIMIT 6
")->fetchAll();

// Recent click logs
$recentClicks = $pdo->query("
    SELECT l.*, p.name AS product_name
    FROM click_logs l
    LEFT JOIN products p ON l.product_id = p.id
    ORDER BY l.id DESC
    LIMIT 5
")->fetchAll();

$flash = getFlashMessage();
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6">
          <h1 class="mb-0 fs-3 fw-bold">Store Overview</h1>
        </div>
        <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
          <a href="product-form" class="btn btn-primary btn-sm px-3">
            <i class="bi bi-plus-lg me-1"></i> Add Product
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="app-content">
    <div class="container-fluid">
      <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
          <?= e($flash['message']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <!-- Clean 4-Column KPI Row -->
      <div class="row g-3 mb-4">
        <div class="col-lg-3 col-sm-6">
          <div class="small-box text-bg-primary mb-0">
            <div class="inner">
              <h3><?= number_format($totalClicks) ?></h3>
              <p>Total Buy Clicks</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M2.25 2.25a.75.75 0 000 1.5h1.386c.17 0 .318.114.362.278l2.558 9.592a3.752 3.752 0 00-2.806 3.63c0 .414.336.75.75.75h15.75a.75.75 0 000-1.5H5.378A2.25 2.25 0 017.5 15h11.218a.75.75 0 00.674-.421 60.358 60.358 0 002.96-7.228.75.75 0 00-.525-.965A60.864 60.864 0 005.68 4.509l-.232-.867A1.875 1.875 0 003.636 2.25H2.25zM3.75 20.25a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0zM16.5 20.25a1.5 1.5 0 113 0 1.5 1.5 0 01-3 0z"></path>
            </svg>
            <a href="analytics" class="small-box-footer link-light link-underline-opacity-0">
              Analytics <i class="bi bi-arrow-right-short"></i>
            </a>
          </div>
        </div>

        <div class="col-lg-3 col-sm-6">
          <div class="small-box text-bg-success mb-0">
            <div class="inner">
              <h3><?= $activeProducts ?></h3>
              <p>Active Products</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.036-.84-1.875-1.875-1.875h-.75zM9.75 8.625c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-.75a1.875 1.875 0 01-1.875-1.875V8.625zM3 13.125c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v6.75c0 1.035-.84 1.875-1.875 1.875h-.75A1.875 1.875 0 013 19.875v-6.75z"></path>
            </svg>
            <a href="products" class="small-box-footer link-light link-underline-opacity-0">
              Manage Catalog <i class="bi bi-arrow-right-short"></i>
            </a>
          </div>
        </div>

        <div class="col-lg-3 col-sm-6">
          <div class="small-box text-bg-warning mb-0">
            <div class="inner">
              <h3><?= $todayClicks ?></h3>
              <p>Today's Clicks</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path clip-rule="evenodd" fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z"></path>
            </svg>
            <a href="hero" class="small-box-footer link-dark link-underline-opacity-0">
              Edit Hero CTA <i class="bi bi-arrow-right-short"></i>
            </a>
          </div>
        </div>

        <div class="col-lg-3 col-sm-6">
          <div class="small-box text-bg-danger mb-0">
            <div class="inner">
              <h3><?= $totalInquiries ?></h3>
              <p>Customer Leads (<?= $unreadInquiriesCount ?> New)</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path d="M1.5 8.67v8.58a3 3 0 003 3h15a3 3 0 003-3V8.67l-8.928 5.493a3 3 0 01-3.144 0L1.5 8.67z"></path>
              <path d="M22.5 6.908V6.75a3 3 0 00-3-3h-15a3 3 0 00-3 3v.158l9.714 5.978a1.5 1.5 0 001.572 0L22.5 6.908z"></path>
            </svg>
            <a href="inquiries" class="small-box-footer link-light link-underline-opacity-0">
              View Leads <i class="bi bi-arrow-right-short"></i>
            </a>
          </div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="row g-3 mb-4">
        <div class="col-lg-8">
          <div class="card h-100 mb-0">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h3 class="card-title fw-semibold">
                <i class="bi bi-graph-up me-1 text-primary"></i>
                7-Day Clicks Trend
              </h3>
              <a href="analytics" class="btn btn-sm btn-outline-secondary">Full Log</a>
            </div>
            <div class="card-body">
              <div class="position-relative" style="height: 260px">
                <canvas id="clicksTrendChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card h-100 mb-0">
            <div class="card-header">
              <h3 class="card-title fw-semibold">
                <i class="bi bi-pie-chart-fill me-1 text-success"></i>
                Clicks by Section
              </h3>
            </div>
            <div class="card-body">
              <div class="position-relative" style="height: 260px">
                <canvas id="placementChart"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Top Products & Live Click Stream -->
      <div class="row g-3">
        <div class="col-lg-8">
          <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h3 class="card-title fw-semibold">
                <i class="bi bi-bag-check-fill me-1 text-primary"></i>
                Top Performing Products
              </h3>
              <a href="products" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body table-responsive p-0">
              <table class="table table-hover align-middle mb-0">
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Platform</th>
                    <th class="text-center">Clicks</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($topProducts as $prod): ?>
                    <tr>
                      <td>
                        <div class="d-flex align-items-center gap-2">
                          <img
                            src="<?= e(resolveImageUrl($prod['main_image'], true)) ?>"
                            alt="<?= e($prod['name']) ?>"
                            class="product-thumb-sm border"
                          />
                          <div>
                            <div class="fw-semibold text-body">
                              <?= e($prod['name']) ?>
                              <?php if ($prod['is_spotlight']): ?>
                                <span class="badge text-bg-warning ms-1">Spotlight</span>
                              <?php endif; ?>
                            </div>
                            <small class="text-secondary"><?= e($prod['category_name'] ?? 'General') ?></small>
                          </div>
                        </div>
                      </td>
                      <td class="text-nowrap">
                        <span class="fw-bold"><?= formatPrice($prod['price']) ?></span>
                      </td>
                      <td>
                        <span class="badge text-bg-light border text-dark"><?= e($prod['platform_label'] ?: 'External Link') ?></span>
                      </td>
                      <td class="text-center">
                        <span class="badge rounded-pill text-bg-primary px-3"><?= number_format((int)$prod['clicks_count']) ?></span>
                      </td>
                      <td class="text-end">
                        <div class="action-btn-group">
                          <a href="product-form?id=<?= (int)$prod['id'] ?>" class="btn btn-outline-primary action-icon-btn" title="Edit Product">
                            <i class="bi bi-pencil"></i>
                          </a>
                          <a href="../buy/<?= e($prod['slug']) ?>?src=card" target="_blank" class="btn btn-outline-success action-icon-btn" title="Test External Link">
                            <i class="bi bi-box-arrow-up-right"></i>
                          </a>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card mb-4">
            <div class="card-header">
              <h3 class="card-title fw-semibold">
                <i class="bi bi-lightning-charge-fill me-1 text-warning"></i>
                Recent Clicks
              </h3>
            </div>
            <div class="card-body p-0">
              <ul class="list-group list-group-flush">
                <?php if (empty($recentClicks)): ?>
                  <li class="list-group-item text-center text-secondary py-4">No clicks logged yet.</li>
                <?php else: ?>
                  <?php foreach ($recentClicks as $rc): ?>
                    <li class="list-group-item py-3">
                      <div class="d-flex justify-content-between align-items-center">
                        <div class="text-truncate me-2">
                          <div class="fw-semibold text-truncate"><?= e($rc['product_name'] ?: 'Hero Banner') ?></div>
                          <small class="text-secondary">
                            <span class="badge text-bg-light border text-dark"><?= e(ucfirst(str_replace('_', ' ', $rc['placement']))) ?></span>
                          </small>
                        </div>
                        <small class="text-muted text-nowrap">
                          <?= date('M j, H:i', strtotime($rc['clicked_at'])) ?>
                        </small>
                      </div>
                    </li>
                  <?php endforeach; ?>
                <?php endif; ?>
              </ul>
            </div>
            <div class="card-footer text-center">
              <a href="analytics" class="text-decoration-none fs-7 fw-semibold">View Full Analytics &rarr;</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Chart.js (Direct Pinned CDN) -->
<script
  src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"
  crossorigin="anonymous"
  referrerpolicy="no-referrer"
></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    try {
      const trendCtx = document.getElementById('clicksTrendChart');
      if (trendCtx && typeof Chart !== 'undefined') {
        new Chart(trendCtx, {
          type: 'line',
          data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
              label: 'Buy Clicks',
              data: <?= json_encode($chartData) ?>,
              borderColor: '#4f46e5',
              backgroundColor: 'rgba(79, 70, 229, 0.14)',
              pointBackgroundColor: '#4f46e5',
              pointRadius: 4,
              fill: true,
              tension: 0.35
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
              y: { beginAtZero: true, ticks: { precision: 0 } }
            }
          }
        });
      }

      const placementCtx = document.getElementById('placementChart');
      if (placementCtx && typeof Chart !== 'undefined') {
        new Chart(placementCtx, {
          type: 'doughnut',
          data: {
            labels: <?= json_encode($placementLabels ?: ['Card', 'Hero', 'Spotlight']) ?>,
            datasets: [{
              data: <?= json_encode($placementCounts ?: [10, 5, 3]) ?>,
              backgroundColor: ['#4f46e5', '#10b981', '#f59e0b', '#ec4899', '#06b6d4']
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { position: 'bottom' }
            }
          }
        });
      }
    } catch (e) {}
  });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
