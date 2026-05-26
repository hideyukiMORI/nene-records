<?php

declare(strict_types=1);

namespace NeNeRecords\PreviewToken;

use DateTimeImmutable;
use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoEntityPreviewTokenRepository implements EntityPreviewTokenRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function findByToken(string $token): ?EntityPreviewToken
    {
        $row = $this->query->fetchOne(
            'SELECT id, entity_id, token, expires_at, created_at
             FROM entity_preview_tokens
             WHERE token = ?',
            [$token],
        );

        return $row === null ? null : $this->mapRow($row);
    }

    public function findByEntityId(int $entityId): ?EntityPreviewToken
    {
        $row = $this->query->fetchOne(
            'SELECT id, entity_id, token, expires_at, created_at
             FROM entity_preview_tokens
             WHERE entity_id = ?
             ORDER BY id DESC
             LIMIT 1',
            [$entityId],
        );

        return $row === null ? null : $this->mapRow($row);
    }

    public function save(EntityPreviewToken $token): EntityPreviewToken
    {
        $this->query->execute(
            'INSERT INTO entity_preview_tokens (entity_id, token, expires_at, created_at)
             VALUES (?, ?, ?, ?)',
            [
                $token->entityId,
                $token->token,
                $token->expiresAt->format('Y-m-d H:i:s'),
                $token->createdAt->format('Y-m-d H:i:s'),
            ],
        );

        $id = $this->query->lastInsertId();

        return new EntityPreviewToken(
            id: $id,
            entityId: $token->entityId,
            token: $token->token,
            expiresAt: $token->expiresAt,
            createdAt: $token->createdAt,
        );
    }

    public function deleteByEntityId(int $entityId): void
    {
        $this->query->execute(
            'DELETE FROM entity_preview_tokens WHERE entity_id = ?',
            [$entityId],
        );
    }

    /** @param array<string, mixed> $row */
    private function mapRow(array $row): EntityPreviewToken
    {
        return new EntityPreviewToken(
            id: (int) $row['id'],
            entityId: (int) $row['entity_id'],
            token: (string) $row['token'],
            expiresAt: new DateTimeImmutable((string) $row['expires_at']),
            createdAt: new DateTimeImmutable((string) $row['created_at']),
        );
    }
}
