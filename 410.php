<?php
/**
 * HTTP 410 Gone Handler for Permanently Removed Content & Retired Routes
 */

require_once __DIR__ . '/config/functions.php';

http_response_code(410);
header('HTTP/1.1 410 Gone');
header('X-Robots-Tag: noindex, nofollow');

$siteName   = getSetting('site_name', 'NovaPulse');
$domainName = getSetting('canonical_domain', 'novapulse.in');
$pageTitle  = formatBrandFirstTitle('410 Content Permanently Removed');
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <title><?= e($pageTitle) ?></title>
    <link rel="icon" type="image/png" sizes="48x48" href="assets/favicons/favicon-48x48.png" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" crossorigin="anonymous" />
  </head>
  <body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <main class="container text-center" style="max-width: 540px;">
      <div class="card border-0 shadow-sm rounded-4 p-4 p-sm-5">
        <span class="badge text-bg-danger mx-auto mb-3 px-3 py-2 rounded-pill">HTTP 410 GONE</span>
        <h1 class="h3 fw-bold mb-2">This Content Has Been Permanently Removed</h1>
        <p class="text-secondary mb-4">
          The product, promotional offer, or route you requested has been permanently retired from <strong><?= e($siteName) ?> (<?= e($domainName) ?>)</strong> and is no longer available.
        </p>
        <a href="<?= e(getBaseUrl() . '/') ?>" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold">
          Explore Active Catalog &rarr;
        </a>
      </div>
    </main>
  </body>
</html>

