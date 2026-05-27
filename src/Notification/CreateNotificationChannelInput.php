<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

final readonly class CreateNotificationChannelInput
{
    /** @param array<string,mixed> $config */
    public function __construct(
        public string $channelType,
        public string $label,
        public bool $isEnabled,
        public array $config,
    ) {
    }
}
