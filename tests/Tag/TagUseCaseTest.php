<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Tag;

use NeNeRecords\Tag\CreateTagInput;
use NeNeRecords\Tag\CreateTagUseCase;
use NeNeRecords\Tag\DeleteTagInput;
use NeNeRecords\Tag\DeleteTagUseCase;
use NeNeRecords\Tag\GetTagByIdInput;
use NeNeRecords\Tag\GetTagByIdUseCase;
use NeNeRecords\Tag\Tag;
use NeNeRecords\Tag\TagNotFoundException;
use NeNeRecords\Tag\TagSlugConflictException;
use NeNeRecords\Tag\UpdateTagInput;
use NeNeRecords\Tag\UpdateTagUseCase;
use PHPUnit\Framework\TestCase;

final class CreateTagUseCaseTest extends TestCase
{
    public function testCreatesTagAndReturnsOutput(): void
    {
        $tags = new InMemoryTagRepository([]);
        $useCase = new CreateTagUseCase($tags);

        $output = $useCase->execute(new CreateTagInput(name: 'Music', slug: 'music'));

        self::assertSame(1, $output->id);
        self::assertSame('music', $output->slug);
        self::assertSame('Music', $output->name);
    }

    public function testAssignsSequentialIds(): void
    {
        $tags = new InMemoryTagRepository([]);
        $useCase = new CreateTagUseCase($tags);

        $first = $useCase->execute(new CreateTagInput(name: 'Music', slug: 'music'));
        $second = $useCase->execute(new CreateTagInput(name: 'Jazz', slug: 'jazz'));

        self::assertSame(1, $first->id);
        self::assertSame(2, $second->id);
    }

    public function testThrowsTagSlugConflictExceptionForDuplicateSlug(): void
    {
        $tags = new InMemoryTagRepository([
            new Tag(slug: 'music', name: 'Music', id: 1),
        ]);
        $useCase = new CreateTagUseCase($tags);

        $this->expectException(TagSlugConflictException::class);

        $useCase->execute(new CreateTagInput(name: 'Other Music', slug: 'music'));
    }
}

final class UpdateTagUseCaseTest extends TestCase
{
    public function testUpdatesTagAndReturnsOutput(): void
    {
        $tags = new InMemoryTagRepository([
            new Tag(slug: 'music', name: 'Music', id: 1),
        ]);
        $useCase = new UpdateTagUseCase($tags);

        $output = $useCase->execute(new UpdateTagInput(id: 1, name: 'Updated Music', slug: 'music-updated'));

        self::assertSame(1, $output->id);
        self::assertSame('music-updated', $output->slug);
        self::assertSame('Updated Music', $output->name);
    }

    public function testThrowsTagNotFoundExceptionIfNotFound(): void
    {
        $tags = new InMemoryTagRepository([]);
        $useCase = new UpdateTagUseCase($tags);

        $this->expectException(TagNotFoundException::class);

        $useCase->execute(new UpdateTagInput(id: 99, name: 'Ghost', slug: 'ghost'));
    }

    public function testThrowsTagSlugConflictExceptionForDuplicateSlug(): void
    {
        $tags = new InMemoryTagRepository([
            new Tag(slug: 'music', name: 'Music', id: 1),
            new Tag(slug: 'jazz', name: 'Jazz', id: 2),
        ]);
        $useCase = new UpdateTagUseCase($tags);

        $this->expectException(TagSlugConflictException::class);

        $useCase->execute(new UpdateTagInput(id: 1, name: 'Music', slug: 'jazz'));
    }

    public function testAllowsUpdatingTagWithSameSlug(): void
    {
        $tags = new InMemoryTagRepository([
            new Tag(slug: 'music', name: 'Music', id: 1),
        ]);
        $useCase = new UpdateTagUseCase($tags);

        $output = $useCase->execute(new UpdateTagInput(id: 1, name: 'Updated Name', slug: 'music'));

        self::assertSame(1, $output->id);
        self::assertSame('music', $output->slug);
        self::assertSame('Updated Name', $output->name);
    }
}

final class DeleteTagUseCaseTest extends TestCase
{
    public function testDeletesTag(): void
    {
        $tags = new InMemoryTagRepository([
            new Tag(slug: 'music', name: 'Music', id: 1),
        ]);
        $useCase = new DeleteTagUseCase($tags);

        $useCase->execute(new DeleteTagInput(id: 1));

        self::assertNull($tags->findById(1));
    }

    public function testThrowsTagNotFoundExceptionIfNotFound(): void
    {
        $tags = new InMemoryTagRepository([]);
        $useCase = new DeleteTagUseCase($tags);

        $this->expectException(TagNotFoundException::class);

        $useCase->execute(new DeleteTagInput(id: 99));
    }
}

final class GetTagByIdUseCaseTest extends TestCase
{
    public function testReturnsTag(): void
    {
        $tags = new InMemoryTagRepository([
            new Tag(slug: 'music', name: 'Music', id: 1),
        ]);
        $useCase = new GetTagByIdUseCase($tags);

        $output = $useCase->execute(new GetTagByIdInput(id: 1));

        self::assertSame(1, $output->id);
        self::assertSame('music', $output->slug);
        self::assertSame('Music', $output->name);
    }

    public function testThrowsTagNotFoundExceptionIfNotFound(): void
    {
        $tags = new InMemoryTagRepository([]);
        $useCase = new GetTagByIdUseCase($tags);

        $this->expectException(TagNotFoundException::class);

        $useCase->execute(new GetTagByIdInput(id: 99));
    }
}
