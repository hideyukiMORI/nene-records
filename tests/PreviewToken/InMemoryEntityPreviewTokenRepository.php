<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PreviewToken;

use NeNeRecords\PreviewToken\EntityPreviewToken;
use NeNeRecords\PreviewToken\EntityPreviewTokenRepositoryInterface;

final class InMemoryEntityPreviewTokenRepository implements EntityPreviewTokenRepositoryInterface
{
    /** @var array<int, EntityPreviewToken> id => token */
    private array $tokens = [];

    private int $nextId = 1;

    public function findByToken(string $token): ?EntityPreviewToken
    {
        foreach ($this->tokens as $stored) {
            if ($stored->token === $token) {
                return $stored;
            }
        }

        return null;
    }

    public function findByEntityId(int $entityId): ?EntityPreviewToken
    {
        $found = null;

        foreach ($this->tokens as $stored) {
            if ($stored->entityId === $entityId) {
                if ($found === null || ($stored->id !== null && $found->id !== null && $stored->id > $found->id)) {
                    $found = $stored;
                }
            }
        }

        return $found;
    }

    public function save(EntityPreviewToken $token): EntityPreviewToken
    {
        $id = $this->nextId++;
        $saved = new EntityPreviewToken(
            id: $id,
            entityId: $token->entityId,
            token: $token->token,
            expiresAt: $token->expiresAt,
            createdAt: $token->createdAt,
        );
        $this->tokens[$id] = $saved;

        return $saved;
    }

    public function deleteByEntityId(int $entityId): void
    {
        foreach (array_keys($this->tokens) as $id) {
            if ($this->tokens[$id]->entityId === $entityId) {
                unset($this->tokens[$id]);
            }
        }
    }
}
