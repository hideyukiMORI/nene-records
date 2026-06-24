<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUrlRedirectsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('url_redirects')
            ->addColumn('organization_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('source_path', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('target_path', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('created_at', 'datetime', ['null' => false])
            // One redirect per (org, source path); supports upsert-on-import idempotency.
            ->addIndex(['organization_id', 'source_path'], ['unique' => true])
            ->create();
    }
}
