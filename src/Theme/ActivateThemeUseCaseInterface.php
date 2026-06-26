<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

interface ActivateThemeUseCaseInterface
{
    public function execute(ActivateThemeInput $input): ActivateThemeOutput;
}
