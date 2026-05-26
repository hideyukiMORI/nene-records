<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRateLimitsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('rate_limits', ['id' => false, 'primary_key' => ['key_hash']])
            ->addColumn('key_hash', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('count', 'integer', ['null' => false, 'signed' => false, 'default' => 1])
            ->addColumn('reset_at', 'integer', ['null' => false, 'signed' => false])
            ->create();
    }
}
