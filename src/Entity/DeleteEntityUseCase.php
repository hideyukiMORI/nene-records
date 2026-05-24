<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

final readonly class DeleteEntityUseCase implements DeleteEntityUseCaseInterface
{
    public function __construct(
        private EntityRepositoryInterface $entities,
    ) {
    }

    public function execute(DeleteEntityInput $input): void
    {
        $entity = $this->entities->findById($input->id);

        if ($entity === null) {
            throw new EntityNotFoundException($input->id);
        }

        $this->entities->softDelete($input->id);
    }
}
