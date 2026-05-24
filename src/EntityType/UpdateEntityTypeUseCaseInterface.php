<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

interface UpdateEntityTypeUseCaseInterface
{
    /**
     * @throws EntityTypeNotFoundException when no entity type matches the given id.
     * @throws EntityTypeSlugConflictException when the slug is taken by another record.
     */
    public function execute(UpdateEntityTypeInput $input): UpdateEntityTypeOutput;
}
