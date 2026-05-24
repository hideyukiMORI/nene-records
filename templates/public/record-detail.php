<!doctype html>
<html lang="ja">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $e($pageTitle) ?> — <?= $e($entityTypeName) ?></title>
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
            <div><?= nl2br($e($field->displayValue)) ?></div>
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
