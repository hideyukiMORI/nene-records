<?php

declare(strict_types=1);

namespace NeNeRecords\DateTimeField;

final readonly class GetDateTimeFieldByIdUseCase implements GetDateTimeFieldByIdUseCaseInterface
{
    public function __construct(
        private DateTimeFieldRepositoryInterface $intFields,
    ) {
    }

    public function execute(GetDateTimeFieldByIdInput $input): GetDateTimeFieldByIdOutput
    {
        $intField = $this->intFields->findById($input->id);

        if ($intField === null) {
            throw new DateTimeFieldNotFoundException($input->id);
        }

        return new GetDateTimeFieldByIdOutput(
            id: (int) $intField->id,
            entityId: $intField->entityId,
            fieldKey: $intField->fieldKey,
            value: $intField->value,
        );
    }
}
