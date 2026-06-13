<?php

declare(strict_types=1);

namespace NeNeRecords\Menu;

final readonly class UpdateMenuUseCase implements UpdateMenuUseCaseInterface
{
    public function __construct(
        private MenuRepositoryInterface $repository,
    ) {
    }

    public function execute(UpdateMenuInput $input): UpdateMenuOutput
    {
        $existing = $this->repository->findById($input->id);

        if ($existing === null) {
            throw new MenuNotFoundException($input->id);
        }

        // Keep the existing slug unless the name changed, then re-derive uniquely.
        $slug = $existing->slug;
        if ($input->name !== $existing->name) {
            $slug = MenuSlug::unique(MenuSlug::fromName($input->name), $this->repository, $existing->id);
        }

        $this->repository->update(new Menu(
            id: $existing->id,
            name: $input->name,
            slug: $slug,
            location: $input->location,
            createdAt: $existing->createdAt,
            updatedAt: '',
        ));

        $saved = $this->repository->findById($input->id);

        if ($saved === null) {
            throw new \RuntimeException('Failed to reload menu after update.');
        }

        return new UpdateMenuOutput(menu: $saved);
    }
}
