<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\EntityTag;

use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityNotFoundException;
use NeNeRecords\EntityTag\AttachEntityTagInput;
use NeNeRecords\EntityTag\AttachEntityTagUseCase;
use NeNeRecords\EntityTag\DetachEntityTagInput;
use NeNeRecords\EntityTag\DetachEntityTagUseCase;
use NeNeRecords\EntityTag\EntityTagAlreadyAttachedException;
use NeNeRecords\EntityTag\EntityTagNotAttachedException;
use NeNeRecords\Tag\Tag;
use NeNeRecords\Tag\TagNotFoundException;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\Tag\InMemoryTagRepository;
use PHPUnit\Framework\TestCase;

final class EntityTagUseCaseTest extends TestCase
{
    // AttachEntityTagUseCase tests

    public function testAttachEntityTagAttachesTagToEntity(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $tags = new InMemoryTagRepository([
            new Tag(slug: 'news', name: 'News', id: 1),
        ]);

        $entityTags = new InMemoryEntityTagRepository();
        $useCase = new AttachEntityTagUseCase($entities, $tags, $entityTags);

        $output = $useCase->execute(new AttachEntityTagInput(entityId: $entityId, tagId: 1));

        self::assertSame(1, $output->id);
        self::assertSame('news', $output->slug);
        self::assertSame('News', $output->name);
        self::assertTrue($entityTags->isAttached($entityId, 1));
    }

    public function testAttachEntityTagThrowsEntityNotFoundExceptionWhenEntityMissing(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $tags = new InMemoryTagRepository([
            new Tag(slug: 'news', name: 'News', id: 1),
        ]);
        $entityTags = new InMemoryEntityTagRepository();
        $useCase = new AttachEntityTagUseCase($entities, $tags, $entityTags);

        $this->expectException(EntityNotFoundException::class);

        $useCase->execute(new AttachEntityTagInput(entityId: 999, tagId: 1));
    }

    public function testAttachEntityTagThrowsTagNotFoundExceptionWhenTagMissing(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $tags = new InMemoryTagRepository([]);
        $entityTags = new InMemoryEntityTagRepository();
        $useCase = new AttachEntityTagUseCase($entities, $tags, $entityTags);

        $this->expectException(TagNotFoundException::class);

        $useCase->execute(new AttachEntityTagInput(entityId: $entityId, tagId: 99));
    }

    public function testAttachEntityTagThrowsEntityTagAlreadyAttachedExceptionWhenAlreadyAttached(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $tags = new InMemoryTagRepository([
            new Tag(slug: 'news', name: 'News', id: 1),
        ]);

        $entityTags = new InMemoryEntityTagRepository();
        $entityTags->attach($entityId, 1);

        $useCase = new AttachEntityTagUseCase($entities, $tags, $entityTags);

        $this->expectException(EntityTagAlreadyAttachedException::class);

        $useCase->execute(new AttachEntityTagInput(entityId: $entityId, tagId: 1));
    }

    // DetachEntityTagUseCase tests

    public function testDetachEntityTagDetachesTagFromEntity(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $entityTags = new InMemoryEntityTagRepository();
        $entityTags->attach($entityId, 1);

        $useCase = new DetachEntityTagUseCase($entities, $entityTags);

        $useCase->execute(new DetachEntityTagInput(entityId: $entityId, tagId: 1));

        self::assertFalse($entityTags->isAttached($entityId, 1));
    }

    public function testDetachEntityTagThrowsEntityNotFoundExceptionWhenEntityMissing(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityTags = new InMemoryEntityTagRepository();
        $useCase = new DetachEntityTagUseCase($entities, $entityTags);

        $this->expectException(EntityNotFoundException::class);

        $useCase->execute(new DetachEntityTagInput(entityId: 999, tagId: 1));
    }

    public function testDetachEntityTagThrowsEntityTagNotAttachedExceptionWhenNotAttached(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $entityTags = new InMemoryEntityTagRepository();
        $useCase = new DetachEntityTagUseCase($entities, $entityTags);

        $this->expectException(EntityTagNotAttachedException::class);

        $useCase->execute(new DetachEntityTagInput(entityId: $entityId, tagId: 1));
    }
}
