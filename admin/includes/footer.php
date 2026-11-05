      <!-- Footer -->
      <footer class="app-footer">
        <div class="float-end d-none d-sm-inline">
          <?= e($siteName) ?> Management Console
        </div>
        <strong>
          Copyright &copy; <?= date('Y') ?>
          <a href="../" class="text-decoration-none"><?= e($siteName) ?></a>.
        </strong>
        All rights reserved.
      </footer>
    </div>

    <!-- OverlayScrollbars (Direct Pinned CDN) -->
    <script
      src="https://cdnjs.cloudflare.com/ajax/libs/overlayscrollbars/2.10.0/browser/overlayscrollbars.browser.es6.min.js"
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
    ></script>
    <!-- Bootstrap 5 Bundle with Popper (Direct Pinned CDN) -->
    <script
      src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
    ></script>
    <!-- Core Admin JS -->
    <script src="../adminlte/dist/js/adminlte.js"></script>

    <!-- OverlayScrollbars Configure (Sandboxed Crawler Safe) -->
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        try {
          const sidebarWrapper = document.querySelector('.sidebar-wrapper');
          const isMobile = window.innerWidth <= 992;
          if (
            sidebarWrapper &&
            globalThis.OverlayScrollbarsGlobal?.OverlayScrollbars !== undefined &&
            !isMobile
          ) {
            OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
              scrollbars: {
                theme: 'os-theme-light',
                autoHide: 'leave',
                clickScroll: true,
              },
            });
          }
        } catch (e) {}
      });
    </script>
  </body>
</html>
