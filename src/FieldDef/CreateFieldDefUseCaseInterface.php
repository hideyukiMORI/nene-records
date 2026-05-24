<?php

declare(strict_types=1);

namespace NeNeRecords\FieldDef;

use NeNeRecords\EntityType\EntityTypeNotFoundException;

interface CreateFieldDefUseCaseInterface
{
    /**
     * @throws EntityTypeNotFoundException when the entity type does not exist.
     * @throws FieldDefConflictException when the field key is taken for the entity type.
     */
    public function execute(CreateFieldDefInput $input): CreateFieldDefOutput;
}
