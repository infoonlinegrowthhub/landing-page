<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/functions.php';

requireAdminAuth();

$pdo = getDB();
$currentAdmin = getCurrentAdmin();
$siteName = getSetting('site_name', 'NovaPulse');
$currentPage = basename($_SERVER['PHP_SELF']);

$unreadInquiriesCount = (int)$pdo->query("SELECT COUNT(*) FROM inquiries WHERE status = 'Unread'")->fetchColumn();
$recentUnreadInquiries = $pdo->query("SELECT id, name, subject, created_at FROM inquiries WHERE status = 'Unread' ORDER BY id DESC LIMIT 4")->fetchAll();
$todayClicksCount = (int)$pdo->query("SELECT COUNT(*) FROM click_logs WHERE DATE(clicked_at) = CURDATE()")->fetchColumn();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?= e(formatBrandFirstTitle($pageTitle ?? 'Dashboard')) ?></title>
    <link rel="icon" type="image/png" sizes="48x48" href="../assets/favicons/favicon-48x48.png" crossorigin="anonymous" />
    <link rel="icon" type="image/png" sizes="96x96" href="../assets/favicons/favicon-96x96.png" crossorigin="anonymous" />
    <link rel="icon" type="image/svg+xml" href="../assets/favicons/favicon.svg" crossorigin="anonymous" />

    <script>
      (() => {
        'use strict';
        try {
          const root = document.documentElement;
          const STORAGE_KEY = 'lte-theme';
          let stored = null;
          try {
            stored = localStorage.getItem(STORAGE_KEY);
          } catch (e) {}
          const authored = root.getAttribute('data-bs-theme');
          let resolved = 'light';
          if (stored === 'dark' || stored === 'light') {
            resolved = stored;
          } else if (authored === 'dark' || authored === 'light') {
            resolved = authored;
          } else if (globalThis.matchMedia && globalThis.matchMedia('(prefers-color-scheme: dark)').matches) {
            resolved = 'dark';
          }
          root.setAttribute('data-bs-theme', resolved);
          root.style.colorScheme = resolved;
          if (resolved !== authored) {
            root.setAttribute('data-lte-theme-resolved', '');
          }
        } catch (e) {}
      })();
    </script>

    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <meta name="color-scheme" content="light dark" />

    <!-- OverlayScrollbars (Direct Pinned CDN) -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/overlayscrollbars/2.10.0/styles/overlayscrollbars.min.css"
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
    />

    <!-- Bootstrap Icons (Direct Pinned CDN) -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css"
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
    />

    <!-- Core Admin CSS -->
    <link rel="stylesheet" href="../adminlte/dist/css/adminlte.css" />

    <style>
      .table td, .table th {
        vertical-align: middle;
      }
      .product-thumb-sm {
        width: 48px;
        height: 48px;
        object-fit: cover;
        border-radius: 0.5rem;
        flex-shrink: 0;
      }
      .external-url-pill {
        max-width: 210px;
        display: inline-block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        vertical-align: bottom;
      }
      /* Fix Sidebar Nav Text & Badge Overlap */
      .sidebar-menu .nav-link {
        display: flex !important;
        align-items: center !important;
      }
      .sidebar-menu .nav-link p {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        width: 100%;
        margin-bottom: 0;
        padding-right: 0 !important;
        gap: 0.5rem;
      }
      .sidebar-menu .nav-badge {
        position: static !important;
        margin-left: auto;
        font-size: 0.72rem;
        padding: 0.2em 0.55em;
        border-radius: 999px;
        flex-shrink: 0;
      }
      /* Uniform Action Buttons in Tables */
      .action-btn-group {
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.35rem;
        flex-wrap: nowrap;
      }
      .action-icon-btn {
        width: 34px;
        height: 34px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.45rem;
        flex-shrink: 0;
        font-size: 0.92rem;
        line-height: 1;
      }
      /* Cleaner Small Box KPI Cards */
      .small-box {
        border-radius: 0.85rem;
        overflow: hidden;
      }
      .small-box .inner {
        padding: 1.15rem 1.25rem;
      }
      .small-box .inner h3 {
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 0.25rem;
      }
      .small-box .inner p {
        font-size: 0.92rem;
        margin-bottom: 0;
        opacity: 0.92;
      }
    </style>
  </head>
  <body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
      <!-- Header -->
      <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
          <ul class="navbar-nav align-items-center">
            <li class="nav-item">
              <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Toggle sidebar">
                <i class="bi bi-list fs-5"></i>
              </a>
            </li>
            <li class="nav-item d-none d-md-block">
              <a href="../" target="_blank" class="nav-link fw-semibold text-primary">
                <i class="bi bi-box-arrow-up-right me-1"></i>
                View Storefront
              </a>
            </li>
            <li class="nav-item d-none d-lg-block">
              <a href="product-form" class="nav-link">
                <i class="bi bi-plus-circle me-1 text-success"></i>
                Add Product
              </a>
            </li>
          </ul>

          <ul class="navbar-nav ms-auto align-items-center">
            <!-- Today's Outbound Clicks Quick Pill -->
            <li class="nav-item d-none d-sm-block me-2">
              <a href="analytics" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                <i class="bi bi-cursor-fill me-1"></i>
                Today's Clicks: <strong><?= $todayClicksCount ?></strong>
              </a>
            </li>

            <!-- Inquiries Dropdown -->
            <li class="nav-item dropdown">
              <a class="nav-link" data-bs-toggle="dropdown" href="#" aria-label="Customer Inquiries">
                <i class="bi bi-envelope-fill"></i>
                <?php if ($unreadInquiriesCount > 0): ?>
                  <span class="navbar-badge badge text-bg-danger"><?= $unreadInquiriesCount ?></span>
                <?php endif; ?>
              </a>
              <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                <span class="dropdown-item dropdown-header">
                  <?= $unreadInquiriesCount ?> Unread Inquiries
                </span>
                <div class="dropdown-divider"></div>
                <?php if (empty($recentUnreadInquiries)): ?>
                  <span class="dropdown-item text-secondary py-3 text-center">
                    <i class="bi bi-check2-circle me-1"></i> All caught up!
                  </span>
                <?php else: ?>
                  <?php foreach ($recentUnreadInquiries as $inq): ?>
                    <a href="inquiries?view=<?= (int)$inq['id'] ?>" class="dropdown-item">
                      <div class="d-flex flex-column">
                        <span class="fw-semibold text-truncate"><?= e($inq['name']) ?></span>
                        <span class="fs-7 text-secondary text-truncate"><?= e($inq['subject']) ?></span>
                        <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('M j, g:i A', strtotime($inq['created_at'])) ?></small>
                      </div>
                    </a>
                    <div class="dropdown-divider"></div>
                  <?php endforeach; ?>
                <?php endif; ?>
                <a href="inquiries" class="dropdown-item dropdown-footer">See All Inquiries</a>
              </div>
            </li>

            <!-- Color Mode Toggle -->
            <li class="nav-item dropdown">
              <a
                class="nav-link"
                href="#"
                id="bd-theme"
                aria-label="Toggle color scheme"
                data-bs-toggle="dropdown"
                aria-expanded="false"
              >
                <i class="bi bi-sun-fill" data-lte-theme-icon="light"></i>
                <i class="bi bi-moon-fill d-none" data-lte-theme-icon="dark"></i>
                <i class="bi bi-circle-half d-none" data-lte-theme-icon="auto"></i>
              </a>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="bd-theme" style="--bs-dropdown-min-width: 8rem">
                <li>
                  <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="light">
                    <i class="bi bi-sun-fill me-2"></i> Light
                  </button>
                </li>
                <li>
                  <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="dark">
                    <i class="bi bi-moon-fill me-2"></i> Dark
                  </button>
                </li>
                <li>
                  <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="auto">
                    <i class="bi bi-circle-half me-2"></i> Auto
                  </button>
                </li>
              </ul>
            </li>

            <!-- User Menu -->
            <li class="nav-item dropdown user-menu">
              <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                <img
                  src="<?= e($currentAdmin['avatar'] ?: '../adminlte/dist/assets/img/user2-160x160.jpg') ?>"
                  class="user-image rounded-circle shadow"
                  alt="<?= e($currentAdmin['full_name'] ?? 'Admin') ?>"
                />
                <span class="d-none d-md-inline"><?= e($currentAdmin['full_name'] ?? 'Administrator') ?></span>
              </a>
              <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                <li class="user-header text-bg-primary">
                  <img
                    src="<?= e($currentAdmin['avatar'] ?: '../adminlte/dist/assets/img/user2-160x160.jpg') ?>"
                    class="rounded-circle shadow"
                    alt="Admin"
                  />
                  <p>
                    <?= e($currentAdmin['full_name'] ?? 'Administrator') ?>
                    <small><?= e($currentAdmin['role'] ?? 'Store Admin') ?> • <?= e($currentAdmin['email'] ?? '') ?></small>
                  </p>
                </li>
                <li class="user-footer">
                  <a href="profile" class="btn btn-outline-secondary">Profile</a>
                  <a href="logout" class="btn btn-outline-danger float-end">Sign out</a>
                </li>
              </ul>
            </li>
          </ul>
        </div>
      </nav>
