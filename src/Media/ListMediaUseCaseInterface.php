<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

interface ListMediaUseCaseInterface
{
    public function execute(): ListMediaOutput;
}
