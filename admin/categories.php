<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';

requireAdminAuth();
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id          = (int)($_POST['id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $icon        = trim($_POST['icon'] ?? 'bi-tag');
        $description = trim($_POST['description'] ?? '');
        $sortOrder   = (int)($_POST['sort_order'] ?? 0);
        $isActive    = isset($_POST['is_active']) ? 1 : 0;
        $slug        = slugify($name);

        if ($name !== '') {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE categories SET name=?, slug=?, icon=?, description=?, sort_order=?, is_active=? WHERE id=?");
                $stmt->execute([$name, $slug, $icon, $description, $sortOrder, $isActive, $id]);
                setFlashMessage('success', 'Category updated.');
            } else {
                $stmt = $pdo->prepare("INSERT INTO categories (name, slug, icon, description, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $slug, $icon, $description, $sortOrder, $isActive]);
                setFlashMessage('success', 'Category created.');
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
        setFlashMessage('info', 'Category deleted.');
    }
    header('Location: categories');
    exit;
}

$editCategory = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editCategory = $stmt->fetch();
}

$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id
    ORDER BY c.sort_order ASC, c.id ASC
")->fetchAll();

$pageTitle = 'Product Categories';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
$flash = getFlashMessage();
$csrfToken = generateCsrfToken();
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <h1 class="mb-0 fs-3">Product Categories</h1>
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
              <h3 class="card-title"><?= $editCategory ? 'Edit Category' : 'Add New Category' ?></h3>
            </div>
            <form method="post">
              <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>" />
              <input type="hidden" name="action" value="save" />
              <input type="hidden" name="id" value="<?= (int)($editCategory['id'] ?? 0) ?>" />
              <div class="card-body">
                <div class="mb-3">
                  <label class="form-label fw-semibold">Category Name</label>
                  <input type="text" name="name" class="form-control" value="<?= e($editCategory['name'] ?? '') ?>" required />
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Bootstrap Icon Class</label>
                  <input type="text" name="icon" class="form-control" value="<?= e($editCategory['icon'] ?? 'bi-headphones') ?>" placeholder="e.g. bi-headphones, bi-smartwatch" />
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Short Description</label>
                  <input type="text" name="description" class="form-control" value="<?= e($editCategory['description'] ?? '') ?>" />
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Sort Order</label>
                  <input type="number" name="sort_order" class="form-control" value="<?= (int)($editCategory['sort_order'] ?? 1) ?>" />
                </div>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" name="is_active" id="catActive" value="1" <?= ($editCategory['is_active'] ?? 1) ? 'checked' : '' ?> />
                  <label class="form-check-label" for="catActive">Active on Landing Page Filter</label>
                </div>
              </div>
              <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                  <?= $editCategory ? 'Update Category' : 'Add Category' ?>
                </button>
                <?php if ($editCategory): ?>
                  <a href="categories" class="btn btn-outline-secondary">Cancel</a>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </div>

        <div class="col-lg-8">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">All Categories</h3>
            </div>
            <div class="card-body table-responsive p-0">
              <table class="table table-striped align-middle mb-0">
                <thead>
                  <tr>
                    <th>Order</th>
                    <th>Category</th>
                    <th>Slug</th>
                    <th class="text-center">Products</th>
                    <th class="text-center">Status</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($categories as $cat): ?>
                    <tr>
                      <td>#<?= (int)$cat['sort_order'] ?></td>
                      <td>
                        <i class="bi <?= e($cat['icon']) ?> me-2 text-primary"></i>
                        <strong><?= e($cat['name']) ?></strong>
                        <?php if ($cat['description']): ?>
                          <div class="fs-7 text-secondary"><?= e($cat['description']) ?></div>
                        <?php endif; ?>
                      </td>
                      <td><code><?= e($cat['slug']) ?></code></td>
                      <td class="text-center"><span class="badge text-bg-secondary"><?= (int)$cat['product_count'] ?></span></td>
                      <td class="text-center">
                        <span class="badge <?= $cat['is_active'] ? 'text-bg-success' : 'text-bg-secondary' ?>">
                          <?= $cat['is_active'] ? 'Active' : 'Hidden' ?>
                        </span>
                      </td>
                      <td class="text-end">
                        <div class="action-btn-group">
                          <a href="categories?edit=<?= (int)$cat['id'] ?>" class="btn btn-outline-primary action-icon-btn" title="Edit Category">
                            <i class="bi bi-pencil"></i>
                          </a>
                          <form method="post" class="d-inline m-0" onsubmit="return confirm('Delete this category?');">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>" />
                            <input type="hidden" name="action" value="delete" />
                            <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>" />
                            <button type="submit" class="btn btn-outline-danger action-icon-btn" title="Delete Category"><i class="bi bi-trash"></i></button>
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

