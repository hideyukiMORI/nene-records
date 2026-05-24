<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

interface DeleteEntityUseCaseInterface
{
    /**
     * @throws EntityNotFoundException when no non-deleted entity matches the given id.
     */
    public function execute(DeleteEntityInput $input): void;
}
