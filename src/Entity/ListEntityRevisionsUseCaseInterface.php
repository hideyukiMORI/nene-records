<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

interface ListEntityRevisionsUseCaseInterface
{
    public function execute(ListEntityRevisionsInput $input): ListEntityRevisionsOutput;
}
