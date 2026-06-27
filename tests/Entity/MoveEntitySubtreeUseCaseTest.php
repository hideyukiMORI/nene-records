<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Entity;

use InvalidArgumentException;
use NeNeRecords\Entity\DuplicateEntityPermalinkException;
use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityNotFoundException;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\Entity\MoveEntitySubtreeInput;
use NeNeRecords\Entity\MoveEntitySubtreeUseCase;
use NeNeRecords\Tests\UrlRedirect\InMemoryUrlRedirectRepository;
use PHPUnit\Framework\TestCase;

final class MoveEntitySubtreeUseCaseTest extends TestCase
{
    private function entity(int $id, string $permalink): Entity
    {
        return new Entity(id: $id, entityTypeId: 1, permalink: $permalink, status: EntityStatus::Published);
    }

    public function testMovesRecordAndSubtreeRewritingPermalinksAndRecording301s(): void
    {
        $entities = new InMemoryEntityRepository([
            $this->entity(1, '/company/about'),
            $this->entity(2, '/company/about/team'),
            $this->entity(3, '/legal'),
        ]);
        $redirects = new InMemoryUrlRedirectRepository();
        $useCase = new MoveEntitySubtreeUseCase($entities, $redirects);

        $output = $useCase->execute(new MoveEntitySubtreeInput(1, '/legal/about'));

        self::assertSame(2, $output->movedCount);
        self::assertSame('/legal/about', $entities->findById(1)?->permalink);
        self::assertSame('/legal/about/team', $entities->findById(2)?->permalink);
        // The unrelated sibling is untouched.
        self::assertSame('/legal', $entities->findById(3)?->permalink);
        self::assertSame([
            '/company/about' => '/legal/about',
            '/company/about/team' => '/legal/about/team',
        ], $redirects->all());
    }

    public function testRejectsMovingIntoOwnSubtree(): void
    {
        $entities = new InMemoryEntityRepository([$this->entity(1, '/company/about')]);
        $useCase = new MoveEntitySubtreeUseCase($entities, new InMemoryUrlRedirectRepository());

        $this->expectException(InvalidArgumentException::class);
        $useCase->execute(new MoveEntitySubtreeInput(1, '/company/about/deep'));
    }

    public function testRejectsCollisionWithRecordOutsideSubtree(): void
    {
        $entities = new InMemoryEntityRepository([
            $this->entity(1, '/company/about'),
            $this->entity(2, '/legal/about'),
        ]);
        $useCase = new MoveEntitySubtreeUseCase($entities, new InMemoryUrlRedirectRepository());

        $this->expectException(DuplicateEntityPermalinkException::class);
        $useCase->execute(new MoveEntitySubtreeInput(1, '/legal/about'));
    }

    public function testRejectsMovingARecordWithoutAPermalink(): void
    {
        $entities = new InMemoryEntityRepository([
            new Entity(id: 1, entityTypeId: 1, permalink: null, status: EntityStatus::Published),
        ]);
        $useCase = new MoveEntitySubtreeUseCase($entities, new InMemoryUrlRedirectRepository());

        $this->expectException(InvalidArgumentException::class);
        $useCase->execute(new MoveEntitySubtreeInput(1, '/legal/about'));
    }

    public function testRejectsMovingAMissingRecord(): void
    {
        $useCase = new MoveEntitySubtreeUseCase(new InMemoryEntityRepository([]), new InMemoryUrlRedirectRepository());

        $this->expectException(EntityNotFoundException::class);
        $useCase->execute(new MoveEntitySubtreeInput(999, '/legal/about'));
    }

    public function testNoOpWhenDroppedOntoItsCurrentLocation(): void
    {
        $entities = new InMemoryEntityRepository([$this->entity(1, '/company/about')]);
        $redirects = new InMemoryUrlRedirectRepository();
        $useCase = new MoveEntitySubtreeUseCase($entities, $redirects);

        $output = $useCase->execute(new MoveEntitySubtreeInput(1, '/company/about'));

        self::assertSame(0, $output->movedCount);
        self::assertSame([], $redirects->all());
    }
}
