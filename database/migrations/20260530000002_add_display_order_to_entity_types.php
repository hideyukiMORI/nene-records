<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDisplayOrderToEntityTypes extends AbstractMigration
{
    public function up(): void
    {
        $this->table('entity_types')
            ->addColumn('display_order', 'integer', ['null' => false, 'default' => 0])
            ->update();

        // organization_id is added by a later-versioned migration; on a fresh
        // (version-ordered) DB it does not exist yet and entity_types is empty, so
        // the per-org backfill is a no-op — skip it to stay forward-reference-free.
        if (!$this->table('entity_types')->hasColumn('organization_id')) {
            return;
        }

        // Backfill so the current (id-ascending) order is preserved per organization.
        $pdo = $this->getAdapter()->getConnection();
        $rows = $pdo->query(
            'SELECT id, organization_id FROM entity_types ORDER BY organization_id ASC, id ASC',
        )->fetchAll(PDO::FETCH_ASSOC);

        $orderByOrg = [];
        $update = $pdo->prepare('UPDATE entity_types SET display_order = ? WHERE id = ?');
        foreach ($rows as $row) {
            $orgId = (int) $row['organization_id'];
            $next = $orderByOrg[$orgId] ?? 0;
            $update->execute([$next, (int) $row['id']]);
            $orderByOrg[$orgId] = $next + 1;
        }
    }

    public function down(): void
    {
        $this->table('entity_types')->removeColumn('display_order')->update();
    }
}
