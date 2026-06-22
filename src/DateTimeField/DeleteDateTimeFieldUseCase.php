<?php

declare(strict_types=1);

namespace NeNeRecords\DateTimeField;

final readonly class DeleteDateTimeFieldUseCase implements DeleteDateTimeFieldUseCaseInterface
{
    public function __construct(
        private DateTimeFieldRepositoryInterface $dateTimeFields,
    ) {
    }

    public function execute(DeleteDateTimeFieldByIdInput $input): void
    {
        $dateTimeField = $this->dateTimeFields->findById($input->id);

        if ($dateTimeField === null) {
            throw new DateTimeFieldNotFoundException($input->id);
        }

        $this->dateTimeFields->delete($input->id);
    }
}
