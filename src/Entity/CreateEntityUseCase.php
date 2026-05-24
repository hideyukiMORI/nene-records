<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use NeNeRecords\EntityType\EntityTypeNotFoundException;
use NeNeRecords\EntityType\EntityTypeRepositoryInterface;

final readonly class CreateEntityUseCase implements CreateEntityUseCaseInterface
{
    public function __construct(
        private EntityRepositoryInterface $entities,
        private EntityTypeRepositoryInterface $entityTypes,
    ) {
    }

    public function execute(CreateEntityInput $input): CreateEntityOutput
    {
        if ($this->entityTypes->findById($input->entityTypeId) === null) {
            throw new EntityTypeNotFoundException($input->entityTypeId);
        }

        $slug = $this->normalizeSlug($input->slug);

        if ($slug !== null && $this->entities->existsBySlug($slug, $input->entityTypeId)) {
            throw new DuplicateEntitySlugException($slug);
        }

        $id = $this->entities->save(new Entity(
            id: null,
            entityTypeId: $input->entityTypeId,
            slug: $slug,
            status: $input->status,
        ));

        return new CreateEntityOutput(
            id: $id,
            entityTypeId: $input->entityTypeId,
            slug: $slug,
            status: $input->status,
            publishedAtIso: null,
            isDeleted: false,
            deletedAtIso: null,
        );
    }

    private function normalizeSlug(?string $slug): ?string
    {
        if ($slug === null) {
            return null;
        }

        $normalized = trim($slug);

        return $normalized !== '' ? $normalized : null;
    }
}
