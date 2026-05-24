<?php

declare(strict_types=1);

namespace NeNeRecords\TextField;

use NeNeRecords\Entity\EntityNotFoundException;
use NeNeRecords\Entity\EntityRepositoryInterface;

final readonly class CreateTextFieldUseCase implements CreateTextFieldUseCaseInterface
{
    public function __construct(
        private TextFieldRepositoryInterface $textFields,
        private EntityRepositoryInterface $entities,
    ) {
    }

    public function execute(CreateTextFieldInput $input): CreateTextFieldOutput
    {
        if ($this->entities->findById($input->entityId) === null) {
            throw new EntityNotFoundException($input->entityId);
        }

        $id = $this->textFields->save(new TextField(
            entityId: $input->entityId,
            fieldKey: $input->fieldKey,
            value: $input->value,
        ));

        return new CreateTextFieldOutput(
            id: $id,
            entityId: $input->entityId,
            fieldKey: $input->fieldKey,
            value: $input->value,
        );
    }
}
