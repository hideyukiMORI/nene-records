<?php

declare(strict_types=1);

namespace NeNeRecords\BoolField;

final readonly class GetBoolFieldByIdUseCase implements GetBoolFieldByIdUseCaseInterface
{
    public function __construct(
        private BoolFieldRepositoryInterface $boolFields,
    ) {
    }

    public function execute(GetBoolFieldByIdInput $input): GetBoolFieldByIdOutput
    {
        $boolField = $this->boolFields->findById($input->id);

        if ($boolField === null) {
            throw new BoolFieldNotFoundException($input->id);
        }

        return new GetBoolFieldByIdOutput(
            id: (int) $boolField->id,
            entityId: $boolField->entityId,
            fieldKey: $boolField->fieldKey,
            value: $boolField->value,
        );
    }
}
