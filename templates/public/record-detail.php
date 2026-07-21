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
    <?php /* Suffix skipped when the title already carries the site name (#909). */ ?>
    <title><?= $e(\NeNeRecords\PublicRecord\PublicDocumentTitle::compose($pageTitle, $siteName)) ?></title>
    <link rel="canonical" href="<?= $e($canonicalUrl) ?>" />
    <?php /* hreflang alternates for the public content locales (#540). */ ?>
    <?php foreach ($alternateLinks as $alt): ?>
      <link rel="alternate" hreflang="<?= $e($alt['hreflang']) ?>" href="<?= $e($alt['href']) ?>" />
    <?php endforeach; ?>

    <meta property="og:type" content="<?= $e($ogType) ?>" />
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
      // As the front page (og:type=website) the record is the site home, not a dated
      // article: type it WebPage and omit the publication dates (#701).
      $jsonLd = [
          '@context' => 'https://schema.org',
          '@type' => $ogType === 'website' ? 'WebPage' : 'BlogPosting',
          'headline' => $pageTitle,
          'url' => $canonicalUrl,
          'mainEntityOfPage' => $canonicalUrl,
          'publisher' => $organizationLd,
      ];
if ($metaDescription !== '') {
    $jsonLd['description'] = $metaDescription;
}
if ($ogType !== 'website' && $publishedAtIso !== null) {
    $jsonLd['datePublished'] = $publishedAtIso;
}
if ($ogType !== 'website' && $updatedAtIso !== null) {
    $jsonLd['dateModified'] = $updatedAtIso;
}
if ($ogImageUrl !== null) {
    $jsonLd['image'] = $ogImageUrl;
}
?>
    <script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
    <?php /* Standalone Organization on the front page — Google's company knowledge-panel
             signal (logo/sameAs/contactPoint from settings); article pages carry it as
             the publisher above instead (#978). */ ?>
    <?php if ($ogType === 'website'): ?>
    <?php $organizationStandalone = array_merge(['@context' => 'https://schema.org'], $organizationLd); ?>
    <script type="application/ld+json"><?= json_encode($organizationStandalone, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
    <?php endif; ?>
    <?php /* BreadcrumbList JSON-LD (#651 PR2): the path-hierarchy signal Google actually ranks on. */ ?>
    <?php if ($breadcrumbs !== []): ?>
    <?php
  $breadcrumbItems = [[
      '@type' => 'ListItem',
      'position' => 1,
      'name' => $siteName,
      'item' => $siteOrigin . $basePath . '/',
  ]];
        $breadcrumbPosition = 2;
        foreach ($breadcrumbs as $crumb) {
            $breadcrumbItem = [
                '@type' => 'ListItem',
                'position' => $breadcrumbPosition,
                'name' => $crumb->label,
            ];
            if ($crumb->path !== null) {
                $breadcrumbItem['item'] = $siteOrigin . $basePath . $crumb->path;
            }
            $breadcrumbItems[] = $breadcrumbItem;
            $breadcrumbPosition++;
        }
        $breadcrumbLd = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $breadcrumbItems,
        ];
        ?>
    <script type="application/ld+json"><?= json_encode($breadcrumbLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
    <?php endif; ?>
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
      .breadcrumb ol { display: flex; flex-wrap: wrap; gap: 0.25rem 0.5rem; margin: 0 0 1rem; padding: 0; list-style: none; font-size: 0.85rem; color: #6b7280; }
      .breadcrumb li { display: flex; align-items: center; gap: 0.5rem; }
      .breadcrumb li:not(:last-child)::after { content: "/"; color: #9ca3af; }
      .child-pages { margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; }
      .child-pages ul { margin: 0.5rem 0 0; padding-left: 1.25rem; }
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
    <?php
      /*
       * Layout scaffold (#879). `bare` = "no header/footer, no theme — fully custom
       * page" and `custom` = a bundle host: the SPA renders neither the article chrome
       * (backlink / title) nor per-field labels for them, so the SSR must not either.
       * Emitting them anyway made every navigation flash the standard layout and — far
       * worse — left it as the *only* thing crawlers ever saw.
       */
      $bareLayout = $layout === 'bare';
      $customLayout = $layout === 'custom';
      $showArticleChrome = !$bareLayout && !$customLayout;
      /* `bare` owns the whole page: even the path chrome is the author's job. */
      $showPathChrome = !$bareLayout;
    ?>
    <?php /* Permalink-path breadcrumb (#651 PR2): populated only on custom-permalink pages. */ ?>
    <?php if ($showPathChrome && $breadcrumbs !== []): ?>
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <ol>
          <li><a href="<?= $e($basePath) ?>/"><?= $e($siteName) ?></a></li>
          <?php foreach ($breadcrumbs as $crumb): ?>
            <li>
              <?php if ($crumb->current || $crumb->path === null): ?>
                <span<?= $crumb->current ? ' aria-current="page"' : '' ?>><?= $e($crumb->label) ?></span>
              <?php else: ?>
                <a href="<?= $e($basePath . $crumb->path) ?>"><?= $e($crumb->label) ?></a>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ol>
      </nav>
    <?php endif; ?>
    <?php if ($showArticleChrome): ?>
    <header>
      <p><a href="<?= $e($basePath) ?>/view/<?= $e($entityTypeSlug) ?>">← <?= $e($entityTypeName) ?></a></p>
      <h1><?= $e($pageTitle) ?></h1>
    </header>
    <?php endif; ?>
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
            <?php /* The SPA renders body prose with no field-key label (it reads as an
                     article, not a labelled definition row). Match that where the SPA
                     drops the article chrome, so the SSR and the mounted DOM agree (#879). */ ?>
            <?php if ($showArticleChrome): ?><h2><?= $e($field->fieldKey) ?></h2><?php endif; ?>
            <div class="markdown-body"><?= $renderHtml($field->displayValue) ?></div>
          </section>
        <?php elseif ($field->fieldKey === 'body'): ?>
          <section>
            <?php if ($showArticleChrome): ?><h2><?= $e($field->fieldKey) ?></h2><?php endif; ?>
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
    <?php /* Section child pages (#651 PR2): a section parent lists its direct children. */ ?>
    <?php if ($showPathChrome && $childPages !== []): ?>
      <nav class="child-pages" aria-label="In this section">
        <h2>In this section</h2>
        <ul>
          <?php foreach ($childPages as $child): ?>
            <li><a href="<?= $e($basePath . $child->path) ?>"><?= $e($child->title) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </nav>
    <?php endif; ?>
    <?php /* Derived chapter navigation (#novel): only on a chapter of a multi-chapter work. */ ?>
    <?php if ($showPathChrome && $chapterNav !== null): ?>
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
    <?php /* Trusted-embed widgets (#802): validated <script> tags, allowlist-gated
             server-side (TrustedEmbedScripts). Emitted inside a <noscript> shell so
             the SPA (live runtime) owns execution — never a double-load. '' when the
             org has no allowlist / no trusted-embed widgets, keeping the page unchanged. */ ?>
    <?= $embedScripts ?>
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
    <?php /* First-party floating CTA chrome (#982): rendered outside #root so the SPA
             mount does not wipe it; '' when disabled / no match. Pre-escaped server-side. */ ?>
    <?= $floatingCta ?? '' ?>
  </body>
</html>
