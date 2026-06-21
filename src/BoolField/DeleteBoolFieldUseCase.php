<?php

declare(strict_types=1);

namespace NeNeRecords\BoolField;

final readonly class DeleteBoolFieldUseCase implements DeleteBoolFieldUseCaseInterface
{
    public function __construct(
        private BoolFieldRepositoryInterface $boolFields,
    ) {
    }

    public function execute(DeleteBoolFieldByIdInput $input): void
    {
        $boolField = $this->boolFields->findById($input->id);

        if ($boolField === null) {
            throw new BoolFieldNotFoundException($input->id);
        }

        $this->boolFields->delete($input->id);
    }
}
