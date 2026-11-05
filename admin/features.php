<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';

requireAdminAuth();
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id          = (int)($_POST['id'] ?? 0);
        $icon        = trim($_POST['icon'] ?? 'bi-shield-check');
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $badge       = trim($_POST['badge'] ?? '');
        $sortOrder   = (int)($_POST['sort_order'] ?? 0);
        $isActive    = isset($_POST['is_active']) ? 1 : 0;

        if ($title !== '') {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE features SET icon=?, title=?, description=?, badge=?, sort_order=?, is_active=? WHERE id=?");
                $stmt->execute([$icon, $title, $description, $badge, $sortOrder, $isActive, $id]);
                setFlashMessage('success', 'Feature card updated.');
            } else {
                $stmt = $pdo->prepare("INSERT INTO features (icon, title, description, badge, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$icon, $title, $description, $badge, $sortOrder, $isActive]);
                setFlashMessage('success', 'Feature card added.');
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM features WHERE id = ?")->execute([$id]);
        setFlashMessage('info', 'Feature card removed.');
    }
    header('Location: features');
    exit;
}

$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM features WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editItem = $stmt->fetch();
}

$features = $pdo->query("SELECT * FROM features ORDER BY sort_order ASC, id ASC")->fetchAll();
$pageTitle = 'Features & Trust Benefits';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
$flash = getFlashMessage();
$csrfToken = generateCsrfToken();
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <h1 class="mb-0 fs-3">Features &amp; Trust Benefits Section</h1>
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

      <div class="row">
        <div class="col-lg-4">
          <div class="card card-primary card-outline mb-4">
            <div class="card-header">
              <h3 class="card-title"><?= $editItem ? 'Edit Benefit Card' : 'Add Benefit Card' ?></h3>
            </div>
            <form method="post">
              <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>" />
              <input type="hidden" name="action" value="save" />
              <input type="hidden" name="id" value="<?= (int)($editItem['id'] ?? 0) ?>" />
              <div class="card-body">
                <div class="mb-3">
                  <label class="form-label fw-semibold">Title</label>
                  <input type="text" name="title" class="form-control" value="<?= e($editItem['title'] ?? '') ?>" required />
                </div>
                <div class="row g-2 mb-3">
                  <div class="col-7">
                    <label class="form-label fw-semibold">Bootstrap Icon</label>
                    <input type="text" name="icon" class="form-control" value="<?= e($editItem['icon'] ?? 'bi-shield-check') ?>" placeholder="bi-shield-check" />
                  </div>
                  <div class="col-5">
                    <label class="form-label fw-semibold">Mini Badge</label>
                    <input type="text" name="badge" class="form-control" value="<?= e($editItem['badge'] ?? 'Verified') ?>" />
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Description</label>
                  <textarea name="description" class="form-control" rows="3" required><?= e($editItem['description'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Sort Order</label>
                  <input type="number" name="sort_order" class="form-control" value="<?= (int)($editItem['sort_order'] ?? 1) ?>" />
                </div>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" name="is_active" id="featActive" value="1" <?= ($editItem['is_active'] ?? 1) ? 'checked' : '' ?> />
                  <label class="form-check-label" for="featActive">Visible on Landing Page</label>
                </div>
              </div>
              <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><?= $editItem ? 'Update' : 'Add Benefit' ?></button>
                <?php if ($editItem): ?>
                  <a href="features" class="btn btn-outline-secondary">Cancel</a>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </div>

        <div class="col-lg-8">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Active Benefit Cards</h3>
            </div>
            <div class="card-body table-responsive p-0">
              <table class="table table-striped align-middle mb-0">
                <thead>
                  <tr>
                    <th>Order</th>
                    <th>Benefit</th>
                    <th>Badge</th>
                    <th class="text-center">Status</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($features as $f): ?>
                    <tr>
                      <td>#<?= (int)$f['sort_order'] ?></td>
                      <td>
                        <div class="d-flex align-items-start gap-2">
                          <i class="bi <?= e($f['icon']) ?> fs-4 text-primary"></i>
                          <div>
                            <div class="fw-bold"><?= e($f['title']) ?></div>
                            <div class="fs-7 text-secondary"><?= e($f['description']) ?></div>
                          </div>
                        </div>
                      </td>
                      <td><span class="badge text-bg-info"><?= e($f['badge']) ?></span></td>
                      <td class="text-center">
                        <span class="badge <?= $f['is_active'] ? 'text-bg-success' : 'text-bg-secondary' ?>">
                          <?= $f['is_active'] ? 'Active' : 'Hidden' ?>
                        </span>
                      </td>
                      <td class="text-end">
                        <div class="action-btn-group">
                          <a href="features?edit=<?= (int)$f['id'] ?>" class="btn btn-outline-primary action-icon-btn" title="Edit Benefit"><i class="bi bi-pencil"></i></a>
                          <form method="post" class="d-inline m-0" onsubmit="return confirm('Delete this feature card?');">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>" />
                            <input type="hidden" name="action" value="delete" />
                            <input type="hidden" name="id" value="<?= (int)$f['id'] ?>" />
                            <button type="submit" class="btn btn-outline-danger action-icon-btn" title="Delete Benefit"><i class="bi bi-trash"></i></button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

