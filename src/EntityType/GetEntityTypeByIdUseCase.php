<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

final readonly class GetEntityTypeByIdUseCase implements GetEntityTypeByIdUseCaseInterface
{
    public function __construct(
        private EntityTypeRepositoryInterface $entityTypes,
    ) {
    }

    public function execute(GetEntityTypeByIdInput $input): GetEntityTypeByIdOutput
    {
        $entityType = $this->entityTypes->findById($input->id);

        if ($entityType === null) {
            throw new EntityTypeNotFoundException($input->id);
        }

        return new GetEntityTypeByIdOutput(
            id: $entityType->id ?? $input->id,
            name: $entityType->name,
            slug: $entityType->slug,
            isPinned: $entityType->isPinned,
        );
    }
}
