<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddEmailVerificationColumns extends AbstractMigration
{
    public function up(): void
    {
        // Pending email change verification (#283): the new address and a hashed,
        // time-limited token live here until the recipient confirms via the new address.
        $this->execute('
            ALTER TABLE users
                ADD COLUMN pending_email VARCHAR(255) NULL DEFAULT NULL AFTER password_reset_expires_at,
                ADD COLUMN email_verification_token_hash VARCHAR(64) NULL DEFAULT NULL AFTER pending_email,
                ADD COLUMN email_verification_expires_at DATETIME NULL DEFAULT NULL AFTER email_verification_token_hash
        ');
    }

    public function down(): void
    {
        $this->execute('
            ALTER TABLE users
                DROP COLUMN email_verification_expires_at,
                DROP COLUMN email_verification_token_hash,
                DROP COLUMN pending_email
        ');
    }
}
