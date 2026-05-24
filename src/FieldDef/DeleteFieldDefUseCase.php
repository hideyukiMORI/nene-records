<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

final readonly class DeleteFieldDefUseCase implements DeleteFieldDefUseCaseInterface
{
    public function __construct(
        private FieldDefRepositoryInterface $fieldDefs,
    ) {
    }

    public function execute(DeleteFieldDefInput $input): void
    {
        $fieldDef = $this->fieldDefs->findById($input->id);

        if ($fieldDef === null) {
            throw new FieldDefNotFoundException($input->id);
        }

        $this->fieldDefs->softDelete($input->id);
    }
}
