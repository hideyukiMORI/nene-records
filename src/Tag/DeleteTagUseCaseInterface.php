<?php

declare(strict_types=1);

namespace NeNeRecords\Tag;

interface DeleteTagUseCaseInterface
{
    public function execute(DeleteTagInput $input): void;
}
