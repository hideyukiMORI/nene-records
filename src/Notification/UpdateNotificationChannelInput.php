<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

final readonly class UpdateNotificationChannelInput
{
    /** @param array<string,mixed> $config */
    public function __construct(
        public int $id,
        public string $label,
        public bool $isEnabled,
        public array $config,
    ) {
    }
}
