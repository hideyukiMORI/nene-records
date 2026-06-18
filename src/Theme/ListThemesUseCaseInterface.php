<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

interface ListThemesUseCaseInterface
{
    public function execute(): ListThemesOutput;
}
