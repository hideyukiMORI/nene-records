<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUserManagementColumns extends AbstractMigration
{
    public function up(): void
    {
        // Add status, invite token, and password reset token columns to users table
        $this->execute("
            ALTER TABLE users
                ADD COLUMN status ENUM('active', 'invited') NOT NULL DEFAULT 'active' AFTER role,
                ADD COLUMN invite_token_hash VARCHAR(64) NULL DEFAULT NULL AFTER status,
                ADD COLUMN invite_expires_at DATETIME NULL DEFAULT NULL AFTER invite_token_hash,
                ADD COLUMN password_reset_token_hash VARCHAR(64) NULL DEFAULT NULL AFTER invite_expires_at,
                ADD COLUMN password_reset_expires_at DATETIME NULL DEFAULT NULL AFTER password_reset_token_hash
        ");
    }

    public function down(): void
    {
        $this->execute('
            ALTER TABLE users
                DROP COLUMN password_reset_expires_at,
                DROP COLUMN password_reset_token_hash,
                DROP COLUMN invite_expires_at,
                DROP COLUMN invite_token_hash,
                DROP COLUMN status
        ');
    }
}
