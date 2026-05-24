<?php

declare(strict_types=1);

namespace NeNeRecords\IntField;

final readonly class DeleteIntFieldUseCase implements DeleteIntFieldUseCaseInterface
{
    public function __construct(
        private IntFieldRepositoryInterface $intFields,
    ) {
    }

    public function execute(DeleteIntFieldByIdInput $input): void
    {
        $intField = $this->intFields->findById($input->id);

        if ($intField === null) {
            throw new IntFieldNotFoundException($input->id);
        }

        $this->intFields->delete($input->id);
    }
}
