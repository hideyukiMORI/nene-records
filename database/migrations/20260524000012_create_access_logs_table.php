<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAccessLogsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('access_logs')
            ->addColumn('request_id', 'string', ['limit' => 64, 'null' => true])
            ->addColumn('method', 'string', ['limit' => 10, 'null' => false])
            ->addColumn('path', 'string', ['limit' => 2048, 'null' => false])
            ->addColumn('status_code', 'integer', ['null' => false])
            ->addColumn('duration_ms', 'float', ['null' => false])
            ->addColumn('accessed_at', 'datetime', ['null' => false])
            ->addColumn('access_date', 'date', ['null' => false])
            ->addIndex(['access_date'])
            ->addIndex(['accessed_at'])
            ->create();
    }
}
