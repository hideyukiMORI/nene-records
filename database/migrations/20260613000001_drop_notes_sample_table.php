<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class DropNotesSampleTable extends AbstractMigration
{
    /**
     * `notes` is a leftover NENE2 sample table, unused by NeNe Records. Its
     * original migration file is already gone, leaving an orphaned phinxlog
     * entry; drop the table and clear that entry so `status` is clean.
     *
     * Uses up()/down() (not change()) because the phinxlog cleanup is not
     * auto-reversible.
     */
    public function up(): void
    {
        $this->execute('DROP TABLE IF EXISTS notes');
        $this->execute("DELETE FROM phinxlog WHERE version = '20260516000000'");
    }

    public function down(): void
    {
        // Recreate the sample table shape so the migration is reversible.
        $this->table('notes')
            ->addColumn('title', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('body', 'text', ['null' => false])
            ->create();
    }
}
