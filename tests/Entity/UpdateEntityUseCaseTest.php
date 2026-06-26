<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Entity;

use NeNeRecords\Entity\DuplicateEntityPermalinkException;
use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\Entity\UpdateEntityInput;
use NeNeRecords\Entity\UpdateEntityUseCase;
use NeNeRecords\EntityType\EntityType;
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;
use NeNeRecords\Tests\UrlRedirect\InMemoryUrlRedirectRepository;
use PHPUnit\Framework\TestCase;

final class UpdateEntityUseCaseTest extends TestCase
{
    public function testRecordsRedirectWhenCustomPermalinkChanges(): void
    {
        $entityTypes = new InMemoryEntityTypeRepository([]);
        $typeId = $entityTypes->save(new EntityType(name: 'Page', slug: 'page'));
        $entities = new InMemoryEntityRepository([
            new Entity(id: 1, entityTypeId: $typeId, permalink: '/company/about/team', status: EntityStatus::Published),
        ]);
        $redirects = new InMemoryUrlRedirectRepository();
        $useCase = new UpdateEntityUseCase($entities, $entityTypes, null, $redirects);

        $useCase->execute(new UpdateEntityInput(
            id: 1,
            entityTypeId: $typeId,
            status: EntityStatus::Published,
            permalink: '/company/team',
        ));

        self::assertSame(['/company/about/team' => '/company/team'], $redirects->all());
        self::assertSame('/company/team', $entities->findById(1)?->permalink);
    }

    public function testRecordsRedirectFromTypePatternWhenPermalinkFirstSet(): void
    {
        $entityTypes = new InMemoryEntityTypeRepository([]);
        $typeId = $entityTypes->save(new EntityType(name: 'Post', slug: 'posts'));
        $entities = new InMemoryEntityRepository([
            new Entity(id: 5, entityTypeId: $typeId, status: EntityStatus::Published),
        ]);
        $redirects = new InMemoryUrlRedirectRepository();
        $useCase = new UpdateEntityUseCase($entities, $entityTypes, null, $redirects);

        $useCase->execute(new UpdateEntityInput(
            id: 5,
            entityTypeId: $typeId,
            status: EntityStatus::Published,
            permalink: '/company/about',
        ));

        // Old canonical = the default /{type}/{id} pattern → /posts/5.
        self::assertSame(['/posts/5' => '/company/about'], $redirects->all());
    }

    public function testNoRedirectWhenPermalinkUnchanged(): void
    {
        $entityTypes = new InMemoryEntityTypeRepository([]);
        $typeId = $entityTypes->save(new EntityType(name: 'Page', slug: 'page'));
        $entities = new InMemoryEntityRepository([
            new Entity(id: 1, entityTypeId: $typeId, slug: 'a', permalink: '/keep', status: EntityStatus::Published),
        ]);
        $redirects = new InMemoryUrlRedirectRepository();
        $useCase = new UpdateEntityUseCase($entities, $entityTypes, null, $redirects);

        $useCase->execute(new UpdateEntityInput(
            id: 1,
            entityTypeId: $typeId,
            slug: 'a',
            status: EntityStatus::Published,
            permalink: '/keep',
        ));

        self::assertSame([], $redirects->all());
    }

    public function testRejectsDuplicatePermalinkOnUpdate(): void
    {
        $entityTypes = new InMemoryEntityTypeRepository([]);
        $typeId = $entityTypes->save(new EntityType(name: 'Page', slug: 'page'));
        $entities = new InMemoryEntityRepository([
            new Entity(id: 1, entityTypeId: $typeId, permalink: '/taken'),
            new Entity(id: 2, entityTypeId: $typeId),
        ]);
        $useCase = new UpdateEntityUseCase($entities, $entityTypes, null, new InMemoryUrlRedirectRepository());

        $this->expectException(DuplicateEntityPermalinkException::class);
        $useCase->execute(new UpdateEntityInput(
            id: 2,
            entityTypeId: $typeId,
            status: EntityStatus::Draft,
            permalink: '/taken',
        ));
    }
}
