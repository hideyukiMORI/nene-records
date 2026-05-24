<?php

declare(strict_types=1);

namespace NeNeRecords\BoolField;

final readonly class GetBoolFieldByIdUseCase implements GetBoolFieldByIdUseCaseInterface
{
    public function __construct(
        private BoolFieldRepositoryInterface $intFields,
    ) {
    }

    public function execute(GetBoolFieldByIdInput $input): GetBoolFieldByIdOutput
    {
        $intField = $this->intFields->findById($input->id);

        if ($intField === null) {
            throw new BoolFieldNotFoundException($input->id);
        }

        return new GetBoolFieldByIdOutput(
            id: (int) $intField->id,
            entityId: $intField->entityId,
            fieldKey: $intField->fieldKey,
            value: $intField->value,
        );
    }
}
