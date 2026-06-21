<?php

declare(strict_types=1);

namespace NeNeRecords\BlocksField;

final readonly class DeleteBlocksFieldUseCase implements DeleteBlocksFieldUseCaseInterface
{
    public function __construct(
        private BlocksFieldRepositoryInterface $blocksFields,
    ) {
    }

    public function execute(DeleteBlocksFieldByIdInput $input): void
    {
        $blocksField = $this->blocksFields->findById($input->id);

        if ($blocksField === null) {
            throw new BlocksFieldNotFoundException($input->id);
        }

        $this->blocksFields->delete($input->id);
    }
}
