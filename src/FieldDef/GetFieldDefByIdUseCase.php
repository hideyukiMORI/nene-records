<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

final readonly class GetFieldDefByIdUseCase implements GetFieldDefByIdUseCaseInterface
{
    public function __construct(
        private FieldDefRepositoryInterface $fieldDefs,
    ) {
    }

    public function execute(GetFieldDefByIdInput $input): GetFieldDefByIdOutput
    {
        $fieldDef = $this->fieldDefs->findById($input->id);

        if ($fieldDef === null) {
            throw new FieldDefNotFoundException($input->id);
        }

        return new GetFieldDefByIdOutput(
            id: $fieldDef->id ?? $input->id,
            entityTypeId: $fieldDef->entityTypeId,
            fieldKey: $fieldDef->fieldKey,
            dataType: $fieldDef->dataType,
            targetEntityTypeId: $fieldDef->targetEntityTypeId,
            cardinality: $fieldDef->cardinality,
        );
    }
}
