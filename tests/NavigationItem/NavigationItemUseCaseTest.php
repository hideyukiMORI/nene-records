<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\NavigationItem;

use NeNeRecords\NavigationItem\CreateNavigationItemInput;
use NeNeRecords\NavigationItem\CreateNavigationItemUseCase;
use NeNeRecords\NavigationItem\DeleteNavigationItemInput;
use NeNeRecords\NavigationItem\DeleteNavigationItemUseCase;
use NeNeRecords\NavigationItem\NavigationItemNotFoundException;
use NeNeRecords\NavigationItem\UpdateNavigationItemInput;
use NeNeRecords\NavigationItem\UpdateNavigationItemUseCase;
use PHPUnit\Framework\TestCase;

final class CreateNavigationItemUseCaseTest extends TestCase
{
    public function testCreatesItemAndReturnsOutputWithCorrectFields(): void
    {
        $repository = new InMemoryNavigationItemRepository();
        $useCase = new CreateNavigationItemUseCase($repository);

        $output = $useCase->execute(new CreateNavigationItemInput(
            label: 'Home',
            url: 'https://example.com',
            location: 'header',
            displayOrder: 1,
        ));

        self::assertSame(1, $output->item->id);
        self::assertSame('Home', $output->item->label);
        self::assertSame('https://example.com', $output->item->url);
        self::assertSame('header', $output->item->location);
        self::assertSame(1, $output->item->displayOrder);
    }

    public function testAssignsSequentialIds(): void
    {
        $repository = new InMemoryNavigationItemRepository();
        $useCase = new CreateNavigationItemUseCase($repository);

        $first = $useCase->execute(new CreateNavigationItemInput(
            label: 'Home',
            url: 'https://example.com',
            location: 'header',
            displayOrder: 1,
        ));
        $second = $useCase->execute(new CreateNavigationItemInput(
            label: 'About',
            url: 'https://example.com/about',
            location: 'header',
            displayOrder: 2,
        ));

        self::assertSame(1, $first->item->id);
        self::assertSame(2, $second->item->id);
    }

    public function testSetsCreatedAtAndUpdatedAt(): void
    {
        $repository = new InMemoryNavigationItemRepository();
        $useCase = new CreateNavigationItemUseCase($repository);

        $output = $useCase->execute(new CreateNavigationItemInput(
            label: 'Home',
            url: 'https://example.com',
            location: 'header',
            displayOrder: 1,
        ));

        self::assertNotSame('', $output->item->createdAt);
        self::assertNotSame('', $output->item->updatedAt);
    }
}

final class UpdateNavigationItemUseCaseTest extends TestCase
{
    public function testUpdatesItemAndReturnsOutput(): void
    {
        $repository = new InMemoryNavigationItemRepository();
        $useCase = new CreateNavigationItemUseCase($repository);
        $useCase->execute(new CreateNavigationItemInput(
            label: 'Home',
            url: 'https://example.com',
            location: 'header',
            displayOrder: 1,
        ));

        $updateUseCase = new UpdateNavigationItemUseCase($repository);
        $output = $updateUseCase->execute(new UpdateNavigationItemInput(
            id: 1,
            label: 'Updated Home',
            url: 'https://example.com/new',
            location: 'footer',
            displayOrder: 10,
        ));

        self::assertSame(1, $output->item->id);
        self::assertSame('Updated Home', $output->item->label);
        self::assertSame('https://example.com/new', $output->item->url);
        self::assertSame('footer', $output->item->location);
        self::assertSame(10, $output->item->displayOrder);
    }

    public function testThrowsNavigationItemNotFoundExceptionIfNotFound(): void
    {
        $repository = new InMemoryNavigationItemRepository();
        $useCase = new UpdateNavigationItemUseCase($repository);

        $this->expectException(NavigationItemNotFoundException::class);

        $useCase->execute(new UpdateNavigationItemInput(
            id: 99,
            label: 'Ghost',
            url: 'https://ghost.example.com',
            location: 'header',
            displayOrder: 1,
        ));
    }
}

final class DeleteNavigationItemUseCaseTest extends TestCase
{
    public function testDeletesItem(): void
    {
        $repository = new InMemoryNavigationItemRepository();
        $createUseCase = new CreateNavigationItemUseCase($repository);
        $createUseCase->execute(new CreateNavigationItemInput(
            label: 'Home',
            url: 'https://example.com',
            location: 'header',
            displayOrder: 1,
        ));

        $deleteUseCase = new DeleteNavigationItemUseCase($repository);
        $deleteUseCase->execute(new DeleteNavigationItemInput(id: 1));

        self::assertNull($repository->findById(1));
    }

    public function testThrowsNavigationItemNotFoundExceptionIfNotFound(): void
    {
        $repository = new InMemoryNavigationItemRepository();
        $useCase = new DeleteNavigationItemUseCase($repository);

        $this->expectException(NavigationItemNotFoundException::class);

        $useCase->execute(new DeleteNavigationItemInput(id: 99));
    }
}
