      <!-- Sidebar -->
      <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <!-- Brand Header (Zero AdminLTE Branding) -->
        <div class="sidebar-brand">
          <a href="./" class="brand-link d-flex align-items-center gap-2 text-decoration-none">
            <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary text-white shadow-sm" style="width: 30px; height: 30px;">
              <i class="bi bi-lightning-charge-fill"></i>
            </span>
            <span class="brand-text fw-bold"><?= e($siteName) ?> <small class="fw-normal opacity-75">Admin</small></span>
          </a>
        </div>

        <!-- Sidebar Search -->
        <div class="sidebar-search" role="search">
          <label for="sidebar-search-input" class="visually-hidden">Filter menu</label>
          <input
            type="search"
            id="sidebar-search-input"
            class="form-control form-control-sm"
            placeholder="Filter menu…"
            autocomplete="off"
            data-lte-toggle="sidebar-search"
            data-lte-target="#navigation"
          />
          <p class="fs-7 text-secondary mt-2 mb-0" data-lte-search-empty role="status" hidden>
            No matching menu items.
          </p>
        </div>

        <!-- Sidebar Wrapper -->
        <div class="sidebar-wrapper">
          <nav class="mt-2" aria-label="Main navigation">
            <ul
              class="nav sidebar-menu flex-column"
              data-lte-toggle="treeview"
              data-accordion="false"
              id="navigation"
            >
              <li class="nav-header">OVERVIEW</li>
              <li class="nav-item">
                <a href="./" class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                  <i class="nav-icon bi bi-speedometer2"></i>
                  <p><span>Dashboard</span></p>
                </a>
              </li>
              <li class="nav-item">
                <a href="analytics" class="nav-link <?= $currentPage === 'analytics.php' ? 'active' : '' ?>">
                  <i class="nav-icon bi bi-graph-up-arrow text-info"></i>
                  <p>
                    <span>Click Analytics</span>
                    <?php if ($todayClicksCount > 0): ?>
                      <span class="nav-badge badge text-bg-info"><?= $todayClicksCount ?></span>
                    <?php endif; ?>
                  </p>
                </a>
              </li>

              <li class="nav-header">CATALOG &amp; BUY LINKS</li>
              <li class="nav-item">
                <a href="products" class="nav-link <?= in_array($currentPage, ['products.php', 'product-form.php']) ? 'active' : '' ?>">
                  <i class="nav-icon bi bi-bag-check-fill text-success"></i>
                  <p><span>Products &amp; Links</span></p>
                </a>
              </li>
              <li class="nav-item">
                <a href="product-form" class="nav-link">
                  <i class="nav-icon bi bi-plus-square-dotted"></i>
                  <p><span>Add Product</span></p>
                </a>
              </li>
              <li class="nav-item">
                <a href="categories" class="nav-link <?= $currentPage === 'categories.php' ? 'active' : '' ?>">
                  <i class="nav-icon bi bi-tags-fill"></i>
                  <p><span>Categories</span></p>
                </a>
              </li>

              <li class="nav-header">LANDING SECTIONS</li>
              <li class="nav-item">
                <a href="hero" class="nav-link <?= $currentPage === 'hero.php' ? 'active' : '' ?>">
                  <i class="nav-icon bi bi-megaphone-fill text-warning"></i>
                  <p><span>Hero Banner</span></p>
                </a>
              </li>
              <li class="nav-item">
                <a href="features" class="nav-link <?= $currentPage === 'features.php' ? 'active' : '' ?>">
                  <i class="nav-icon bi bi-stars"></i>
                  <p><span>Trust Benefits</span></p>
                </a>
              </li>
              <li class="nav-item">
                <a href="testimonials" class="nav-link <?= $currentPage === 'testimonials.php' ? 'active' : '' ?>">
                  <i class="nav-icon bi bi-chat-quote-fill"></i>
                  <p><span>Customer Reviews</span></p>
                </a>
              </li>
              <li class="nav-item">
                <a href="faqs" class="nav-link <?= $currentPage === 'faqs.php' ? 'active' : '' ?>">
                  <i class="nav-icon bi bi-question-circle-fill"></i>
                  <p><span>FAQs</span></p>
                </a>
              </li>

              <li class="nav-header">LEADS &amp; SETTINGS</li>
              <li class="nav-item">
                <a href="inquiries" class="nav-link <?= $currentPage === 'inquiries.php' ? 'active' : '' ?>">
                  <i class="nav-icon bi bi-inbox-fill"></i>
                  <p>
                    <span>Inquiries</span>
                    <?php if ($unreadInquiriesCount > 0): ?>
                      <span class="nav-badge badge text-bg-danger"><?= $unreadInquiriesCount ?></span>
                    <?php endif; ?>
                  </p>
                </a>
              </li>
              <li class="nav-item">
                <a href="settings" class="nav-link <?= $currentPage === 'settings.php' ? 'active' : '' ?>">
                  <i class="nav-icon bi bi-sliders"></i>
                  <p><span>Store Settings</span></p>
                </a>
              </li>
              <li class="nav-item">
                <a href="profile" class="nav-link <?= $currentPage === 'profile.php' ? 'active' : '' ?>">
                  <i class="nav-icon bi bi-person-gear"></i>
                  <p><span>Admin Profile</span></p>
                </a>
              </li>
            </ul>

            <div class="p-3 mt-3 border-top border-secondary border-opacity-25">
              <a
                href="../"
                target="_blank"
                class="btn btn-sm btn-primary w-100 d-flex align-items-center justify-content-center gap-2"
              >
                <i class="bi bi-eye-fill" aria-hidden="true"></i>
                Open Live Storefront
              </a>
            </div>
          </nav>
        </div>
      </aside>
