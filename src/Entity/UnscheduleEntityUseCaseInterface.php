<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

interface UnscheduleEntityUseCaseInterface
{
    public function execute(int $entityId): void;
}
