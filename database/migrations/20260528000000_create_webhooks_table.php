<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateWebhooksTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('webhooks', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci'])
            ->addColumn('url', 'string', ['limit' => 2048, 'null' => false])
            ->addColumn('events', 'text', ['null' => false, 'comment' => 'JSON array: ["entity.created","entity.updated","entity.deleted"]'])
            ->addColumn('entity_type_id', 'integer', ['signed' => false, 'null' => true, 'default' => null, 'comment' => 'NULL = all entity types'])
            ->addColumn('secret', 'string', ['limit' => 255, 'null' => true, 'default' => null, 'comment' => 'HMAC-SHA256 signing secret'])
            ->addColumn('is_active', 'boolean', ['null' => false, 'default' => true])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->create();
    }

    public function down(): void
    {
        $this->table('webhooks')->drop()->save();
    }
}
