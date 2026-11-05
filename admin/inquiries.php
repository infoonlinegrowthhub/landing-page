<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';

requireAdminAuth();
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        if ($action === 'mark_read') {
            $pdo->prepare("UPDATE inquiries SET status = 'Read' WHERE id = ?")->execute([$id]);
            setFlashMessage('success', 'Inquiry marked as read.');
        } elseif ($action === 'mark_replied') {
            $pdo->prepare("UPDATE inquiries SET status = 'Replied' WHERE id = ?")->execute([$id]);
            setFlashMessage('success', 'Inquiry marked as replied.');
        } elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM inquiries WHERE id = ?")->execute([$id]);
            setFlashMessage('info', 'Inquiry deleted.');
        }
    }
    header('Location: inquiries');
    exit;
}

if (isset($_GET['view'])) {
    $pdo->prepare("UPDATE inquiries SET status = 'Read' WHERE id = ? AND status = 'Unread'")->execute([(int)$_GET['view']]);
}

$inquiries = $pdo->query("SELECT * FROM inquiries ORDER BY id DESC")->fetchAll();
$pageTitle = 'Customer Pre-Sale Inquiries';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
$flash = getFlashMessage();
$csrfToken = generateCsrfToken();
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <h1 class="mb-0 fs-3">Customer Pre-Sale Inquiries &amp; Leads</h1>
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

      <div class="card">
        <div class="card-header">
          <h3 class="card-title">All Customer Messages (<?= count($inquiries) ?>)</h3>
        </div>
        <div class="card-body table-responsive p-0">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Date</th>
                <th>Customer</th>
                <th>Product Interest &amp; Message</th>
                <th class="text-center">Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($inquiries)): ?>
                <tr>
                  <td colspan="5" class="text-center py-5 text-secondary">No customer inquiries received yet.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($inquiries as $inq): ?>
                  <tr class="<?= $inq['status'] === 'Unread' ? 'fw-semibold' : '' ?>">
                    <td class="text-nowrap fs-7"><?= date('M j, Y g:i A', strtotime($inq['created_at'])) ?></td>
                    <td>
                      <div><?= e($inq['name']) ?></div>
                      <small class="text-secondary">
                        <a href="mailto:<?= e($inq['email']) ?>"><?= e($inq['email']) ?></a>
                        <?= $inq['phone'] ? ' • ' . e($inq['phone']) : '' ?>
                      </small>
                    </td>
                    <td>
                      <?php if ($inq['product_interest']): ?>
                        <span class="badge text-bg-primary mb-1"><?= e($inq['product_interest']) ?></span>
                      <?php endif; ?>
                      <div><?= e($inq['subject']) ?></div>
                      <div class="fs-7 text-secondary fw-normal"><?= nl2br(e($inq['message'])) ?></div>
                    </td>
                    <td class="text-center">
                      <?php
                        $badgeClass = match ($inq['status']) {
                            'Unread'  => 'text-bg-danger',
                            'Replied' => 'text-bg-success',
                            default   => 'text-bg-secondary'
                        };
                      ?>
                      <span class="badge <?= $badgeClass ?>"><?= e($inq['status']) ?></span>
                    </td>
                    <td class="text-end text-nowrap">
                      <div class="action-btn-group">
                        <a href="mailto:<?= e($inq['email']) ?>?subject=Re: <?= urlencode($inq['subject']) ?>" class="btn btn-outline-primary action-icon-btn" title="Reply via Email">
                          <i class="bi bi-reply-fill"></i>
                        </a>
                        <?php if ($inq['status'] === 'Unread'): ?>
                          <form method="post" class="d-inline m-0">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>" />
                            <input type="hidden" name="action" value="mark_read" />
                            <input type="hidden" name="id" value="<?= (int)$inq['id'] ?>" />
                            <button type="submit" class="btn btn-outline-success action-icon-btn" title="Mark Read">
                              <i class="bi bi-check2"></i>
                            </button>
                          </form>
                        <?php endif; ?>
                        <form method="post" class="d-inline m-0" onsubmit="return confirm('Delete this inquiry?');">
                          <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>" />
                          <input type="hidden" name="action" value="delete" />
                          <input type="hidden" name="id" value="<?= (int)$inq['id'] ?>" />
                          <button type="submit" class="btn btn-outline-danger action-icon-btn" title="Delete">
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

