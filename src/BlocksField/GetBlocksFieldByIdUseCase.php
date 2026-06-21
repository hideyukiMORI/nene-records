<?php

declare(strict_types=1);

namespace NeNeRecords\BlocksField;

final readonly class GetBlocksFieldByIdUseCase implements GetBlocksFieldByIdUseCaseInterface
{
    public function __construct(
        private BlocksFieldRepositoryInterface $blocksFields,
    ) {
    }

    public function execute(GetBlocksFieldByIdInput $input): GetBlocksFieldByIdOutput
    {
        $blocksField = $this->blocksFields->findById($input->id);

        if ($blocksField === null) {
            throw new BlocksFieldNotFoundException($input->id);
        }

        return new GetBlocksFieldByIdOutput(
            id: (int) $blocksField->id,
            entityId: $blocksField->entityId,
            fieldKey: $blocksField->fieldKey,
            value: $blocksField->value,
            locale: $blocksField->locale,
        );
    }
}
