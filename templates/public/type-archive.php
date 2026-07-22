<!doctype html>
<html lang="<?= $e($htmlLang) ?>">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php /* Runtime base path: anchors the SPA (router/API derive it from <base>). #zip-install S2 */ ?>
    <base href="<?= $e($basePath . '/') ?>" />
    <?php /* Favicon: declared in the crawlable SSR shell so Google/browsers use the real
             icon instead of an auto-generated letter monogram (#986). Base-relative. */ ?>
    <link rel="icon" href="assets/favicon/favicon.svg" type="image/svg+xml" />
    <link rel="icon" href="assets/favicon/favicon-32.png" sizes="32x32" />
    <link rel="icon" href="assets/favicon/favicon-16.png" sizes="16x16" />
    <link rel="apple-touch-icon" href="assets/favicon/apple-touch-icon.png" />
    <link rel="manifest" href="assets/favicon/site.webmanifest" />
    <?= $analyticsHead ?>
    <?php if ($metaDescription !== ''): ?>
      <meta name="description" content="<?= $e($metaDescription) ?>" />
    <?php endif; ?>
    <?php /* Suffix skipped when the title already carries the site name (#909). */ ?>
    <title><?= $e(\NeNeRecords\PublicRecord\PublicDocumentTitle::compose($pageTitle, $siteName)) ?></title>
    <link rel="canonical" href="<?= $e($canonicalUrl) ?>" />
    <?php /* Paged archives: point crawlers along the sequence rather than at dead ends. */ ?>
    <?php if ($prevUrl !== null): ?>
      <link rel="prev" href="<?= $e($prevUrl) ?>" />
    <?php endif; ?>
    <?php if ($nextUrl !== null): ?>
      <link rel="next" href="<?= $e($nextUrl) ?>" />
    <?php endif; ?>

    <meta property="og:type" content="website" />
    <meta property="og:title" content="<?= $e($pageTitle) ?>" />
    <?php if ($metaDescription !== ''): ?>
      <meta property="og:description" content="<?= $e($metaDescription) ?>" />
    <?php endif; ?>
    <meta property="og:site_name" content="<?= $e($siteName) ?>" />
    <meta property="og:url" content="<?= $e($canonicalUrl) ?>" />
    <?php if ($ogImageUrl !== null): ?>
      <meta property="og:image" content="<?= $e($ogImageUrl) ?>" />
    <?php endif; ?>
    <meta name="twitter:card" content="<?= $ogImageUrl !== null ? 'summary_large_image' : 'summary' ?>" />
    <meta name="twitter:title" content="<?= $e($pageTitle) ?>" />
    <?php if ($ogImageUrl !== null): ?>
      <meta name="twitter:image" content="<?= $e($ogImageUrl) ?>" />
    <?php endif; ?>

    <style>
      .archive__list { margin: 1rem 0 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 0.75rem; }
      .archive__item { display: flex; flex-wrap: wrap; align-items: baseline; gap: 0.5rem 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e5e7eb; }
      .archive__date { color: #6b7280; font-size: 0.85rem; }
      .archive__empty { color: #6b7280; }
      .archive__pager { display: flex; gap: 1rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; }
      .archive__count { color: #6b7280; font-size: 0.85rem; }
    </style>
    <?php if ($spaMode === 'prod'): ?>
      <?php foreach ($spaCss as $href): ?>
      <link rel="stylesheet" crossorigin href="<?= $e($basePath . $href) ?>" />
      <?php endforeach; ?>
    <?php elseif ($spaMode === 'dev'): ?>
      <script type="module" src="<?= $e($viteUrl) ?>/@vite/client"></script>
    <?php endif; ?>
  </head>
  <body>
    <!-- SSR content lives inside #root: crawlers / no-JS read it; the SPA
         (PublicBrowsePage) replaces it with the interactive list on mount. -->
    <div id="root">
      <header>
        <h1><?= $e($typeName) ?></h1>
        <p class="archive__count"><?= $e($countLabel) ?></p>
      </header>
      <?php if ($items === []): ?>
        <p class="archive__empty"><?= $e($emptyLabel) ?></p>
      <?php else: ?>
        <ul class="archive__list">
          <?php foreach ($items as $item): ?>
            <li class="archive__item">
              <a href="<?= $e($basePath . $item->path) ?>"><?= $e($item->label) ?></a>
              <?php if ($item->publishedAt !== null): ?>
                <time class="archive__date" datetime="<?= $e($item->publishedAt->format(DATE_ATOM)) ?>">
                  <?= $e($item->publishedAt->format('Y-m-d')) ?>
                </time>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <?php if ($prevUrl !== null || $nextUrl !== null): ?>
        <nav class="archive__pager" aria-label="Pagination">
          <?php if ($prevUrl !== null): ?>
            <a rel="prev" href="<?= $e($prevUrl) ?>">← <?= $e($prevLabel) ?></a>
          <?php endif; ?>
          <?php if ($nextUrl !== null): ?>
            <a rel="next" href="<?= $e($nextUrl) ?>"><?= $e($nextLabel) ?> →</a>
          <?php endif; ?>
        </nav>
      <?php endif; ?>
    </div>
    <?php if ($spaMode === 'dev'): ?>
      <script type="module" src="<?= $e($viteUrl) ?>/src/main.tsx"></script>
    <?php elseif ($spaMode === 'prod' && $spaJs !== null): ?>
      <?php foreach ($spaPreload as $href): ?>
      <link rel="modulepreload" crossorigin href="<?= $e($basePath . $href) ?>" />
      <?php endforeach; ?>
      <script type="module" crossorigin src="<?= $e($basePath . $spaJs) ?>"></script>
    <?php endif; ?>
    <?php /* First-party floating CTA chrome (#982): outside #root; '' when disabled. */ ?>
    <?= $floatingCta ?? '' ?>
  </body>
</html>
