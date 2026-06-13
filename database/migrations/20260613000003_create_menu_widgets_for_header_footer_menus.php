<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMenuWidgetsForHeaderFooterMenus extends AbstractMigration
{
    /**
     * Placement is unified on regions: header/footer are now widget regions, and
     * the public header/footer render their region widgets instead of reading
     * menu.location. To preserve existing chrome navigation, create a `menu`
     * widget (region = the menu's location) for every header/footer menu.
     */
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');
        $connection = $this->getAdapter()->getConnection();

        /** @var list<array{id: int|string, organization_id: int|string, location: string|null}> $menus */
        $menus = $this->fetchAll(
            "SELECT id, organization_id, location FROM menus WHERE location IN ('header', 'footer')",
        );

        foreach ($menus as $menu) {
            $menuId = (int) $menu['id'];
            $orgId = (int) $menu['organization_id'];
            $region = (string) $menu['location'];

            // Skip if a menu widget for this menu already exists in that region.
            $existing = $this->fetchRow(sprintf(
                "SELECT id FROM widgets WHERE organization_id = %d AND widget_type = 'menu' AND region = %s AND settings LIKE %s",
                $orgId,
                $connection->quote($region),
                $connection->quote('%"menuId":' . $menuId . '%'),
            ));
            if (!empty($existing)) {
                continue;
            }

            $settings = json_encode(['menuId' => $menuId], JSON_THROW_ON_ERROR);

            $this->execute(sprintf(
                'INSERT INTO widgets (organization_id, widget_type, region, display_order, title, settings, created_at, updated_at)
                 VALUES (%d, %s, %s, 0, NULL, %s, %s, %s)',
                $orgId,
                $connection->quote('menu'),
                $connection->quote($region),
                $connection->quote($settings),
                $connection->quote($now),
                $connection->quote($now),
            ));
        }
    }

    public function down(): void
    {
        // Best-effort: remove the menu widgets this migration could have created.
        $this->execute(
            "DELETE FROM widgets WHERE widget_type = 'menu' AND region IN ('header', 'footer') AND settings LIKE '%\"menuId\":%'",
        );
    }
}
