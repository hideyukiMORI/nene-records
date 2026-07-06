<?php

declare(strict_types=1);

namespace NeNeRecords\PreviewToken;

use DateTimeInterface;
use Nene2\Http\ClockInterface;
use NeNeRecords\Entity\EntityNotFoundException;
use NeNeRecords\Entity\EntityRepositoryInterface;

final readonly class GeneratePreviewTokenUseCase implements GeneratePreviewTokenUseCaseInterface
{
    private const TOKEN_TTL_SECONDS = 86400; // 24 hours

    public function __construct(
        private EntityRepositoryInterface $entities,
        private EntityPreviewTokenRepositoryInterface $previewTokens,
        private ClockInterface $clock,
    ) {
    }

    public function execute(GeneratePreviewTokenInput $input): GeneratePreviewTokenOutput
    {
        $entity = $this->entities->findById($input->entityId);

        if ($entity === null || $entity->isDeleted) {
            throw new EntityNotFoundException($input->entityId);
        }

        // Revoke any existing token for this entity before issuing a new one.
        $this->previewTokens->deleteByEntityId($input->entityId);

        $now = $this->clock->now();
        $expiresAt = $now->modify('+' . self::TOKEN_TTL_SECONDS . ' seconds');
        $token = bin2hex(random_bytes(32));

        $saved = $this->previewTokens->save(new EntityPreviewToken(
            id: null,
            entityId: $input->entityId,
            token: $token,
            expiresAt: $expiresAt,
            createdAt: $now,
        ));

        return new GeneratePreviewTokenOutput(
            token: $saved->token,
            expiresAtIso: $saved->expiresAt->format(DateTimeInterface::ATOM),
            previewUrl: '/api/v1/public/preview/' . $saved->token,
        );
    }
}
