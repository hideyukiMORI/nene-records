<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Security fix: remove the default-credential seed users that
 * 20260525000000_seed_default_users created (admin@/editor@nene-records.local,
 * password "nene1234"). Those credentials are public (repo + README) and the
 * seed runs in EVERY environment, so they shipped to production as a
 * known-password admin backdoor. This deletes them everywhere, and — running
 * after the seed on a fresh DB — keeps fresh installs free of them too. Dev
 * logins should be created via the installer, never baked into a migration.
 */
final class DeleteDefaultSeedUsers extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('users')) {
            return;
        }

        // Only the unused, org-less seed accounts — never a real per-org user
        // who might happen to reuse one of these addresses.
        $this->execute(
            "DELETE FROM users
              WHERE email IN ('admin@nene-records.local', 'editor@nene-records.local')
                AND organization_id IS NULL",
        );
    }

    public function down(): void
    {
        // Intentionally irreversible: re-creating a known-password admin would
        // reopen the security hole. Create users via the installer if needed.
    }
}
