<?php

declare(strict_types=1);

namespace NeNeRecords\IntField;

final readonly class GetIntFieldByIdUseCase implements GetIntFieldByIdUseCaseInterface
{
    public function __construct(
        private IntFieldRepositoryInterface $intFields,
    ) {
    }

    public function execute(GetIntFieldByIdInput $input): GetIntFieldByIdOutput
    {
        $intField = $this->intFields->findById($input->id);

        if ($intField === null) {
            throw new IntFieldNotFoundException($input->id);
        }

        return new GetIntFieldByIdOutput(
            id: (int) $intField->id,
            entityId: $intField->entityId,
            fieldKey: $intField->fieldKey,
            value: $intField->value,
        );
    }
}
