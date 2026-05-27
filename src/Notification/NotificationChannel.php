<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

/**
 * Persisted notification channel configuration.
 *
 * @phpstan-type ChannelType 'email'|'slack'|'discord'|'chatwork'|'webhook'
 */
final readonly class NotificationChannel
{
    /**
     * @param array<string,mixed> $config Decoded channel-specific settings
     */
    public function __construct(
        public int $id,
        public int $organizationId,
        public string $channelType,
        public string $label,
        public bool $isEnabled,
        public array $config,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }
}
