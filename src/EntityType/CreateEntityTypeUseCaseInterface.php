<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

interface CreateEntityTypeUseCaseInterface
{
    /**
     * @throws EntityTypeSlugConflictException when the slug is taken.
     */
    public function execute(CreateEntityTypeInput $input): CreateEntityTypeOutput;
}
