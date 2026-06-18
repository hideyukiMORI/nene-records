<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

interface DeleteThemeUseCaseInterface
{
    public function execute(DeleteThemeInput $input): void;
}
