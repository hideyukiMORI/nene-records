<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

final readonly class ListEntityTypesUseCase implements ListEntityTypesUseCaseInterface
{
    public function __construct(
        private EntityTypeRepositoryInterface $entityTypes,
    ) {
    }

    public function execute(ListEntityTypesInput $input): ListEntityTypesOutput
    {
        $rows = $this->entityTypes->findAll($input->limit, $input->offset);

        $items = array_map(
            static fn (EntityType $entityType) => new ListEntityTypeItem(
                id: (int) $entityType->id,
                name: $entityType->name,
                slug: $entityType->slug,
                isPinned: $entityType->isPinned,
                labels: $entityType->labels,
                permalinkPattern: $entityType->permalinkPattern,
                previousPermalinkPattern: $entityType->previousPermalinkPattern,
                displayOrder: $entityType->displayOrder,
                defaultLayout: $entityType->defaultLayout,
            ),
            $rows,
        );

        return new ListEntityTypesOutput(
            items: $items,
            limit: $input->limit,
            offset: $input->offset,
        );
    }
}
