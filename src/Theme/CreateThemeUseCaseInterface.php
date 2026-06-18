<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

interface CreateThemeUseCaseInterface
{
    public function execute(CreateThemeInput $input): CreateThemeOutput;
}
