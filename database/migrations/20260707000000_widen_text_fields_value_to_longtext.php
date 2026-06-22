<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

/**
 * Widen `text_fields.value` from MySQL TEXT (~64KB) to LONGTEXT (#311 / #491 WS3-S3b).
 *
 * `bundle` fields (self-contained HTML/JS/CSS custom pages) share this column and
 * a real bundle easily exceeds 64KB; the old TEXT ceiling silently truncated/
 * rejected large values. LONGTEXT matches `blocks_fields.value`. Non-destructive
 * widening (existing values are preserved). The per-field size limit is enforced
 * by the application (e.g. {@see \NeNeRecords\BundleField\BundleDocumentValidator}).
 */
final class WidenTextFieldsValueToLongtext extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('text_fields')) {
            return;
        }

        $this->table('text_fields')
            ->changeColumn('value', 'text', ['null' => false, 'limit' => MysqlAdapter::TEXT_LONG])
            ->update();
    }

    public function down(): void
    {
        if (!$this->hasTable('text_fields')) {
            return;
        }

        $this->table('text_fields')
            ->changeColumn('value', 'text', ['null' => false])
            ->update();
    }
}
