<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddLabelsToEntityTypes extends AbstractMigration
{
    public function up(): void
    {
        // labels stores JSON: {"ja":"投稿","fr":"Articles"} – locale-specific display names
        // Null means no overrides; the base `name` is used as fallback.
        $this->execute('ALTER TABLE entity_types ADD COLUMN labels TEXT NULL DEFAULT NULL');
    }

    public function down(): void
    {
        $this->execute('ALTER TABLE entity_types DROP COLUMN labels');
    }
}
