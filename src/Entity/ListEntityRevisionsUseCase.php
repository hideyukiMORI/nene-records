<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

final readonly class ListEntityRevisionsUseCase implements ListEntityRevisionsUseCaseInterface
{
    public function __construct(
        private EntityRepositoryInterface $entities,
    ) {
    }

    public function execute(ListEntityRevisionsInput $input): ListEntityRevisionsOutput
    {
        if ($this->entities->findById($input->entityId) === null) {
            throw new EntityNotFoundException($input->entityId);
        }

        return new ListEntityRevisionsOutput(
            items: $this->entities->findRevisionsByEntityId($input->entityId, $input->limit, $input->offset),
            limit: $input->limit,
            offset: $input->offset,
        );
    }
}
