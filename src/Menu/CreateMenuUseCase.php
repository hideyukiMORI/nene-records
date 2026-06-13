<?php

declare(strict_types=1);

namespace NeNeRecords\Menu;

final readonly class CreateMenuUseCase implements CreateMenuUseCaseInterface
{
    public function __construct(
        private MenuRepositoryInterface $repository,
    ) {
    }

    public function execute(CreateMenuInput $input): CreateMenuOutput
    {
        $slug = MenuSlug::unique(MenuSlug::fromName($input->name), $this->repository);

        $menu = new Menu(
            id: null,
            name: $input->name,
            slug: $slug,
            location: $input->location,
            createdAt: '',
            updatedAt: '',
        );

        $id = $this->repository->save($menu);
        $saved = $this->repository->findById($id);

        if ($saved === null) {
            throw new \RuntimeException('Failed to persist menu.');
        }

        return new CreateMenuOutput(menu: $saved);
    }
}
