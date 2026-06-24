<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMenusTableAndBackfill extends AbstractMigration
{
    /**
     * Introduces named menus. `navigation_items.location` is kept for now so the
     * existing frontend keeps working; a later migration drops it once the UI
     * moves to menu_id. Existing items are grouped by (org, location) into a menu
     * each, with the menu carrying the header/footer display location (side → no
     * auto location; surfaced via a menu widget).
     */
    public function up(): void
    {
        $this->table('menus')
            ->addColumn('organization_id', 'integer', ['null' => false, 'default' => 0, 'signed' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 255, 'null' => false])
            // header | footer | null (null = standalone, shown via a menu widget)
            ->addColumn('location', 'string', ['limit' => 16, 'null' => true, 'default' => null])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->addIndex(['organization_id'])
            ->addIndex(['organization_id', 'slug'], ['unique' => true])
            ->create();

        $this->table('navigation_items')
            ->addColumn('menu_id', 'integer', ['null' => true, 'default' => null])
            ->addIndex(['menu_id'])
            ->update();

        $this->backfillMenus();
    }

    public function down(): void
    {
        $this->table('navigation_items')->removeColumn('menu_id')->update();
        $this->table('menus')->drop()->save();
    }

    private function backfillMenus(): void
    {
        // organization_id is added to navigation_items by a later-versioned migration;
        // on a fresh (version-ordered) DB it does not exist yet and there are no nav
        // items to backfill — skip to stay forward-reference-free.
        if (!$this->table('navigation_items')->hasColumn('organization_id')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $labels = ['header' => 'Header', 'footer' => 'Footer', 'side' => 'Side'];
        $connection = $this->getAdapter()->getConnection();

        /** @var list<array{organization_id: int|string, location: string}> $buckets */
        $buckets = $this->fetchAll('SELECT DISTINCT organization_id, location FROM navigation_items');

        foreach ($buckets as $bucket) {
            $orgId = (int) $bucket['organization_id'];
            $location = (string) $bucket['location'];
            $name = $labels[$location] ?? ucfirst($location);
            // Menus auto-display only in header/footer; side becomes standalone.
            $menuLocation = in_array($location, ['header', 'footer'], true) ? $location : null;

            $this->execute(sprintf(
                'INSERT INTO menus (organization_id, name, slug, location, created_at, updated_at) VALUES (%d, %s, %s, %s, %s, %s)',
                $orgId,
                $connection->quote($name),
                $connection->quote($location),
                $menuLocation === null ? 'NULL' : $connection->quote($menuLocation),
                $connection->quote($now),
                $connection->quote($now),
            ));

            $menuId = (int) $connection->lastInsertId();

            $this->execute(sprintf(
                'UPDATE navigation_items SET menu_id = %d WHERE organization_id = %d AND location = %s',
                $menuId,
                $orgId,
                $connection->quote($location),
            ));
        }
    }
}
