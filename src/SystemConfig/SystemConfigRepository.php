<?php

declare(strict_types=1);

namespace NeNeRecords\SystemConfig;

use Nene2\Database\DatabaseQueryExecutorInterface;

/**
 * Lightweight key-value store backed by the system_config table.
 */
final readonly class SystemConfigRepository
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function get(string $key): ?string
    {
        $row = $this->query->fetchOne('SELECT `value` FROM system_config WHERE `key` = ?', [$key]);
        if ($row === null) {
            return null;
        }

        return (string) $row['value'];
    }

    public function set(string $key, string $value): void
    {
        $this->query->execute(
            'INSERT INTO system_config (`key`, `value`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `updated_at` = NOW()',
            [$key, $value],
        );
    }

    /** @return array<string, string> */
    public function all(): array
    {
        $rows = $this->query->fetchAll('SELECT `key`, `value` FROM system_config ORDER BY `key`', []);
        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['key']] = (string) $row['value'];
        }

        return $result;
    }
}
