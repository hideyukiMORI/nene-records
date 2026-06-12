<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMetadataColumnsToMedia extends AbstractMigration
{
    public function up(): void
    {
        $this->table('media')
            // Driver-agnostic storage key (e.g. "2026/06/abcdef.png"), so deletes
            // do not depend on reverse-parsing the public URL.
            ->addColumn('storage_key', 'string', ['limit' => 1024, 'null' => false, 'default' => '', 'after' => 'url'])
            ->addColumn('width', 'integer', ['null' => true, 'default' => null, 'after' => 'size'])
            ->addColumn('height', 'integer', ['null' => true, 'default' => null, 'after' => 'width'])
            ->addColumn('alt_text', 'string', ['limit' => 1024, 'null' => true, 'default' => null, 'after' => 'mime_type'])
            ->update();

        // Backfill storage_key for existing rows by stripping the "/media/" prefix
        // off the stored URL (the local-disk convention used so far).
        $pdo = $this->getAdapter()->getConnection();
        $rows = $pdo->query('SELECT id, url FROM media')->fetchAll(PDO::FETCH_ASSOC);
        $update = $pdo->prepare('UPDATE media SET storage_key = ? WHERE id = ?');

        foreach ($rows as $row) {
            $url = (string) $row['url'];
            $key = str_starts_with($url, '/media/') ? substr($url, strlen('/media/')) : ltrim($url, '/');
            $update->execute([$key, (int) $row['id']]);
        }
    }

    public function down(): void
    {
        $this->table('media')
            ->removeColumn('storage_key')
            ->removeColumn('width')
            ->removeColumn('height')
            ->removeColumn('alt_text')
            ->update();
    }
}
