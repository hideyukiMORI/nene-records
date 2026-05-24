<?php

declare(strict_types=1);

namespace NeNeRecords\BoolField;

final readonly class DeleteBoolFieldUseCase implements DeleteBoolFieldUseCaseInterface
{
    public function __construct(
        private BoolFieldRepositoryInterface $intFields,
    ) {
    }

    public function execute(DeleteBoolFieldByIdInput $input): void
    {
        $intField = $this->intFields->findById($input->id);

        if ($intField === null) {
            throw new BoolFieldNotFoundException($input->id);
        }

        $this->intFields->delete($input->id);
    }
}
