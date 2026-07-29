<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Per-entity-type SEO page kind — `webpage` or `article` (#1020).
 *
 * Until now the public SSR typed every record that was not the pinned front page as
 * `og:type=article` + JSON-LD `BlogPosting`, so contact pages and company profiles
 * claimed to be dated articles (and carried a `datePublished` they do not have).
 *
 * **This migration deliberately changes nothing about the rendered output.** The
 * column default is `webpage` — that is the default for *newly created* types, where
 * the safer mistake is to under-claim rather than to assert a publication date that
 * does not exist. But every **existing** row is backfilled to `article`, which is
 * exactly what the code did before, so `migrate` is output-neutral on every install.
 *
 * Why not infer the right value from the slug (`pages` → webpage, `posts` → article)?
 * Two reasons. It would silently rewrite live structured data as a side effect of
 * deploying — including aozora's 1,114 `work` records, whose correct schema.org type
 * is explicitly *undecided* (#1022 tracks whether `Book`/`CreativeWork` fits better);
 * baking `article` in here would turn that open question into an accomplished fact.
 * And it would encode "a slug's name implies its meaning" into a migration, which
 * cannot be validated against data that does not exist yet: an org running a blog
 * under the slug `pages` would break, and nobody would know when. Migrations should
 * be mechanical — an inference baked into one is unfalsifiable by construction.
 *
 * So the correct value is chosen per type in the admin UI, where the change is
 * visible, reversible, and attributable. Production has 17 types in total.
 */
final class AddSeoPageKindToEntityTypes extends AbstractMigration
{
    public function up(): void
    {
        $this->table('entity_types')
            ->addColumn('seo_page_kind', 'string', [
                'limit' => 16,
                'null' => false,
                'default' => 'webpage',
            ])
            ->update();

        // Preserve current behaviour for everything that already exists: before this
        // column, every non-front-page record rendered as an article.
        $this->execute("UPDATE entity_types SET seo_page_kind = 'article'");
    }

    public function down(): void
    {
        $this->table('entity_types')->removeColumn('seo_page_kind')->update();
    }
}
