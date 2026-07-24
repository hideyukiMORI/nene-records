<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Path B visitor analytics — privacy-first columns on access_logs + daily salt store.
 *
 * See ADR 0006 (visitor analytics privacy model). All new columns are NULLable and only
 * populated when an org opts in (`analytics_visitor_tracking`); existing rows and the
 * default behaviour are unchanged. No raw client IP is ever stored: `visitor_hash` is a
 * daily-salted, org-scoped SHA-256 whose salt lives in `analytics_salts` and rotates per
 * calendar day, so past days cannot be re-identified once salts are pruned.
 *
 * - visitor_hash  : lowercase hex sha256(daily_salt || client_ip || ':' || org_id)
 * - referer_host  : host only (never the full referer URL — it can carry PII/query)
 * - utm_* / ref   : campaign + outreach attribution only (raw query strings are never
 *                   stored — the beacon may send the full query, but the server persists
 *                   only this allowlist and discards the rest)
 * - client_type   : derived UA class (never the raw UA); is_bot enables bot filtering
 *
 * `analytics_salts` holds one 32-byte random salt per day (get-or-create at first use),
 * pruned on the same retention window as the visitor rows.
 */
final class AddVisitorColumnsToAccessLogs extends AbstractMigration
{
    public function change(): void
    {
        $this->table('access_logs')
            ->addColumn('visitor_hash', 'string', ['limit' => 64, 'null' => true, 'default' => null])
            ->addColumn('referer_host', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('utm_source', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('utm_medium', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('utm_campaign', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('ref', 'string', ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('client_type', 'enum', ['values' => ['bot', 'mobile', 'desktop', 'other'], 'null' => true, 'default' => null])
            ->addColumn('is_bot', 'boolean', ['null' => true, 'default' => null])
            ->addIndex(['access_date', 'visitor_hash'], ['name' => 'idx_access_logs_date_visitor'])
            ->update();

        $this->table('analytics_salts', ['id' => false, 'primary_key' => ['salt_date']])
            ->addColumn('salt_date', 'date', ['null' => false])
            ->addColumn('salt', 'varbinary', ['limit' => 32, 'null' => false])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->create();
    }
}
