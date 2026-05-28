<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Durable queue for asynchronous webhook delivery (#285).
 *
 * Each row is one delivery for a single webhook. The dispatcher enqueues rows
 * (status=pending); a worker claims due rows, sends them, and reschedules with
 * backoff or marks them failed once attempts are exhausted. The target URL and
 * secret are snapshotted so in-flight deliveries are unaffected by later config changes.
 */
final class CreateWebhookDeliveriesTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('webhook_deliveries', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci'])
            ->addColumn('webhook_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('event', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('entity_type_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('entity_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('target_url', 'string', ['limit' => 2048, 'null' => false])
            ->addColumn('secret', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('payload', 'text', ['null' => false])
            ->addColumn('status', 'enum', ['values' => ['pending', 'delivered', 'failed'], 'null' => false, 'default' => 'pending'])
            ->addColumn('attempts', 'integer', ['signed' => false, 'null' => false, 'default' => 0])
            ->addColumn('max_attempts', 'integer', ['signed' => false, 'null' => false, 'default' => 5])
            ->addColumn('next_attempt_at', 'datetime', ['null' => false])
            ->addColumn('last_error', 'text', ['null' => true, 'default' => null])
            ->addColumn('response_status', 'integer', ['null' => true, 'default' => null])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->addColumn('delivered_at', 'datetime', ['null' => true, 'default' => null])
            // Worker claim query: WHERE status = 'pending' AND next_attempt_at <= NOW().
            ->addIndex(['status', 'next_attempt_at'], ['name' => 'idx_webhook_deliveries_due'])
            ->addIndex(['webhook_id'], ['name' => 'idx_webhook_deliveries_webhook'])
            ->create();
    }

    public function down(): void
    {
        $this->table('webhook_deliveries')->drop()->save();
    }
}
