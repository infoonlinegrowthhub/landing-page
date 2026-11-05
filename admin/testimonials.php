<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';

requireAdminAuth();
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id           = (int)($_POST['id'] ?? 0);
        $customerName = trim($_POST['customer_name'] ?? '');
        $customerRole = trim($_POST['customer_role'] ?? 'Verified Buyer');
        $rating       = min(5, max(1, (int)($_POST['rating'] ?? 5)));
        $reviewTitle  = trim($_POST['review_title'] ?? '');
        $reviewText   = trim($_POST['review_text'] ?? '');
        $productName  = trim($_POST['product_name'] ?? '');
        $sortOrder    = (int)($_POST['sort_order'] ?? 0);
        $isVerified   = isset($_POST['is_verified']) ? 1 : 0;
        $isActive     = isset($_POST['is_active']) ? 1 : 0;

        $uploadedAvatar = handleImageUpload('avatar_file', 'avatar');
        $avatar = $uploadedAvatar ?: trim($_POST['customer_avatar'] ?? '');
        if ($avatar === '') {
            $avatar = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=250&q=80';
        }

        if ($customerName !== '' && $reviewText !== '') {
            if ($id > 0) {
                $stmt = $pdo->prepare("
                    UPDATE testimonials
                    SET customer_name=?, customer_role=?, customer_avatar=?, rating=?, review_title=?, review_text=?, product_name=?, is_verified=?, is_active=?, sort_order=?
                    WHERE id=?
                ");
                $stmt->execute([$customerName, $customerRole, $avatar, $rating, $reviewTitle, $reviewText, $productName, $isVerified, $isActive, $sortOrder, $id]);
                setFlashMessage('success', 'Customer review updated.');
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO testimonials (customer_name, customer_role, customer_avatar, rating, review_title, review_text, product_name, is_verified, is_active, sort_order)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$customerName, $customerRole, $avatar, $rating, $reviewTitle, $reviewText, $productName, $isVerified, $isActive, $sortOrder]);
                setFlashMessage('success', 'Customer review added.');
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM testimonials WHERE id = ?")->execute([$id]);
        setFlashMessage('info', 'Customer review deleted.');
    }
    header('Location: testimonials');
    exit;
}

$editReview = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM testimonials WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editReview = $stmt->fetch();
}

$testimonials = $pdo->query("SELECT * FROM testimonials ORDER BY sort_order ASC, id DESC")->fetchAll();
$pageTitle = 'Customer Testimonials & Reviews';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
$flash = getFlashMessage();
$csrfToken = generateCsrfToken();
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <h1 class="mb-0 fs-3">Customer Testimonials &amp; Social Proof</h1>
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
              <h3 class="card-title"><?= $editReview ? 'Edit Review' : 'Add Customer Review' ?></h3>
            </div>
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>" />
              <input type="hidden" name="action" value="save" />
              <input type="hidden" name="id" value="<?= (int)($editReview['id'] ?? 0) ?>" />
              <div class="card-body">
                <div class="mb-3">
                  <label class="form-label fw-semibold">Customer Name</label>
                  <input type="text" name="customer_name" class="form-control" value="<?= e($editReview['customer_name'] ?? '') ?>" required />
                </div>
                <div class="row g-2 mb-3">
                  <div class="col-8">
                    <label class="form-label fw-semibold">Role / Title</label>
                    <input type="text" name="customer_role" class="form-control" value="<?= e($editReview['customer_role'] ?? 'Verified Buyer') ?>" />
                  </div>
                  <div class="col-4">
                    <label class="form-label fw-semibold">Stars (1-5)</label>
                    <input type="number" min="1" max="5" name="rating" class="form-control" value="<?= (int)($editReview['rating'] ?? 5) ?>" />
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Review Headline</label>
                  <input type="text" name="review_title" class="form-control" value="<?= e($editReview['review_title'] ?? '') ?>" placeholder="e.g. Best purchase of the year!" />
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Review Quote</label>
                  <textarea name="review_text" class="form-control" rows="3" required><?= e($editReview['review_text'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Purchased Product Name</label>
                  <input type="text" name="product_name" class="form-control" value="<?= e($editReview['product_name'] ?? '') ?>" />
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Avatar URL (or upload below)</label>
                  <input type="text" name="customer_avatar" class="form-control mb-2" value="<?= e($editReview['customer_avatar'] ?? '') ?>" />
                  <input type="file" name="avatar_file" class="form-control form-control-sm" accept="image/*" />
                </div>
                <div class="row g-2 align-items-center">
                  <div class="col-5">
                    <label class="form-label fw-semibold">Sort Order</label>
                    <input type="number" name="sort_order" class="form-control" value="<?= (int)($editReview['sort_order'] ?? 1) ?>" />
                  </div>
                  <div class="col-7 pt-3">
                    <div class="form-check form-switch">
                      <input class="form-check-input" type="checkbox" name="is_verified" id="verBadge" value="1" <?= ($editReview['is_verified'] ?? 1) ? 'checked' : '' ?> />
                      <label class="form-check-label" for="verBadge">Verified Buyer</label>
                    </div>
                    <div class="form-check form-switch">
                      <input class="form-check-input" type="checkbox" name="is_active" id="revActive" value="1" <?= ($editReview['is_active'] ?? 1) ? 'checked' : '' ?> />
                      <label class="form-check-label" for="revActive">Published</label>
                    </div>
                  </div>
                </div>
              </div>
              <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><?= $editReview ? 'Update Review' : 'Add Review' ?></button>
                <?php if ($editReview): ?>
                  <a href="testimonials" class="btn btn-outline-secondary">Cancel</a>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </div>

        <div class="col-lg-8">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Published Customer Reviews (<?= count($testimonials) ?>)</h3>
            </div>
            <div class="card-body table-responsive p-0">
              <table class="table table-striped align-middle mb-0">
                <thead>
                  <tr>
                    <th>Customer</th>
                    <th>Review</th>
                    <th class="text-center">Rating</th>
                    <th class="text-center">Status</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($testimonials as $t): ?>
                    <tr>
                      <td>
                        <div class="d-flex align-items-center gap-2">
                          <img src="<?= e(resolveImageUrl($t['customer_avatar'], true)) ?>" alt="" class="rounded-circle border" style="width:42px;height:42px;object-fit:cover;" />
                          <div>
                            <div class="fw-bold"><?= e($t['customer_name']) ?></div>
                            <small class="text-secondary"><?= e($t['customer_role']) ?></small>
                          </div>
                        </div>
                      </td>
                      <td>
                        <?php if ($t['review_title']): ?>
                          <div class="fw-semibold"><?= e($t['review_title']) ?></div>
                        <?php endif; ?>
                        <div class="fs-7 text-secondary"><?= e($t['review_text']) ?></div>
                        <?php if ($t['product_name']): ?>
                          <span class="badge text-bg-light border mt-1"><?= e($t['product_name']) ?></span>
                        <?php endif; ?>
                      </td>
                      <td class="text-center text-warning text-nowrap">
                        <?= str_repeat('<i class="bi bi-star-fill"></i>', (int)$t['rating']) ?>
                      </td>
                      <td class="text-center">
                        <span class="badge <?= $t['is_active'] ? 'text-bg-success' : 'text-bg-secondary' ?>">
                          <?= $t['is_active'] ? 'Active' : 'Hidden' ?>
                        </span>
                      </td>
                      <td class="text-end text-nowrap">
                        <div class="action-btn-group">
                          <a href="testimonials?edit=<?= (int)$t['id'] ?>" class="btn btn-outline-primary action-icon-btn" title="Edit Review"><i class="bi bi-pencil"></i></a>
                          <form method="post" class="d-inline m-0" onsubmit="return confirm('Delete this review?');">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>" />
                            <input type="hidden" name="action" value="delete" />
                            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>" />
                            <button type="submit" class="btn btn-outline-danger action-icon-btn" title="Delete Review"><i class="bi bi-trash"></i></button>
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

