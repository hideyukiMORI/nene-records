<?php

declare(strict_types=1);

namespace NeNeRecords\EnumField;

final readonly class GetEnumFieldByIdUseCase implements GetEnumFieldByIdUseCaseInterface
{
    public function __construct(
        private EnumFieldRepositoryInterface $enumFields,
    ) {
    }

    public function execute(GetEnumFieldByIdInput $input): GetEnumFieldByIdOutput
    {
        $enumField = $this->enumFields->findById($input->id);

        if ($enumField === null) {
            throw new EnumFieldNotFoundException($input->id);
        }

        return new GetEnumFieldByIdOutput(
            id: (int) $enumField->id,
            entityId: $enumField->entityId,
            fieldKey: $enumField->fieldKey,
            value: $enumField->value,
        );
    }
}
