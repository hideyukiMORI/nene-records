<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Seed the `active_theme` public setting — the public site's selected theme id
 * (epic #367 / Phase 1 #370). Defaults to the built-in `consumer` theme. The
 * public frontend reads this and applies `[data-theme='<id>']`.
 */
final class AddActiveThemeSetting extends AbstractMigration
{
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->table('setting_defs')->insert([
            [
                'setting_key' => 'active_theme',
                'data_type' => 'text',
                'default_value' => 'consumer',
                'is_public' => 1,
                'label' => 'Public site theme',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ])->saveData();
    }

    public function down(): void
    {
        $this->execute("DELETE FROM setting_defs WHERE setting_key = 'active_theme'");
    }
}
