<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\EntityType;

use NeNeRecords\EntityType\CreateEntityTypeInput;
use NeNeRecords\EntityType\CreateEntityTypeUseCase;
use NeNeRecords\EntityType\EntityType;
use NeNeRecords\EntityType\EntityTypeSlugConflictException;
use PHPUnit\Framework\TestCase;

final class CreateEntityTypeUseCaseTest extends TestCase
{
    public function testReturnsOutputWithNewId(): void
    {
        $repository = new InMemoryEntityTypeRepository([]);
        $useCase = new CreateEntityTypeUseCase($repository);

        $output = $useCase->execute(new CreateEntityTypeInput(name: 'Notebook', slug: 'notebook'));

        self::assertSame(1, $output->id);
        self::assertSame('Notebook', $output->name);
        self::assertSame('notebook', $output->slug);
    }

    public function testAssignsSequentialIds(): void
    {
        $repository = new InMemoryEntityTypeRepository([]);
        $useCase = new CreateEntityTypeUseCase($repository);

        $first = $useCase->execute(new CreateEntityTypeInput(name: 'A', slug: 'a'));
        $second = $useCase->execute(new CreateEntityTypeInput(name: 'B', slug: 'b'));

        self::assertSame(1, $first->id);
        self::assertSame(2, $second->id);
    }

    public function testThrowsEntityTypeSlugConflictExceptionOnDuplicateSlug(): void
    {
        $repository = new InMemoryEntityTypeRepository([]);
        $repository->save(new EntityType(name: 'First', slug: 'shared-slug'));

        $useCase = new CreateEntityTypeUseCase($repository);

        $this->expectException(EntityTypeSlugConflictException::class);

        $useCase->execute(new CreateEntityTypeInput(name: 'Second', slug: 'shared-slug'));
    }
}
