<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PreviewToken;

use Nene2\Http\UtcClock;
use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityNotFoundException;
use NeNeRecords\PreviewToken\GeneratePreviewTokenInput;
use NeNeRecords\PreviewToken\GeneratePreviewTokenUseCase;
use NeNeRecords\PreviewToken\RevokePreviewTokenInput;
use NeNeRecords\PreviewToken\RevokePreviewTokenUseCase;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use PHPUnit\Framework\TestCase;

final class PreviewTokenUseCaseTest extends TestCase
{
    public function testGenerateCreatesTokenForExistingEntity(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $previewTokens = new InMemoryEntityPreviewTokenRepository();
        $useCase = new GeneratePreviewTokenUseCase($entities, $previewTokens, new UtcClock());

        $output = $useCase->execute(new GeneratePreviewTokenInput(entityId: $entityId));

        self::assertSame('/api/v1/public/preview/' . $output->token, $output->previewUrl);
        self::assertNotSame('', $output->token);
        self::assertNotSame('', $output->expiresAtIso);
    }

    public function testGenerateThrowsEntityNotFoundExceptionWhenEntityDoesNotExist(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $previewTokens = new InMemoryEntityPreviewTokenRepository();
        $useCase = new GeneratePreviewTokenUseCase($entities, $previewTokens, new UtcClock());

        $this->expectException(EntityNotFoundException::class);

        $useCase->execute(new GeneratePreviewTokenInput(entityId: 999));
    }

    public function testGenerateThrowsEntityNotFoundExceptionWhenEntityIsDeleted(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));
        $entities->softDelete($entityId);

        $previewTokens = new InMemoryEntityPreviewTokenRepository();
        $useCase = new GeneratePreviewTokenUseCase($entities, $previewTokens, new UtcClock());

        $this->expectException(EntityNotFoundException::class);

        $useCase->execute(new GeneratePreviewTokenInput(entityId: $entityId));
    }

    public function testGenerateRevokesExistingTokenBeforeCreatingNew(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $previewTokens = new InMemoryEntityPreviewTokenRepository();
        $useCase = new GeneratePreviewTokenUseCase($entities, $previewTokens, new UtcClock());

        $first = $useCase->execute(new GeneratePreviewTokenInput(entityId: $entityId));
        $second = $useCase->execute(new GeneratePreviewTokenInput(entityId: $entityId));

        // Only the second token should exist — one token per entity
        self::assertNull($previewTokens->findByToken($first->token));
        $secondToken = $previewTokens->findByToken($second->token);
        self::assertNotNull($secondToken);
        self::assertSame($entityId, $secondToken->entityId);
    }

    public function testRevokeRemovesTokenForExistingEntity(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $previewTokens = new InMemoryEntityPreviewTokenRepository();

        $generate = new GeneratePreviewTokenUseCase($entities, $previewTokens, new UtcClock());
        $generated = $generate->execute(new GeneratePreviewTokenInput(entityId: $entityId));

        self::assertNotNull($previewTokens->findByToken($generated->token));

        $revoke = new RevokePreviewTokenUseCase($entities, $previewTokens);
        $revoke->execute(new RevokePreviewTokenInput(entityId: $entityId));

        self::assertNull($previewTokens->findByToken($generated->token));
    }

    public function testRevokeThrowsEntityNotFoundExceptionWhenEntityDoesNotExist(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $previewTokens = new InMemoryEntityPreviewTokenRepository();
        $useCase = new RevokePreviewTokenUseCase($entities, $previewTokens);

        $this->expectException(EntityNotFoundException::class);

        $useCase->execute(new RevokePreviewTokenInput(entityId: 999));
    }
}
