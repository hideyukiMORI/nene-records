<?php

declare(strict_types=1);

namespace NeNeRecords\EnumField;

final readonly class DeleteEnumFieldUseCase implements DeleteEnumFieldUseCaseInterface
{
    public function __construct(
        private EnumFieldRepositoryInterface $intFields,
    ) {
    }

    public function execute(DeleteEnumFieldByIdInput $input): void
    {
        $intField = $this->intFields->findById($input->id);

        if ($intField === null) {
            throw new EnumFieldNotFoundException($input->id);
        }

        $this->intFields->delete($input->id);
    }
}
