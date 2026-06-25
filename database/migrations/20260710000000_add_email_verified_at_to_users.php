<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddEmailVerifiedAtToUsers extends AbstractMigration
{
    public function up(): void
    {
        // Self-serve signup email verification: NULL = unverified, a timestamp =
        // the address has been confirmed. Appended (no positional AFTER) so it is
        // independent of other users-table migrations' column order.
        $this->execute('ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL DEFAULT NULL');

        // Grandfather every existing user as verified — they were provisioned by
        // install / invite (trusted), so only NEW self-serve signups start NULL.
        $this->execute('UPDATE users SET email_verified_at = NOW() WHERE email_verified_at IS NULL');
    }

    public function down(): void
    {
        $this->execute('ALTER TABLE users DROP COLUMN email_verified_at');
    }
}
