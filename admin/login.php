<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';

if (isAdminLoggedIn()) {
    header('Location: ./');
    exit;
}

$pdo = getDB();
$siteName = getSetting('site_name', 'NovaPulse');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginInput = trim($_POST['login'] ?? '');
    $password   = $_POST['password'] ?? '';

    if ($loginInput === '' || $password === '') {
        $error = 'Please enter both username/email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$loginInput, $loginInput]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$admin['id'];
            $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")->execute([$admin['id']]);
            header('Location: ./');
            exit;
        } else {
            $error = 'Invalid username/email or password.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?= e(formatBrandFirstTitle('Admin Sign In')) ?></title>
    <link rel="icon" type="image/png" sizes="48x48" href="../assets/favicons/favicon-48x48.png" crossorigin="anonymous" />
    <script>
      (() => {
        'use strict';
        try {
          const root = document.documentElement;
          const stored = localStorage.getItem('lte-theme');
          const resolved = stored === 'dark' || stored === 'light'
            ? stored
            : (globalThis.matchMedia && globalThis.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
          root.setAttribute('data-bs-theme', resolved);
          root.style.colorScheme = resolved;
        } catch (e) {}
      })();
    </script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css"
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
    />
    <link rel="stylesheet" href="../adminlte/dist/css/adminlte.css" />
  </head>
  <body class="login-page bg-body-secondary">
    <main class="login-box">
      <div class="card card-outline card-primary shadow">
        <div class="card-header text-center py-3">
          <a href="../" class="link-dark text-center text-decoration-none">
            <h1 class="mb-0 fs-3"><i class="bi bi-lightning-charge-fill text-primary me-1"></i><b><?= e($siteName) ?></b> Admin</h1>
          </a>
        </div>
        <div class="card-body login-card-body">
          <p class="login-box-msg">Sign in to manage products &amp; payment links</p>

          <?php if ($error): ?>
            <div class="alert alert-danger py-2 fs-7">
              <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?>
            </div>
          <?php endif; ?>

          <form action="login" method="post">
            <div class="input-group mb-3">
              <div class="form-floating">
                <input
                  id="loginInput"
                  name="login"
                  type="text"
                  class="form-control"
                  value="<?= e($_POST['login'] ?? 'admin') ?>"
                  placeholder="Username or Email"
                  required
                />
                <label for="loginInput">Username or Email</label>
              </div>
              <div class="input-group-text">
                <span class="bi bi-person-fill"></span>
              </div>
            </div>

            <div class="input-group mb-3">
              <div class="form-floating">
                <input
                  id="loginPassword"
                  name="password"
                  type="password"
                  class="form-control"
                  value="admin123"
                  placeholder="Password"
                  required
                />
                <label for="loginPassword">Password</label>
              </div>
              <div class="input-group-text">
                <span class="bi bi-lock-fill"></span>
              </div>
            </div>

            <div class="row align-items-center mb-3">
              <div class="col-7">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="rememberMe" checked />
                  <label class="form-check-label" for="rememberMe">Remember Me</label>
                </div>
              </div>
              <div class="col-5">
                <div class="d-grid">
                  <button type="submit" class="btn btn-primary fw-semibold">
                    Sign In <i class="bi bi-box-arrow-in-right ms-1"></i>
                  </button>
                </div>
              </div>
            </div>
          </form>

          <div class="p-2 rounded bg-body-tertiary border fs-7 text-secondary mb-3">
            <div class="fw-semibold text-body mb-1"><i class="bi bi-key-fill me-1 text-primary"></i> Admin Login:</div>
            <div>Username: <code>admin</code> &nbsp;|&nbsp; Password: <code>admin123</code></div>
          </div>

          <p class="mb-0 text-center">
            <a href="../" class="text-decoration-none fs-7">
              <i class="bi bi-arrow-left me-1"></i> Back to Storefront
            </a>
          </p>
        </div>
      </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="../adminlte/dist/js/adminlte.js"></script>
  </body>
</html>
