<?php

declare(strict_types=1);

namespace NeNeRecords\DateTimeField;

final readonly class GetDateTimeFieldByIdUseCase implements GetDateTimeFieldByIdUseCaseInterface
{
    public function __construct(
        private DateTimeFieldRepositoryInterface $dateTimeFields,
    ) {
    }

    public function execute(GetDateTimeFieldByIdInput $input): GetDateTimeFieldByIdOutput
    {
        $dateTimeField = $this->dateTimeFields->findById($input->id);

        if ($dateTimeField === null) {
            throw new DateTimeFieldNotFoundException($input->id);
        }

        return new GetDateTimeFieldByIdOutput(
            id: (int) $dateTimeField->id,
            entityId: $dateTimeField->entityId,
            fieldKey: $dateTimeField->fieldKey,
            value: $dateTimeField->value,
        );
    }
}
