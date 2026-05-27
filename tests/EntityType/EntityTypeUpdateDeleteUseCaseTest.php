<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\EntityType;

use NeNeRecords\Entity\Entity;
use NeNeRecords\EntityType\DeleteEntityTypeInput;
use NeNeRecords\EntityType\DeleteEntityTypeUseCase;
use NeNeRecords\EntityType\EntityType;
use NeNeRecords\EntityType\EntityTypeHasEntitiesException;
use NeNeRecords\EntityType\EntityTypeNotFoundException;
use NeNeRecords\EntityType\EntityTypeSlugConflictException;
use NeNeRecords\EntityType\UpdateEntityTypeInput;
use NeNeRecords\EntityType\UpdateEntityTypeUseCase;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\EntityArchive\InMemoryEntityArchiveRepository;
use PHPUnit\Framework\TestCase;

final class EntityTypeUpdateDeleteUseCaseTest extends TestCase
{
    public function testUpdateEntityTypeChangesNameSlugAndIsPinned(): void
    {
        $entityTypes = new InMemoryEntityTypeRepository([
            new EntityType(name: 'Post', slug: 'post', isPinned: false, id: 1),
        ]);
        $useCase = new UpdateEntityTypeUseCase($entityTypes);

        $output = $useCase->execute(new UpdateEntityTypeInput(
            id: 1,
            name: 'Article',
            slug: 'article',
            isPinned: true,
        ));

        self::assertSame(1, $output->id);
        self::assertSame('Article', $output->name);
        self::assertSame('article', $output->slug);
        self::assertSame(true, $output->isPinned);
    }

    public function testUpdateEntityTypeThrowsNotFoundWhenIdDoesNotExist(): void
    {
        $entityTypes = new InMemoryEntityTypeRepository([]);
        $useCase = new UpdateEntityTypeUseCase($entityTypes);

        $this->expectException(EntityTypeNotFoundException::class);

        $useCase->execute(new UpdateEntityTypeInput(id: 99, name: 'Ghost', slug: 'ghost'));
    }

    public function testUpdateEntityTypeThrowsSlugConflictOnDuplicateSlug(): void
    {
        $entityTypes = new InMemoryEntityTypeRepository([
            new EntityType(name: 'Post', slug: 'post', id: 1),
            new EntityType(name: 'Article', slug: 'article', id: 2),
        ]);
        $useCase = new UpdateEntityTypeUseCase($entityTypes);

        $this->expectException(EntityTypeSlugConflictException::class);

        // Try to rename entity type 1 to use the slug already owned by entity type 2.
        $useCase->execute(new UpdateEntityTypeInput(id: 1, name: 'Post', slug: 'article'));
    }

    public function testUpdateEntityTypeSavesOldPermalinkPatternAsPrevious(): void
    {
        $entityTypes = new InMemoryEntityTypeRepository([
            new EntityType(name: 'Post', slug: 'post', id: 1, permalinkPattern: '/{type}/{id}'),
        ]);
        $useCase = new UpdateEntityTypeUseCase($entityTypes);

        $output = $useCase->execute(new UpdateEntityTypeInput(
            id: 1,
            name: 'Post',
            slug: 'post',
            permalinkPattern: '/{type}/{slug}',
        ));

        self::assertSame('/{type}/{slug}', $output->permalinkPattern);
        self::assertSame('/{type}/{id}', $output->previousPermalinkPattern);
    }

    public function testDeleteEntityTypeSucceedsWhenNoEntitiesExist(): void
    {
        $entityTypes = new InMemoryEntityTypeRepository([
            new EntityType(name: 'Post', slug: 'post', id: 1),
        ]);
        $entities = new InMemoryEntityRepository([]);
        $entityArchive = new InMemoryEntityArchiveRepository();
        $useCase = new DeleteEntityTypeUseCase($entityTypes, $entities, $entityArchive);

        $useCase->execute(new DeleteEntityTypeInput(id: 1));

        self::assertNull($entityTypes->findById(1));
    }

    public function testDeleteEntityTypeThrowsNotFoundWhenIdDoesNotExist(): void
    {
        $entityTypes = new InMemoryEntityTypeRepository([]);
        $entities = new InMemoryEntityRepository([]);
        $entityArchive = new InMemoryEntityArchiveRepository();
        $useCase = new DeleteEntityTypeUseCase($entityTypes, $entities, $entityArchive);

        $this->expectException(EntityTypeNotFoundException::class);

        $useCase->execute(new DeleteEntityTypeInput(id: 99));
    }

    public function testDeleteEntityTypeThrowsHasEntitiesWhenActiveEntitiesExist(): void
    {
        $entityTypes = new InMemoryEntityTypeRepository([
            new EntityType(name: 'Post', slug: 'post', id: 1),
        ]);
        $entities = new InMemoryEntityRepository([]);
        $entities->save(new Entity(id: null, entityTypeId: 1));
        $entityArchive = new InMemoryEntityArchiveRepository();
        $useCase = new DeleteEntityTypeUseCase($entityTypes, $entities, $entityArchive);

        $this->expectException(EntityTypeHasEntitiesException::class);

        $useCase->execute(new DeleteEntityTypeInput(id: 1));
    }
}
