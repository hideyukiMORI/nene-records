<?php

declare(strict_types=1);

namespace NeNeRecords\EntityArchive;

interface GetEntityArchiveCsvUseCaseInterface
{
    public function execute(GetEntityArchiveCsvInput $input): GetEntityArchiveCsvOutput;
}
