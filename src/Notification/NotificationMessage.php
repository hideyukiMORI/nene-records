<?php

declare(strict_types=1);

namespace NeNeRecords\Notification;

/**
 * Channel-agnostic notification payload.
 * Channels format this into their specific wire format (JSON/text/HTML).
 */
final readonly class NotificationMessage
{
    /**
     * @param string      $event   Machine-readable event key (e.g. "comment.submitted")
     * @param string      $title   Short human-readable summary
     * @param string      $body    Full message text (plain text)
     * @param string|null $url     Optional deep-link URL to the related resource
     */
    public function __construct(
        public string $event,
        public string $title,
        public string $body,
        public string|null $url = null,
    ) {
    }
}
