<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';

requireAdminAuth();
$pdo = getDB();
$admin = getCurrentAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $newPass  = $_POST['new_password'] ?? '';

    if ($fullName !== '' && $email !== '' && $username !== '') {
        $stmt = $pdo->prepare("UPDATE admins SET full_name = ?, email = ?, username = ? WHERE id = ?");
        $stmt->execute([$fullName, $email, $username, $admin['id']]);

        if ($newPass !== '') {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE admins SET password_hash = ? WHERE id = ?")->execute([$hash, $admin['id']]);
        }

        setFlashMessage('success', 'Admin profile updated successfully.');
        header('Location: profile');
        exit;
    }
}

$pageTitle = 'Admin Profile & Security';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
$flash = getFlashMessage();
$csrfToken = generateCsrfToken();
?>

<main class="app-main">
  <div class="app-content-header">
    <div class="container-fluid">
      <h1 class="mb-0 fs-3">Admin Profile &amp; Credentials</h1>
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

      <div class="row justify-content-center">
        <div class="col-lg-6">
          <div class="card card-primary card-outline">
            <div class="card-header">
              <h3 class="card-title">Update Administrator Account</h3>
            </div>
            <form method="post">
              <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>" />
              <div class="card-body">
                <div class="mb-3">
                  <label class="form-label fw-semibold">Full Name</label>
                  <input type="text" name="full_name" class="form-control" value="<?= e($currentAdmin['full_name']) ?>" required />
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Username</label>
                  <input type="text" name="username" class="form-control" value="<?= e($currentAdmin['username']) ?>" required />
                </div>
                <div class="mb-3">
                  <label class="form-label fw-semibold">Email Address</label>
                  <input type="email" name="email" class="form-control" value="<?= e($currentAdmin['email']) ?>" required />
                </div>
                <hr />
                <div class="mb-0">
                  <label class="form-label fw-semibold">New Password <small class="text-secondary fw-normal">(Leave blank to keep current password)</small></label>
                  <input type="password" name="new_password" class="form-control" placeholder="••••••••" />
                </div>
              </div>
              <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">Save Changes</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

