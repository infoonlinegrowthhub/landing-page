<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';

requireAdminAuth();
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id        = (int)($_POST['id'] ?? 0);
        $question  = trim($_POST['question'] ?? '');
        $answer    = trim($_POST['answer'] ?? '');
        $category  = trim($_POST['category'] ?? 'General');
        $sortOrder = (int)($_POST['sort_order'] ?? 1);
        $isActive  = isset($_POST['is_active']) ? 1 : 0;

        if ($question !== '' && $answer !== '') {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE faqs SET question=?, answer=?, category=?, sort_order=?, is_active=? WHERE id=?");
                $stmt->execute([$question, $answer, $category, $sortOrder, $isActive, $id]);
                setFlashMessage('success', 'FAQ updated.');
            } else {
                $stmt = $pdo->prepare("INSERT INTO faqs (question, answer, category, sort_order, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$question, $answer, $category, $sortOrder, $isActive]);
                setFlashMessage('success', 'FAQ added.');
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM faqs WHERE id = ?")->execute([$id]);
        setFlashMessage('info', 'FAQ deleted.');
    }
    header('Location: faqs');
    exit;
}

$editFaq = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM faqs WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editFaq = $stmt->fetch();
}

$faqs = $pdo->query("SELECT * FROM faqs ORDER BY sort_order ASC, id ASC")->fetchAll();
$pageTitle = 'FAQs Accordion Manager';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
$flash = getFlashMessage();
$csrfToken = generateCsrfToken();
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <h1 class="mb-0 fs-3">Frequently Asked Questions (FAQ) Manager</h1>
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
              <h3 class="card-title"><?= $editFaq ? 'Edit FAQ Item' : 'Add FAQ Item' ?></h3>
            </div>
            <form method="post">
              <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>" />
              <input type="hidden" name="action" value="save" />
              <input type="hidden" name="id" value="<?= (int)($editFaq['id'] ?? 0) ?>" />
              <div class="card-body">
                <div class="mb-3">
                  <label class="form-label fw-semibold">Question</label>
                  <input type="text" name="question" class="form-control" value="<?= e($editFaq['question'] ?? '') ?>" required />
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Answer</label>
                  <textarea name="answer" class="form-control" rows="4" required><?= e($editFaq['answer'] ?? '') ?></textarea>
                </div>
                <div class="row g-2 mb-3">
                  <div class="col-7">
                    <label class="form-label fw-semibold">Topic Tag</label>
                    <input type="text" name="category" class="form-control" value="<?= e($editFaq['category'] ?? 'Ordering') ?>" />
                  </div>
                  <div class="col-5">
                    <label class="form-label fw-semibold">Sort Order</label>
                    <input type="number" name="sort_order" class="form-control" value="<?= (int)($editFaq['sort_order'] ?? 1) ?>" />
                  </div>
                </div>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" name="is_active" id="faqActive" value="1" <?= ($editFaq['is_active'] ?? 1) ? 'checked' : '' ?> />
                  <label class="form-check-label" for="faqActive">Visible on Landing Page</label>
                </div>
              </div>
              <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><?= $editFaq ? 'Update FAQ' : 'Add FAQ' ?></button>
                <?php if ($editFaq): ?>
                  <a href="faqs" class="btn btn-outline-secondary">Cancel</a>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </div>

        <div class="col-lg-8">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Landing Page FAQs (<?= count($faqs) ?>)</h3>
            </div>
            <div class="card-body table-responsive p-0">
              <table class="table table-striped align-middle mb-0">
                <thead>
                  <tr>
                    <th>Order</th>
                    <th>Question &amp; Answer</th>
                    <th>Topic</th>
                    <th class="text-center">Status</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($faqs as $q): ?>
                    <tr>
                      <td>#<?= (int)$q['sort_order'] ?></td>
                      <td>
                        <div class="fw-bold"><?= e($q['question']) ?></div>
                        <div class="fs-7 text-secondary"><?= e($q['answer']) ?></div>
                      </td>
                      <td><span class="badge text-bg-secondary"><?= e($q['category']) ?></span></td>
                      <td class="text-center">
                        <span class="badge <?= $q['is_active'] ? 'text-bg-success' : 'text-bg-secondary' ?>">
                          <?= $q['is_active'] ? 'Active' : 'Hidden' ?>
                        </span>
                      </td>
                      <td class="text-end text-nowrap">
                        <div class="action-btn-group">
                          <a href="faqs?edit=<?= (int)$q['id'] ?>" class="btn btn-outline-primary action-icon-btn" title="Edit FAQ"><i class="bi bi-pencil"></i></a>
                          <form method="post" class="d-inline m-0" onsubmit="return confirm('Delete this FAQ?');">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>" />
                            <input type="hidden" name="action" value="delete" />
                            <input type="hidden" name="id" value="<?= (int)$q['id'] ?>" />
                            <button type="submit" class="btn btn-outline-danger action-icon-btn" title="Delete FAQ"><i class="bi bi-trash"></i></button>
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

