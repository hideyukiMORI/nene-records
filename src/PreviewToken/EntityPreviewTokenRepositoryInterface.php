<?php

declare(strict_types=1);

namespace NeNeRecords\PreviewToken;

interface EntityPreviewTokenRepositoryInterface
{
    public function findByToken(string $token): ?EntityPreviewToken;

    public function findByEntityId(int $entityId): ?EntityPreviewToken;

    public function save(EntityPreviewToken $token): EntityPreviewToken;

    public function deleteByEntityId(int $entityId): void;
}
