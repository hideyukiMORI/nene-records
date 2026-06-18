<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

interface UpdateThemeUseCaseInterface
{
    public function execute(UpdateThemeInput $input): UpdateThemeOutput;
}
