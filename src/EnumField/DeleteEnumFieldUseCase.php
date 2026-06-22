<?php

declare(strict_types=1);

namespace NeNeRecords\EnumField;

final readonly class DeleteEnumFieldUseCase implements DeleteEnumFieldUseCaseInterface
{
    public function __construct(
        private EnumFieldRepositoryInterface $enumFields,
    ) {
    }

    public function execute(DeleteEnumFieldByIdInput $input): void
    {
        $enumField = $this->enumFields->findById($input->id);

        if ($enumField === null) {
            throw new EnumFieldNotFoundException($input->id);
        }

        $this->enumFields->delete($input->id);
    }
}
