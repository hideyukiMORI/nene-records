<?php

declare(strict_types=1);

namespace NeNeRecords\PreviewToken;

use DateTimeImmutable;

final readonly class EntityPreviewToken
{
    public function __construct(
        public ?int $id,
        public int $entityId,
        public string $token,
        public DateTimeImmutable $expiresAt,
        public DateTimeImmutable $createdAt,
    ) {
    }
}
