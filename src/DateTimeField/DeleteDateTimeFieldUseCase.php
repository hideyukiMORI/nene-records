<?php

declare(strict_types=1);

namespace NeNeRecords\DateTimeField;

final readonly class DeleteDateTimeFieldUseCase implements DeleteDateTimeFieldUseCaseInterface
{
    public function __construct(
        private DateTimeFieldRepositoryInterface $intFields,
    ) {
    }

    public function execute(DeleteDateTimeFieldByIdInput $input): void
    {
        $intField = $this->intFields->findById($input->id);

        if ($intField === null) {
            throw new DateTimeFieldNotFoundException($input->id);
        }

        $this->intFields->delete($input->id);
    }
}
