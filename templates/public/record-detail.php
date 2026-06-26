<!doctype html>
<html lang="<?= $e($htmlLang) ?>">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php /* Runtime base path: anchors the SPA (router/API derive it from <base>). #zip-install S2 */ ?>
    <base href="<?= $e($basePath . '/') ?>" />
    <?php /* GA4 / GTM + Consent Mode v2 — pre-built, nonce'd, '' when analytics is off. */ ?>
    <?= $analyticsHead ?>
    <?php if ($metaDescription !== ''): ?>
      <meta name="description" content="<?= $e($metaDescription) ?>" />
    <?php endif; ?>
    <title><?= $e($pageTitle) ?> — <?= $e($siteName) ?></title>
    <link rel="canonical" href="<?= $e($canonicalUrl) ?>" />
    <?php /* hreflang alternates for the public content locales (#540). */ ?>
    <?php foreach ($alternateLinks as $alt): ?>
      <link rel="alternate" hreflang="<?= $e($alt['hreflang']) ?>" href="<?= $e($alt['href']) ?>" />
    <?php endforeach; ?>

    <meta property="og:type" content="article" />
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
    <?php if ($metaDescription !== ''): ?>
      <meta name="twitter:description" content="<?= $e($metaDescription) ?>" />
    <?php endif; ?>
    <?php if ($ogImageUrl !== null): ?>
      <meta name="twitter:image" content="<?= $e($ogImageUrl) ?>" />
    <?php endif; ?>

    <?php
      $jsonLd = [
          '@context' => 'https://schema.org',
          '@type' => 'BlogPosting',
          'headline' => $pageTitle,
          'url' => $canonicalUrl,
          'mainEntityOfPage' => $canonicalUrl,
          'publisher' => ['@type' => 'Organization', 'name' => $siteName],
      ];
      if ($metaDescription !== '') {
          $jsonLd['description'] = $metaDescription;
      }
      if ($publishedAtIso !== null) {
          $jsonLd['datePublished'] = $publishedAtIso;
      }
      if ($updatedAtIso !== null) {
          $jsonLd['dateModified'] = $updatedAtIso;
      }
      if ($ogImageUrl !== null) {
          $jsonLd['image'] = $ogImageUrl;
      }
    ?>
    <script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
    <style>
      .markdown-body { display: flex; flex-direction: column; gap: 0.75rem; line-height: 1.6; }
      .markdown-body :is(h1, h2, h3, h4) { font-weight: 600; line-height: 1.3; }
      .markdown-body h2 { font-size: 1.25rem; }
      .markdown-body h3 { font-size: 1.125rem; }
      .markdown-body p { margin: 0; }
      .markdown-body ul, .markdown-body ol { margin: 0; padding-left: 1.25rem; }
      .markdown-body blockquote { margin: 0; padding-left: 0.75rem; border-left: 3px solid #d1d5db; color: #6b7280; }
      .chapter-nav { display: flex; flex-wrap: wrap; align-items: baseline; gap: 0.75rem 1rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; }
      .chapter-nav__pos { margin-left: auto; color: #6b7280; font-size: 0.9rem; }
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
         (createRoot().render) replaces it with the interactive app on mount. -->
    <div id="root">
    <header>
      <p><a href="<?= $e($basePath) ?>/view/<?= $e($entityTypeSlug) ?>">← <?= $e($entityTypeName) ?></a></p>
      <h1><?= $e($pageTitle) ?></h1>
    </header>
    <article>
      <?php foreach ($displayFields as $field): ?>
        <?php if ($field->dataType === 'relation'): ?>
          <section>
            <h2><?= $e($field->fieldKey) ?></h2>
            <?php if ($field->relationLinks === []): ?>
              <p>—</p>
            <?php else: ?>
              <ul>
                <?php foreach ($field->relationLinks as $link): ?>
                  <li><a href="<?= $e($basePath . $link['href']) ?>"><?= $e($link['label']) ?></a></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </section>
        <?php elseif ($field->dataType === 'html'): ?>
          <section>
            <h2><?= $e($field->fieldKey) ?></h2>
            <div class="markdown-body"><?= $renderHtml($field->displayValue) ?></div>
          </section>
        <?php elseif ($field->fieldKey === 'body'): ?>
          <section>
            <h2><?= $e($field->fieldKey) ?></h2>
            <div class="markdown-body"><?= $renderMarkdown($field->displayValue) ?></div>
          </section>
        <?php elseif ($field->dataType === 'bundle'): ?>
          <section class="bundle-seo">
            <div class="markdown-body"><?= $renderBundleSeo($field->displayValue) ?></div>
          </section>
        <?php else: ?>
          <section>
            <h2><?= $e($field->fieldKey) ?></h2>
            <p><?= $e($field->displayValue) ?></p>
          </section>
        <?php endif; ?>
      <?php endforeach; ?>
    </article>
    <?php /* Derived chapter navigation (#novel): only on a chapter of a multi-chapter work. */ ?>
    <?php if ($chapterNav !== null): ?>
      <nav class="chapter-nav" aria-label="章ナビゲーション">
        <?php if ($chapterNav->prevUrl !== null): ?>
          <a rel="prev" href="<?= $e($basePath . $chapterNav->prevUrl) ?>">← 前の章</a>
        <?php endif; ?>
        <a href="<?= $e($basePath . $chapterNav->indexUrl) ?>">目次</a>
        <?php if ($chapterNav->nextUrl !== null): ?>
          <a rel="next" href="<?= $e($basePath . $chapterNav->nextUrl) ?>">次の章 →</a>
        <?php endif; ?>
        <span class="chapter-nav__pos">第<?= $e((string) $chapterNav->chapterNo) ?>章 / 全<?= $e((string) $chapterNav->chapterTotal) ?>章</span>
      </nav>
    <?php endif; ?>
    </div>
    <script
      id="nene-records-public-record-bootstrap"
      type="application/json"
    ><?= $bootstrapJson ?></script>
    <?php if ($spaMode === 'dev'): ?>
      <script type="module" src="<?= $e($viteUrl) ?>/src/main.tsx"></script>
    <?php elseif ($spaMode === 'prod' && $spaJs !== null): ?>
      <?php foreach ($spaPreload as $href): ?>
      <link rel="modulepreload" crossorigin href="<?= $e($basePath . $href) ?>" />
      <?php endforeach; ?>
      <script type="module" crossorigin src="<?= $e($basePath . $spaJs) ?>"></script>
    <?php endif; ?>
  </body>
</html>
