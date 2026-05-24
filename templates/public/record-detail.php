<!doctype html>
<html lang="ja">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php if ($metaDescription !== ''): ?>
      <meta name="description" content="<?= $e($metaDescription) ?>" />
    <?php endif; ?>
    <title><?= $e($pageTitle) ?> — <?= $e($siteName) ?></title>
    <style>
      .markdown-body { display: flex; flex-direction: column; gap: 0.75rem; line-height: 1.6; }
      .markdown-body :is(h1, h2, h3, h4) { font-weight: 600; line-height: 1.3; }
      .markdown-body h2 { font-size: 1.25rem; }
      .markdown-body h3 { font-size: 1.125rem; }
      .markdown-body p { margin: 0; }
      .markdown-body ul, .markdown-body ol { margin: 0; padding-left: 1.25rem; }
      .markdown-body blockquote { margin: 0; padding-left: 0.75rem; border-left: 3px solid #d1d5db; color: #6b7280; }
    </style>
    <?php if ($includeViteClient): ?>
      <script type="module" src="<?= $e($viteUrl) ?>/@vite/client"></script>
    <?php endif; ?>
  </head>
  <body>
    <header>
      <p><a href="/view/<?= $e($entityTypeSlug) ?>">← <?= $e($entityTypeName) ?></a></p>
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
                  <li><a href="<?= $e($link['href']) ?>"><?= $e($link['label']) ?></a></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </section>
        <?php elseif ($field->fieldKey === 'body'): ?>
          <section>
            <h2><?= $e($field->fieldKey) ?></h2>
            <div class="markdown-body"><?= $renderMarkdown($field->displayValue) ?></div>
          </section>
        <?php else: ?>
          <section>
            <h2><?= $e($field->fieldKey) ?></h2>
            <p><?= $e($field->displayValue) ?></p>
          </section>
        <?php endif; ?>
      <?php endforeach; ?>
    </article>
    <script
      id="nene-records-public-record-bootstrap"
      type="application/json"
    ><?= $bootstrapJson ?></script>
    <?php if ($includeViteClient): ?>
      <div id="root"></div>
      <script type="module" src="<?= $e($viteUrl) ?>/src/main.tsx"></script>
    <?php endif; ?>
  </body>
</html>
