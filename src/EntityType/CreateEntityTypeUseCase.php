<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

final readonly class CreateEntityTypeUseCase implements CreateEntityTypeUseCaseInterface
{
    public function __construct(
        private EntityTypeRepositoryInterface $entityTypes,
    ) {
    }

    public function execute(CreateEntityTypeInput $input): CreateEntityTypeOutput
    {
        $existing = $this->entityTypes->findBySlug($input->slug);

        if ($existing !== null) {
            throw new EntityTypeSlugConflictException($input->slug);
        }

        $id = $this->entityTypes->save(new EntityType(name: $input->name, slug: $input->slug));

        return new CreateEntityTypeOutput(id: $id, name: $input->name, slug: $input->slug);
    }
}
