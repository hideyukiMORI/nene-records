<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\SystemConfig;

use NeNeRecords\SystemConfig\SystemConfigRepositoryInterface;

final class InMemorySystemConfigRepository implements SystemConfigRepositoryInterface
{
    /** @var array<string, string> */
    private array $data;

    /** @param array<string, string> $data */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function get(string $key): ?string
    {
        return $this->data[$key] ?? null;
    }

    public function set(string $key, string $value): void
    {
        $this->data[$key] = $value;
    }

    /** @return array<string, string> */
    public function all(): array
    {
        return $this->data;
    }
}
