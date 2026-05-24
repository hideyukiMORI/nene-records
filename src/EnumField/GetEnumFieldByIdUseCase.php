<?php

declare(strict_types=1);

namespace NeNeRecords\EnumField;

final readonly class GetEnumFieldByIdUseCase implements GetEnumFieldByIdUseCaseInterface
{
    public function __construct(
        private EnumFieldRepositoryInterface $intFields,
    ) {
    }

    public function execute(GetEnumFieldByIdInput $input): GetEnumFieldByIdOutput
    {
        $intField = $this->intFields->findById($input->id);

        if ($intField === null) {
            throw new EnumFieldNotFoundException($input->id);
        }

        return new GetEnumFieldByIdOutput(
            id: (int) $intField->id,
            entityId: $intField->entityId,
            fieldKey: $intField->fieldKey,
            value: $intField->value,
        );
    }
}
