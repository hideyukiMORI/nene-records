<?php

declare(strict_types=1);

namespace NeNeRecords\Webhook;

final readonly class Webhook
{
    /** @var list<string> */
    public array $events;

    /**
     * @param list<string> $events
     */
    public function __construct(
        public ?int $id,
        public string $url,
        array $events,
        public ?int $entityTypeId,
        public ?string $secret,
        public bool $isActive,
        public string $createdAt,
        public string $updatedAt,
    ) {
        $this->events = $events;
    }
}
